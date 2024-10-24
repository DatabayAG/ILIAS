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

use ILIAS\Modules\OrgUnit\ARHelper\DropdownBuilder;

class ilOrgUnitTypeTableGUI extends ilTable2GUI
{
    private ilTabsGUI $tabs;
    private array $columns = [
        'title',
        'description',
        'default_language',
        'icon',
    ];
    protected DropdownBuilder $dropdownbuilder;

    public function __construct(ilOrgUnitTypeGUI $parent_obj, string $parent_cmd)
    {
        $dic = ilOrgUnitLocalDIC::dic();
        $this->ctrl = $dic['ctrl'];
        $this->tabs = $dic['tabs'];
        $this->lng = $dic['lng'];
        $this->dropdownbuilder = $dic['dropdownbuilder'];

        $this->setPrefix('orgu_types_table');
        $this->setId('orgu_types_table');
        parent::__construct($parent_obj, $parent_cmd);
        $this->setRowTemplate('tpl.types_row.html', 'Modules/OrgUnit');
        $this->initColumns();
        $this->addColumn($this->lng->txt('action'));
        $this->buildData();
        $this->setFormAction($this->ctrl->getFormAction($this->parent_obj));
    }

    /**
     * Pass data to row template
     */
    public function fillRow(array $a_set): void
    {
        $this->tpl->setVariable('TITLE', $a_set['title']);
        $this->tpl->setVariable('DESCRIPTION', $a_set['description']);
        $this->tpl->setVariable('DEFAULT_LANG', $a_set['default_language']);
        $this->tpl->setVariable('ICON', $a_set['icon']);
        $this->ctrl->setParameterByClass("ilorgunittypegui", "type_id", $a_set['id']);
        $dropdownbuilder = $this->dropdownbuilder
            ->withItem(
                'edit',
                $this->ctrl->getLinkTargetByClass('ilorgunittypegui', 'edit')
            )
            ->withItem(
                'delete',
                $this->ctrl->getLinkTargetByClass('ilorgunittypegui', 'delete')
            )
            ->get();
        $this->tpl->setVariable('ACTIONS', $dropdownbuilder);
    }

    protected function initColumns(): void
    {
        foreach ($this->columns as $column) {
            $this->addColumn($this->lng->txt($column), $column);
        }
    }

    protected function buildData(): void
    {
        $types = ilOrgUnitType::getAllTypes();
        $data = array();
        /** @var $type ilOrgUnitType */
        foreach ($types as $type) {
            $row = array();
            $row['id'] = $type->getId();
            $row['title'] = $type->getTitle($type->getDefaultLang());
            $row['default_language'] = $type->getDefaultLang();
            $row['description'] = $type->getDescription($type->getDefaultLang());
            $row['icon'] = $type->getIcon();
            $data[] = $row;
        }
        $this->setData($data);
    }
}
