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
use ilBadge;
use ilBadgeHandler;
use ILIAS\Data\Link;
use ILIAS\Data\URI;
use ILIAS\UI\Implementation\Component\Link\Standard;
use ILIAS\StaticURL\Init;
use ilObject;
use ilLink;

/*
 * @ilCtrl_IsCalledBy ilObjBadgeAdministration: ilObjectBadgeTable
 */

class ilObjectBadgeTableGUI
{
    private Factory $factory;
    private Renderer $renderer;
    private \ILIAS\Refinery\Factory $refinery;
    private ServerRequestInterface|RequestInterface $request;
    private Services $http;
    protected ilLanguage $lng;
    protected ilGlobalTemplateInterface $tpl;
    public function __construct($parentObj) {
        global $DIC;
        $this->lng = $DIC->language();
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->factory = $DIC->ui()->factory();
        $this->renderer = $DIC->ui()->renderer();
        $this->refinery = $DIC->refinery();
        $this->request = $DIC->http()->request();
        $this->http = $DIC->http();
        $this->parent_obj = $parentObj;
    }

    protected function buildDataRetrievalObject(Factory $f, Renderer $r, $p) : DataRetrieval
    {
        return new class ($f, $r, $p) implements DataRetrieval {
            private ilBadgeImage $badge_image_service;
            private Factory $factory;
            private Renderer $renderer;
            private \ilCtrlInterface $ctrl;
            private ilLanguage $lng;
            private \ilAccessHandler $access;
            private ?bool $user_has_write_permission = null;

            public function __construct(
                protected Factory $ui_factory,
                protected Renderer $ui_renderer,
                protected $parent
            ) {
                global $DIC;
                $this->badge_image_service = new ilBadgeImage($DIC->resourceStorage(), $DIC->upload(), $DIC->ui()->mainTemplate());
                $this->factory = $this->ui_factory;
                $this->renderer = $this->ui_renderer;
                $this->ctrl = $DIC->ctrl();
                $this->lng = $DIC->language();
                $this->access = $DIC->access();
            }

            protected function userHasWritePermission(int $parent_id) : bool
            {
                if($this->user_has_write_permission === null) {
                    $parent_ref_id = ilObject::_getAllReferences($parent_id);
                    if (count($parent_ref_id) > 0) {
                        $parent_ref_id = array_pop($parent_ref_id);
                    }
                    $this->user_has_write_permission = $this->access->checkAccess("write", "", $parent_ref_id);

                }
                return $this->user_has_write_permission;
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
                $data = [];
                $image_html = '';
                $types = ilBadgeHandler::getInstance()->getAvailableTypes(false);
                $filter = ['type' => '' , 'title' => '', 'object' => ''];
                foreach (ilBadge::getObjectInstances($filter) as $badge_item) {
                    $type_caption = ilBadge::getExtendedTypeCaption($types[$badge_item['type_id']]);
                    $badge_rid = $badge_item['image_rid'];
                    $image_src = $this->badge_image_service->getImageFromResourceId($badge_item, $badge_rid);
                    if($badge_rid != '') {
                        $badge_template_image = $image_src;
                        if($badge_template_image !== '') {
                            $badge_img = $this->factory->image()->responsive(
                                $badge_template_image,
                                $badge_item['title']
                            );
                            $image_html = $this->renderer->render($badge_img);
                        }
                    }
                    $url = '';
                    $ref_ids = ilObject::_getAllReferences($badge_item['parent_id']);
                    $ref_id = array_shift($ref_ids);
                    $this->userHasWritePermission($badge_item['parent_id']);

                    $user_url_link = '';
                    if ($this->user_has_write_permission) {
                        $this->ctrl->setParameter($this->parent, 'pid', (string) $badge_item['parent_id']);
                        $this->ctrl->setParameter($this->parent, 'bid', (string) $badge_item['id']);
                        $url = ILIAS_HTTP_PATH . '/' . $this->ctrl->getLinkTarget($this->parent, 'listObjectBadgeUsers');
                        $this->ctrl->setParameter($this->parent, 'bid', '');
                        $this->ctrl->setParameter($this->parent, 'pid', '');
                        $user_url_link = new Standard($this->lng->txt('user'), new URI($url));
                    }

                    $container_url_link = '';
                    if ($this->access->checkAccess('read', '', $ref_id)) {
                        $container_url = ilLink::_getLink($ref_id);
                        $container_url_link = new Standard($badge_item['parent_title'], new URI($container_url));
                    }
                    $data[] = [
                        'id' => (int) $badge_item['id'],
                        'active' => $badge_item['active'] ? true : false,
                        'type' => $type_caption,
                        'title' => $badge_item['title'],
                        'image_rid' => $image_html,
                        'container' => $badge_item['parent_title'],
                        'container_url' => $container_url_link ?: '',
                        'container_deleted' => ($badge_item['deleted'] ?? false),
                        'container_id' => (int) $badge_item['parent_id'],
                        'container_type' => $badge_item['parent_type'],
                        'user' => $user_url_link ?: ''
                    ];
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
            'obj_badge_activate' => $f->table()->action()->multi(
                $this->lng->txt("activate"),
                $url_builder->withParameter($action_parameter_token, "obj_badge_activate"),
                $row_id_token
            ),
            'obj_badge_deactivate' =>
                $f->table()->action()->multi(
                    $this->lng->txt("deactivate"),
                    $url_builder->withParameter($action_parameter_token, "obj_badge_deactivate"),
                    $row_id_token
                ),
            'obj_badge_delete' =>
                $f->table()->action()->multi(
                    $this->lng->txt("delete"),
                    $url_builder->withParameter($action_parameter_token, "obj_badge_delete"),
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
            'container_url' => $f->table()->column()->link($this->lng->txt("container")),
            'active' => $f->table()->column()->boolean($this->lng->txt("active"), $this->lng->txt("yes"), $this->lng->txt("no")),
            'user' => $f->table()->column()->link($this->lng->txt("user")),
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

        $data_retrieval = $this->buildDataRetrievalObject($f, $r, $this->parent_obj);

        $actions = $this->getActions($url_builder, $action_parameter_token, $row_id_token);

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

            if ($action === 'obj_badge_delete') {
                $items = [];
                foreach ($ids as $id) {
                    $badge = new ilBadge($id);
                    $items[] = $f->modal()->interruptiveItem()->keyValue($id, $badge->getId(), $badge->getTitle());
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