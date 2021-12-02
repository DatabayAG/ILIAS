<?php declare(strict_types=1);

namespace ILIAS\Mail\Setup\Objective;

use ILIAS\Setup\Metrics\CollectedObjective;
use ILIAS\Setup\Environment;
use ILIAS\Setup\Metrics\Storage;
use ILIAS\DI\Container;
use ILIAS\Setup\Metrics\Metric;

class MetricsCollectedObjective extends CollectedObjective
{
    private \ilDBInterface $database;

    protected function getTentativePreconditions(Environment $environment) : array
    {
        return [
            new \ilIniFilesLoadedObjective(),
            new \ilDatabaseInitializedObjective(),
        ];
    }

    protected function collectFrom(Environment $environment, Storage $storage) : void
    {
        $this->database = $environment->getResource(Environment::RESOURCE_DATABASE);
        $storage->store('Database FK Violations', new Metric(
            Metric::STABILITY_VOLATILE,
            Metric::TYPE_COLLECTION,
            $this->collectMetrics(),
            'Holds information about the number of violations for intended foreign keys'
        ));
    }

    /**
     * @return array<string, Metric>
     */
    private function collectMetrics() : array
    {
        $metrics = [];
        foreach ($this->definitions() as $definition) {
            $metric = $this->metric($definition);
            if ($metric->getValue() > 0) {
                $metrics[$this->buildField($definition)] = $metric;
            }
        }

        return $metrics;
    }

    private function metric(Definition $definition) : Metric
    {
        return new Metric(
            Metric::STABILITY_VOLATILE,
            Metric::TYPE_GAUGE,
            $this->query($definition),
            'Number of violations for the intended FK on field ' . $this->buildField($definition)
        );
    }

    private function buildField(Definition $definition) : string
    {
        return join('|', array_map(static function (Association $association) {
            return $association->field()->fieldName();
        }, $definition->associations()));
    }

    private function query(Definition $definition) : int
    {
        $on = [];
        $where = [];
        // $definition->associations() always returns a non empty array
        foreach ($definition->associations() as $association) {
            $on[] = sprintf('%s = %s', $association->field()->fieldName(), $association->referenceField()->fieldName());
            $where[] = sprintf('%s IS NULL', $association->referenceField()->fieldName());
            foreach ($definition->ignoreValues() as $valueToIgnore) {
                $where[] = sprintf('%s %s', $association->field()->fieldName(), $valueToIgnore);
            }
        }

        $result = $this->database->query(sprintf(
            'SELECT COUNT(1) as violations FROM %s LEFT JOIN %s ON %s WHERE %s',
            $definition->tableName(),
            $definition->referenceTableName(),
            join(' AND ', $on),
            join(' AND ', $where),
        ));

        $result = $this->database->fetchAssoc($result);

        return (int) $result['violations'];
    }

    /**
     * @return Definition[]
     */
    private function definitions() : array
    {
        $mailId = new Field('mail', 'mail_id');
        $mailObjDataId = new Field('mail_obj_data', 'obj_id');

        return [
            new Definition([new Association(new Field('mail', 'folder_id'), $mailObjDataId)]),
            new Definition([new Association(new Field('mail_attachment', 'mail_id'), $mailId)]),
            new Definition([new Association(new Field('mail_cron_orphaned', 'mail_id'), $mailId)]),
            new Definition([new Association(new Field('mail_cron_orphaned', 'folder_id'), $mailObjDataId)]),
            new Definition([new Association(new Field('mail_tree', 'child'), $mailObjDataId)]),
            new Definition([new Association(new Field('mail_tree', 'parent'), new Field('mail_tree', 'child', 'parent'))], new Ignore(null, '0')),
        ];
    }
}
