<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

use ILIAS\Data;
use ILIAS\UI;
use Psr\Http\Message\ServerRequestInterface;

class ForumModeratorsTable
{
    private ilForumModerators $forum_moderators;
    protected \ilCtrl $ctrl;
    protected \ilLanguage $lng;
    protected UI\Factory $ui_factory;
    protected ServerRequestInterface $request;
    protected Data\Factory $data_factory;

    public function __construct(ilForumModerators $forum_moderators)
    {
        global $DIC;

        $this->ctrl = $DIC->ctrl();
        $this->lng = $DIC->language();
        $this->ui_factory = $DIC->ui()->factory();
        $this->request = $DIC->http()->request();
        $this->data_factory = new Data\Factory();
        $this->forum_moderators = $forum_moderators;
    }

    public function getComponent(): UI\Component\Table\Data
    {
        $columns = $this->getColumns();
        $actions = $this->getActions();
        $data_retrieval = $this->getDataRetrieval();

        return $this->ui_factory->table()
                                ->data($this->lng->txt('frm_moderators'), $columns, $data_retrieval)
                                ->withActions($actions)
                                ->withRequest($this->request);
    }

    protected function getColumns(): array
    {
        return [
            'usr_id' => $this->ui_factory->table()->column()->number('User ID')
                                         ->withIsSortable(false),

            'login' => $this->ui_factory->table()->column()->text($this->lng->txt('login'))
                                        ->withIsSortable(true),

            'firstname' => $this->ui_factory->table()->column()->text($this->lng->txt('firstname'))
                                            ->withIsSortable(true),

            'lastname' => $this->ui_factory->table()->column()->text($this->lng->txt('lastname'))
                                           ->withIsSortable(true),
        ];
    }

    protected function getActions(): array
    {
        $query_params_namespace = ['frm_moderators_table'];

        $uri_detach = $this->data_factory->uri(
            ILIAS_HTTP_PATH . '/' . $this->ctrl->getLinkTargetByClass(
                ilForumModeratorsGUI::class,
                'detachModeratorRole'
            )
        );

        $url_builder_detach = new UI\URLBuilder($uri_detach);
        list(
            $url_builder_detach, $action_parameter_token_copy, $row_id_token_detach
        ) =
            $url_builder_detach->acquireParameters(
                $query_params_namespace,
                'action',
                'usr_ids'
            );

        return [
            'detachModeratorRole' => $this->ui_factory->table()->action()->multi(
                $this->lng->txt('remove'),
                $url_builder_detach->withParameter($action_parameter_token_copy, 'detachModeratorRole'),
                $row_id_token_detach
            ),
        ];
    }

    protected function getDataRetrieval(): UI\Component\Table\DataRetrieval
    {
        $data_retrieval = new class($this->forum_moderators) implements UI\Component\Table\DataRetrieval {

            private ?array $records = null;

            public function __construct(protected readonly \ilForumModerators $forum_moderators)
            {
            }

            private function initRecords(): void
            {
                if ($this->records === null) {
                    $this->records = [];
                    $i = 0;
                    $entries = $this->forum_moderators->getCurrentModerators();
                    $num = count($entries);
                    foreach ($entries as $usr_id) {
                        /** @var ilObjUser $user */
                        $user = ilObjectFactory::getInstanceByObjId($usr_id, false);
                        if (!($user instanceof ilObjUser)) {
                            $this->forum_moderators->detachModeratorRole($usr_id);
                            continue;
                        }

                        $this->records[$i]['usr_id'] = $user->getId();
                        $this->records[$i]['login'] = $user->getLogin();
                        $this->records[$i]['firstname'] = $user->getFirstname();
                        $this->records[$i]['lastname'] = $user->getLastname();
                        ++$i;
                    }
                }
            }

            public function getRows(
                UI\Component\Table\DataRowBuilder $row_builder,
                array $visible_column_ids,
                Data\Range $range,
                Data\Order $order,
                ?array $filter_data,
                ?array $additional_parameters
            ): \Generator {
                $records = $this->getRecords($range, $order);

                foreach ($records as $record) {
                    $row_id = (string) $record['usr_id'];
                    yield $row_builder->buildDataRow($row_id, $record);
                }
            }

            public function getTotalRowCount(
                ?array $filter_data,
                ?array $additional_parameters
            ): ?int {
                $this->initRecords();
                return count((array) $this->records);
            }

            private function getRecords(Data\Range $range, Data\Order $order): array
            {
                $this->initRecords();
                $records = $this->records;

                [$order_field, $order_direction] = $order->join([], fn ($ret, $key, $value) => [$key, $value]);
                usort($records, static function (array $left, array $right) use ($order_field): int {
                    return $left[$order_field] <=> $right[$order_field];
                });

                if ($order_direction === "DESC") {
                    $records = array_reverse($records);
                }
                return $this->limitRecords($records, $range);
            }

            private function limitRecords(array $records, Data\Range $range): array
            {
                return array_slice($records, $range->getStart(), $range->getLength());
            }
        };

        return $data_retrieval;
    }

}
