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

use ILIAS\UI\Component\Table\DataRetrieval;
use ILIAS\UI\Component\Table\DataRowBuilder;
use ILIAS\Data\Range;
use ILIAS\Data\Order;
use ILIAS\UI\Factory as UIFactory;
use ILIAS\UI\Renderer as UIRenderer;

class ilDidacticTemplateSettingsTableDataRetrieval implements DataRetrieval
{
    protected ilDidacticTemplateSettingsTableFilter $filter;
    protected ilLanguage $lng;
    protected UIFactory $ui_factory;
    protected UIRenderer $ui_renderer;

    public function __construct(
        ilDidacticTemplateSettingsTableFilter $filter,
        ilLanguage $lng,
        UIFactory $ui_factory,
        UIRenderer $ui_renderer,
    ) {
        $this->filter = $filter;
        $this->lng = $lng;
        $this->ui_factory = $ui_factory;
        $this->ui_renderer = $ui_renderer;
    }

    public function getRows(
        DataRowBuilder $row_builder,
        array $visible_column_ids,
        Range $range,
        Order $order,
        ?array $filter_data,
        ?array $additional_parameters
    ): Generator {
        $records = $this->getRecords($order, $range);
        foreach ($records as $record) {
            yield $row_builder->buildDataRow((string) $record['template_id'], $record);
        }
    }

    public function getTotalRowCount(
        ?array $filter_data,
        ?array $additional_parameters
    ): ?int {
        return count($this->getTemplates());
    }

    /**
     * @return ilDidacticTemplateSetting[]
     */
    protected function getTemplates(): array
    {
        $tpls = ilDidacticTemplateSettings::getInstance();
        $tpls->readInactive();
        return $this->filter->filter($tpls->getTemplates());
    }

    protected function getRecords(Order $order, Range $range): array
    {
        $records = [];
        foreach ($this->getTemplates() as $tpl) {
            /* @var $tpl ilDidacticTemplateSetting */
            $atxt = '';
            foreach ($tpl->getAssignments() as $obj_type) {
                $atxt .= ($this->lng->txt('objs_' . $obj_type) . '<br/>');
            }
            $title_desc = $tpl->getPresentationTitle()
                . "<br><br>"
                . $tpl->getPresentationDescription()
                . (trim($tpl->getInfo()) ? "<br><br>" . $tpl->getInfo() : '')
                . ($tpl->isAutoGenerated() ? "<br><br>" . $this->lng->txt("didactic_auto_generated") : '');

            $scope_str = '';
            if (count($tpl->getEffectiveFrom()) > 0) {
                $scope_str .= $this->lng->txt('didactic_scope_list_header');
                foreach ($tpl->getEffectiveFrom() as $ref_id) {
                    $link = $this->ui_renderer->render($this->ui_factory->link()->standard(
                        ilObject::_lookupTitle(ilObject::_lookupObjId($ref_id)),
                        ilLink::_getLink($ref_id)
                    ));
                    $scope_str .= "<br>";
                    $scope_str .= $link;
                }
            } else {
                $scope_str .= (isset($a_set['local']) ? $this->lng->txt('meta_local') : $this->lng->txt('meta_global'));
            }
            $scope_str .= "<br>";

            $icon_label = '';
            foreach ($tpl->getAssignments() as $obj_type) {
                $icon_label = $this->lng->txt('objs_' . $obj_type);
            }
            $icon = $this->ui_renderer->render(
                $this->ui_factory->symbol()->icon()->custom(
                    $tpl->getIconHandler()->getAbsolutePath(),
                    $icon_label
                )
            );

            $icon_active = $this->ui_renderer->render($this->ui_factory->symbol()->icon()->custom(
                $tpl->isEnabled() ? ilUtil::getImagePath('icon_ok.svg') : ilUtil::getImagePath('icon_not_ok.svg'),
                $tpl->isEnabled() ? $this->lng->txt('active') : $this->lng->txt('inactive')
            ));

            $records[] = [
                'template_id' => $tpl->getId(),
                'icon' => $icon,
                'title' => $title_desc,
                'applicable' => $atxt,
                'scope' => $scope_str,
                'enabled' => $icon_active
            ];
        }
        list($order_field, $order_direction) = $order->join([], fn($ret, $key, $value) => [$key, $value]);
        usort($records, fn($a, $b) => $a[$order_field] <=> $b[$order_field]);
        if (
            $order_direction === 'DESC'
        ) {
            $records = array_reverse($records);
        }
        $selected_records = array_slice(
            $records,
            $range->getStart() * $range->getLength(),
            $range->getLength()
        );
        return $selected_records;
    }
}
