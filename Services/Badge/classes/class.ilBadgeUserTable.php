<?php
namespace ILIAS\Badge;

use ILIAS\UI\Factory;
use ILIAS\UI\URLBuilder;
use ILIAS\Data\Order;
use ILIAS\Data\Range;
use ilBadgeImageTemplate;
use ilLanguage;
use ilGlobalTemplateInterface;
use ILIAS\UI\Renderer;
use Psr\Http\Message\ServerRequestInterface;
use ILIAS\HTTP\Services;
use Psr\Http\Message\RequestInterface;
use ILIAS\UI\Component\Table\DataRowBuilder;
use Generator;
use ILIAS\UI\Component\Table\DataRetrieval;
use ILIAS\UI\URLBuilderToken;
use ILIAS\DI\Container;
use ilBadgeHandler;
use ilBadgeAuto;
use ilObject;
use ilBadge;
use ilBadgeAssignment;
use ilUserQuery;
use DateTimeImmutable;

class ilBadgeUserTable
{
    private Factory $factory;
    private Renderer $renderer;
    private \ILIAS\Refinery\Factory $refinery;
    private ServerRequestInterface|RequestInterface $request;
    private Services $http;
    private int $parent_ref_id;
    private ?ilBadge $award_badge;
    protected ilLanguage $lng;
    protected ilGlobalTemplateInterface $tpl;
    public function __construct(int $parent_ref_id, ?ilBadge $award_badge = null) {
        global $DIC;
        $this->lng = $DIC->language();
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->factory = $DIC->ui()->factory();
        $this->renderer = $DIC->ui()->renderer();
        $this->refinery = $DIC->refinery();
        $this->request = $DIC->http()->request();
        $this->http = $DIC->http();
        $this->parent_ref_id = $parent_ref_id;
        $this->award_badge = $award_badge;
    }

    /**
     * @param Factory  $f
     * @param Renderer $r
     * @return DataRetrieval|__anonymous@1221
     */
    protected function buildDataRetrievalObject(Factory $f, Renderer $r, int $parent_ref_id, ?ilBadge $award_badge = null)
    {
        return new class ($f, $r, $parent_ref_id, $award_badge) implements DataRetrieval {
            public function __construct(
                protected Factory $ui_factory,
                protected Renderer $ui_renderer,
                protected int $parent_ref_id,
                protected ?ilBadge $award_badge = null
            ) {
            }

            /**
             * @param Container $DIC
             * @param array $data
             * @return array
             */
            protected function getBadgeImageTemplates(Container $DIC, array $data) : array
            {
                $a_parent_obj_id = null;
                $parent_ref_id = $this->parent_ref_id;
                $a_restrict_badge_id = 0;

                global $DIC;
                $data = [];
                $tree = $DIC->repositoryTree();
                $user_ids = null;

                if (!$a_parent_obj_id) {
                    $a_parent_obj_id = ilObject::_lookupObjId($parent_ref_id);
                }

                if ($parent_ref_id) {
                    $user_ids = ilBadgeHandler::getInstance()->getUserIds($parent_ref_id, $a_parent_obj_id);
                }

                $obj_ids = [$a_parent_obj_id];
                foreach ($tree->getSubTree($tree->getNodeData($parent_ref_id)) as $node) {
                    $obj_ids[] = $node["obj_id"];
                }
                $badges = [];
                foreach ($obj_ids as $obj_id) {
                    foreach (ilBadge::getInstancesByParentId($obj_id) as $badge) {
                        $badges[$badge->getId()] = $badge;
                    }

                    foreach (ilBadgeAssignment::getInstancesByParentId($obj_id) as $ass) {
                        if ($a_restrict_badge_id &&
                            $a_restrict_badge_id !== $ass->getBadgeId()) {
                            continue;
                        }

                        $assignments[$ass->getUserId()][] = $ass;
                    }
                }

                if (!$user_ids) {
                    $user_ids = array_keys($assignments);
                }

                $tmp['set'] = [];
                if (count($user_ids) > 0) {
                    $uquery = new ilUserQuery();
                    $uquery->setLimit(9999);
                    $uquery->setUserFilter($user_ids);
                    $tmp = $uquery->query();
                }
                foreach ($tmp["set"] as $user) {
                    if (array_key_exists($user["usr_id"], $assignments)) {
                        foreach ($assignments[$user["usr_id"]] as $user_ass) {
                            $idx = $user_ass->getBadgeId() . "-" . $user["usr_id"];
                            $badge = $badges[$user_ass->getBadgeId()];
                            $parent = $badge->getParentMeta();
                            $timestamp = $user_ass->getTimestamp();
                            $immutable = new DateTimeImmutable();
                            $user_id = $user['usr_id'];
                            $name = $user['lastname'] . ', ' . $user['firstname'];
                            $login = $user['login'];
                            $type = ilBadge::getExtendedTypeCaption($badge->getTypeInstance());
                            $title = $badge->getTitle();
                            $issued = $immutable->setTimestamp($timestamp);
                            $parent_id =  $parent["id"] ?? 0;
                            $data[$idx] = [
                                "user_id" => $user_id,
                                "name" => $name,
                                "login" => $login,
                                "type" => $type,
                                "title" => $title,
                                "issued" => $issued,
                                "parent_id" => $parent_id,
                                "parent_meta" => $parent
                            ];
                        }
                    }
                    elseif ($this->award_badge) {
                        $idx = "0-" . $user["usr_id"];
                        $user_id = $user['usr_id'];
                        $name = $user['lastname'] . ', ' . $user['firstname'];
                        $login = $user['login'];
                        $data[$idx] = [
                            "user_id" => $user_id,
                            "name" => $name,
                            "login" => $login,
                            "type" => "",
                            "title" => "",
                            "issued" => "",
                            "parent_id" => ""
                        ];
                    }
                }

               return $data;
            }

            public function getRows(
                DataRowBuilder $row_builder,
                array $visible_column_ids,
                Range $range,
                Order $order,
                ?array $filter_data,
                ?array $additional_parameters
            ) : Generator {
                $records = $this->getRecords($range, $order);
                foreach ($records as $idx => $record) {
                    if(isset($idx)) {
                        $row_id = (string) $idx;
                        yield $row_builder->buildDataRow($row_id, $record);
                    }
                }
            }

            public function getTotalRowCount(
                ?array $filter_data,
                ?array $additional_parameters
            ) : ?int {
                return count($this->getRecords());
            }

            protected function getRecords(Range $range = null, Order $order = null) : array
            {

                global $DIC;
                $data = array();

                $data = $this->getBadgeImageTemplates($DIC, $data);

                if ($order) {
                    list($order_field, $order_direction) = $order->join([],
                        fn($ret, $key, $value) => [$key, $value]);
                    usort($data, fn($a, $b) => $a[$order_field] <=> $b[$order_field]);
                    if ($order_direction === 'DESC') {
                        $data = array_reverse($data);
                    }
                }
                if ($range) {
                    $data = array_slice($data, $range->getStart(), $range->getLength());
                }

                return $data;
            }
        };
    }

    /**
     * @param URLBuilder      $url_builder
     * @param URLBuilderToken $action_parameter_token
     * @param URLBuilderToken $row_id_token
     * @return array
     */
    protected function getActions(
        URLBuilder $url_builder,
        URLBuilderToken $action_parameter_token,
        URLBuilderToken  $row_id_token
    ) : array {
        $f = $this->factory;
        return [
            'edit' => $f->table()->action()->single( //never in multi actions
                $this->lng->txt("edit"),
                $url_builder->withParameter($action_parameter_token, "editImageTemplate"),
                $row_id_token
            ),
            'info' =>
                $f->table()->action()->standard( //in both
                    $this->lng->txt("info"),
                    $url_builder->withParameter($action_parameter_token, "info"),
                    $row_id_token
                )
                  ->withAsync()
            ,
            'delete' =>
                $f->table()->action()->standard( //in both
                    $this->lng->txt("delete"),
                    $url_builder->withParameter($action_parameter_token, "delete"),
                    $row_id_token
                )
                  ->withAsync()
        ];
    }

    public function renderTable() : void
    {
        $f = $this->factory;
        $r = $this->renderer;
        $refinery = $this->refinery;
        $request = $this->request;
        $df = new \ILIAS\Data\Factory();

        $columns = [
            'name' => $f->table()->column()->text($this->lng->txt("name")),
            'login' => $f->table()->column()->text($this->lng->txt("login")),
            'type' => $f->table()->column()->text($this->lng->txt("type")),
            'title' => $f->table()->column()->text($this->lng->txt("title")),
            'issued' => $f->table()->column()->date($this->lng->txt("badge_issued_on"), $df->dateFormat()->germanShort())
        ];

        $table_uri = $df->uri($request->getUri()->__toString());
        $url_builder = new URLBuilder($table_uri);
        $query_params_namespace = ['tid'];

        list($url_builder, $action_parameter_token, $row_id_token) =
            $url_builder->acquireParameters(
                $query_params_namespace,
                "table_action",
                "id",
            );

        $actions = $this->getActions($url_builder, $action_parameter_token, $row_id_token);

        $data_retrieval = $this->buildDataRetrievalObject($f, $r, $this->parent_ref_id, $this->award_badge);

        $table = $f->table()
                   ->data('', $columns, $data_retrieval)
                   ->withActions($actions)
                   ->withRequest($request);

        $out = [$table];

        $query = $this->http->wrapper()->query();
        if ($query->has($action_parameter_token->getName())) {
            $action = $query->retrieve($action_parameter_token->getName(), $refinery->to()->string());
            $ids = $query->retrieve($row_id_token->getName(), $refinery->custom()->transformation(fn($v) => $v));
            $listing = $f->listing()->characteristicValue()->text([
                'table_action' => $action,
                'id' => print_r($ids, true),
            ]);

            if ($action === 'delete') {
                $items = [];
                foreach ($ids as $id) {
                    $items[] = $f->modal()->interruptiveItem()->keyValue($id, $row_id_token->getName(), $id);
                }
                echo($r->renderAsync([
                    $f->modal()->interruptive(
                        'Deletion',
                        'You are about to delete items!',
                        '#'
                    )->withAffectedItems($items)
                      ->withAdditionalOnLoadCode(static fn($id) : string => "console.log('ASYNC JS');")
                ]));
                exit();
            }
            if ($action === 'info') {
                echo(
                    $r->render($f->messageBox()->info('an info message: <br><li>' . implode('<li>', $ids)))
                    . '<script data-replace-marker="script">console.log("ASYNC JS, too");</script>'
                );

            }

            $out[] = $f->divider()->horizontal();
            $out[] = $listing;
        }

        $this->tpl->setContent($r->render($out));
    }
}