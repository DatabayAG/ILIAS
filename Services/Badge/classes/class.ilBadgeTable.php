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
use ilBadge;
use ilBadgeAuto;

class ilBadgeTable
{
    private Factory $factory;
    private Renderer $renderer;
    private \ILIAS\Refinery\Factory $refinery;
    private ServerRequestInterface|RequestInterface $request;
    private Services $http;
    private int $parent_id;
    private string $parent_type;
    protected ilLanguage $lng;
    protected ilGlobalTemplateInterface $tpl;
    public function __construct(int $parent_obj_id, string $parent_obj_type) {
        global $DIC;
        $this->lng = $DIC->language();
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->factory = $DIC->ui()->factory();
        $this->renderer = $DIC->ui()->renderer();
        $this->refinery = $DIC->refinery();
        $this->request = $DIC->http()->request();
        $this->http = $DIC->http();

        $this->parent_id = $parent_obj_id;
        $this->parent_type = $parent_obj_type;
    }

    /**
     * @param Factory  $f
     * @param Renderer $r
     * @return DataRetrieval|__anonymous@1221
     */
    protected function buildDataRetrievalObject(Factory $f, Renderer $r, int $p, string $type)
    {
        return new class ($f, $r, $p, $type) implements DataRetrieval {
            protected $parent_obj_id;
            protected $parent_obj_type;
            public function __construct(
                protected Factory $ui_factory,
                protected Renderer $ui_renderer,
                protected int $parent_id,
                protected string $parent_type
            ) {
                global $DIC;
                $this->parent_obj_id = $parent_id;
                $this->parent_obj_type = $parent_type;
                $this->badge_image_service = new ilBadgeImage($DIC->resourceStorage(), $DIC->upload(), $DIC->ui()->mainTemplate());
            }

            /**
             * @param Container $DIC
             * @param array $data
             * @return array
             */
            protected function getBadges(Container $DIC, array $data) : array
            {
                if (true) {
                    $data = array();

                    foreach (ilBadge::getInstancesByParentId($this->parent_id, null) as $badge) {
                        $badge_rid = $badge->getImageRid();
                        $image_src = $this->badge_image_service->getImageFromResourceId($badge, $badge_rid);
                        if($badge_rid != '') {
                            $badge_template_image = $image_src;
                            if($badge_template_image !== '') {
                                $badge_img = $DIC->ui()->factory()->image()->responsive(
                                    $badge_template_image,
                                    $badge->getTitle()
                                );
                                $image_html = $DIC->ui()->renderer()->render($badge_img);
                            }
                        }

                        $badge_active = $badge->isActive() ? $DIC->language()->txt('yes') : $DIC->language()->txt('no');
                        $data[] = array(
                            "id" => $badge->getId(),
                            "badge" => $badge,
                            "title" => $badge->getTitle(),
                            "active" => $badge_active,
                            "type" => ($this->parent_type !== "bdga")
                                ? ilBadge::getExtendedTypeCaption($badge->getTypeInstance())
                                : $badge->getTypeInstance()->getCaption(),
                            "manual" => (!$badge->getTypeInstance() instanceof ilBadgeAuto),
                            "image_rid" => $image_html,
                            "renderer" => fn () => $this->tile->asTitle($this->tile->modalContent($badge)),
                        );
                    }
                    return $data;
                }
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
                    $row_id = (string) $record['id'];
                    yield $row_builder->buildDataRow($row_id, $record);
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

                $data = $this->getBadges($DIC, $data);

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
            'title' => $f->table()->column()->text($this->lng->txt("title")),
            'image_rid' => $f->table()->column()->text($this->lng->txt("image")),
            'type' => $f->table()->column()->text($this->lng->txt("type")),
            'active' => $f->table()->column()->text($this->lng->txt("active")),
        ];

        $table_uri = $df->uri($request->getUri()->__toString());
        $url_builder = new URLBuilder($table_uri);
        $query_params_namespace = ['tid'];

        list($url_builder, $action_parameter_token, $row_id_token) =
            $url_builder->acquireParameters(
                $query_params_namespace,
                "table_action",
                "id"
            );

        $actions = $this->getActions($url_builder, $action_parameter_token, $row_id_token);

        $data_retrieval = $this->buildDataRetrievalObject($f, $r, $this->parent_id, $this->parent_type);

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