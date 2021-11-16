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
            Metric::STABILITY_STABLE,
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
                $metrics[$definition->field()->fieldName()] = $metric;
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
            'Number of violations for the intended FK on field ' . $definition->field()->fieldName()
        );
    }

    private function query(Definition $definition) : int
    {
        $result = $this->database->query(sprintf(
            'select count(1) from %s left join %s on %s = %s where %s is NULL%s',
            $definition->field()->tableName(),
            $definition->referenceField()->tableName(),
            $definition->field()->fieldName(),
            $definition->referenceField()->fieldName(),
            $definition->referenceField()->fieldName(),
            $definition->nullable() ? ' and ' . $definition->field()->fieldName() . ' is not null' : ''
        ));

        $result = $this->database->fetchAssoc($result);

        return (int) $result['count(1)'];
    }

    /**
     * @return Definition[]
     */
    private function definitions() : array
    {
        $userId = new Field('usr_data', 'usr_id');
        $mailId = new Field('mail', 'mail_id');
        $mailObjDataId = new Field('mail_obj_data', 'obj_id');

        return [
            new Definition(new Field('mail', 'user_id'), $userId),
            new Definition(new Field('mail', 'folder_id'), $mailObjDataId),
            new Definition(new Field('mail', 'sender_id'), $userId, Definition::NULLABLE),
            new Definition(new Field('mail_attachment', 'mail_id'), $mailId),
            new Definition(new Field('mail_cron_orphaned', 'mail_id'), $mailId),
            new Definition(new Field('mail_cron_orphaned', 'folder_id'), $mailObjDataId),
            new Definition(new Field('mail_obj_data', 'user_id'), $userId),
            new Definition(new Field('mail_options', 'user_id'), $userId),
            new Definition(new Field('mail_saved', 'user_id'), $userId),
            new Definition(new Field('mail_tree', 'child'), $mailObjDataId),
            new Definition(new Field('mail_tree', 'parent'), new Field('mail_tree', 'child', 'parent'), Definition::NULLABLE),
            new Definition(new Field('mail_tree', 'tree'), $userId),
        ];
    }
}
