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

    use ILIAS\UI\Factory;
    use ILIAS\UI\URLBuilder;
    use ILIAS\Data\Order;
    use ILIAS\Data\Range;
    use ILIAS\UI\Renderer;
    use Psr\Http\Message\ServerRequestInterface;
    use ILIAS\HTTP\Services;
    use Psr\Http\Message\RequestInterface;
    use ILIAS\UI\Component\Table\DataRowBuilder;
    use ILIAS\UI\Component\Table\DataRetrieval;
    use ILIAS\UI\URLBuilderToken;
    use ILIAS\Data\URI;
    use ILIAS\UI\Implementation\Component\Link\Standard;
    use ILIAS\Badge\ilBadgeImage;
    use ILIAS\Badge\PresentationHeader;

/*
 * @ilCtrl_IsCalledBy ilObjBadgeAdministration: ilObjectBadgeTable
 */

class ilBadgePersonalTableGUI
{
    private Factory $factory;
    private Renderer $renderer;
    private \ILIAS\Refinery\Factory $refinery;
    private ServerRequestInterface|RequestInterface $request;
    private Services $http;
    protected ilLanguage $lng;
    protected ilGlobalTemplateInterface $tpl;
    public function __construct() {
        global $DIC;
        $this->lng = $DIC->language();
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->factory = $DIC->ui()->factory();
        $this->renderer = $DIC->ui()->renderer();
        $this->refinery = $DIC->refinery();
        $this->request = $DIC->http()->request();
        $this->http = $DIC->http();
    }

    protected function buildDataRetrievalObject(Factory $f, Renderer $r) : DataRetrieval
    {
        return new class ($f, $r) implements DataRetrieval {
            private ilBadgeImage $badge_image_service;
            private Factory $factory;
            private Renderer $renderer;
            private \ilCtrlInterface $ctrl;
            private ilLanguage $lng;

            private ILIAS\Data\Factory $data_factory;
            private \ilAccessHandler $access;
            private ?bool $user_has_write_permission = null;

            public function __construct(
                protected Factory $ui_factory,
                protected Renderer $ui_renderer
            ) {
                global $DIC;
                $this->badge_image_service = new ilBadgeImage($DIC->resourceStorage(), $DIC->upload(), $DIC->ui()->mainTemplate());
                $this->factory = $this->ui_factory;
                $this->renderer = $this->ui_renderer;
                $this->data_factory = new \ILIAS\Data\Factory();
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
                global $DIC;
                $data = [];
                $a_user_id = $DIC->user()->getId();
                $df = $this->data_factory->dateFormat();
                foreach (ilBadgeAssignment::getInstancesByUserId($a_user_id) as $ass) {
                    $image_html = '';
                    $badge = new ilBadge($ass->getBadgeId());
                    $image_src = $this->badge_image_service->getImageFromBadge($badge);
                    if($image_src != '') {
                        $badge_template_image = $image_src;
                        if($badge_template_image !== '') {
                            $badge_img = $this->factory->image()->responsive(
                                $badge_template_image,
                                $badge->getTitle()
                            );
                            $image_html = $this->renderer->render($badge_img);
                        }
                    }
                    $parent = null;
                    if ($badge->getParentId()) {
                        $parent = $badge->getParentMeta();
                        if ($parent["type"] === "bdga") {
                            $parent = null;
                        }
                    }
                    $user_url_link = '';
                    $data[] = [
                        'id' => $badge->getId(),
                        'image' => $image_html,
                        'user' => $user_url_link ?: '',
                        'badge_in_profile' => '',
                        "title" => $badge->getTitle(),
                        "badge_issued_on" =>  (new \DateTimeImmutable())->setTimestamp($ass->getTimestamp()),
                        "awarded_by" => $parent ? $parent["title"] : null,
                        "parent" => $parent,
                        "active" => (bool) $ass->getPosition()
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
            'obj_badge_activate' => $f->table()->action()->multi( //never in multi actions
                $this->lng->txt("activate"),
                $url_builder->withParameter($action_parameter_token, "obj_badge_activate"),
                $row_id_token
            ),
            'obj_badge_deactivate' =>
                $f->table()->action()->multi( //in both
                    $this->lng->txt("deactivate"),
                    $url_builder->withParameter($action_parameter_token, "obj_badge_deactivate"),
                    $row_id_token
                ),
            'obj_badge_delete' =>
                $f->table()->action()->multi( //in both
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
        $data_format = $df->dateFormat();
        $date_format = $data_format->withTime24($df->dateFormat()->germanShort());

        $columns = [
            'title' => $f->table()->column()->text($this->lng->txt("title")),
            'image' => $f->table()->column()->text($this->lng->txt("image")),
            'awarded_by' => $f->table()->column()->text($this->lng->txt("awarded_by")),
            'badge_issued_on' => $f->table()->column()->date($this->lng->txt("badge_issued_on"), $date_format),
            'active' => $f->table()->column()->boolean($this->lng->txt("active"), $this->lng->txt("yes"), $this->lng->txt("no")),
        ];

        $table_uri = $df->uri($request->getUri()->__toString());
        $url_builder = new URLBuilder($table_uri);
        $query_params_namespace = ['badge'];

        list($url_builder, $action_parameter_token, $row_id_token) =
            $url_builder->acquireParameters(
                $query_params_namespace,
                "table_action",
                "id"
            );

        $data_retrieval = $this->buildDataRetrievalObject($f, $r);

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
