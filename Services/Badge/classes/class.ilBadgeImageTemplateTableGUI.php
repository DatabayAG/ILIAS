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

use ILIAS\ResourceStorage\Services;

/**
 * TableGUI class for badge template listing
 * @author Jörg Lützenkirchen <luetzenkirchen@leifos.com>
 */
class ilBadgeImageTemplateTableGUI extends ilTable2GUI
{

    protected Services $resource_storage;

    public function __construct(
        object $a_parent_obj,
        string $a_parent_cmd = "",
        protected bool $has_write = false
    ) {
        global $DIC;
        $this->resource_storage = $DIC->resourceStorage();
        $this->ctrl = $DIC->ctrl();
        $this->lng = $DIC->language();
        $ilCtrl = $DIC->ctrl();
        $lng = $DIC->language();

        $this->setId("bdgtmpl");

        parent::__construct($a_parent_obj, $a_parent_cmd);

        $this->setLimit(9999);

        $this->setTitle($lng->txt("badge_image_templates"));

        if ($this->has_write) {
            $this->addColumn("", "", 1);
        }

        $this->addColumn($lng->txt("title"), "title");
        $this->addColumn($lng->txt("image"), "image");

        if ($this->has_write) {
            $this->addColumn($lng->txt("action"), "");
            $this->addMultiCommand("confirmDeleteImageTemplates", $lng->txt("delete"));
        }

        $this->setSelectAllCheckbox("id");

        $this->setFormAction($ilCtrl->getFormAction($a_parent_obj));
        $this->setRowTemplate("tpl.template_row.html", "Services/Badge");
        $this->setDefaultOrderField("title");
        $this->setExternalSorting(true);

        $this->getItems();
    }

    public function getItems(): void
    {
        $data = array();

        foreach (ilBadgeImageTemplate::getInstances() as $template) {

            if($template->getId() !== null) {
                $data[] = $template;
            }
        }

        $this->setData($data);
    }

    protected function fillRow($badge_image_template): void
    {
        $lng = $this->lng;
        $ilCtrl = $this->ctrl;
        /**
         * @var ilBadgeImageTemplate $badge_image_template
         */
        if ($this->has_write) {
            $this->tpl->setVariable("VAL_ID", $badge_image_template->getId());
        }

        $this->tpl->setVariable("TXT_TITLE", $badge_image_template->getTitle());
        if($badge_image_template->getImageRid()) {
            $this->tpl->setVariable("TXT_IMG", $badge_image_template->getImageRid());
            $img = $badge_image_template->getImageFromResourceId($badge_image_template->getImageRid());
            $this->tpl->setVariable("VAL_IMG", $img);
        } else {
            $this->tpl->setVariable("VAL_IMG", ilWACSignedPath::signFile($badge_image_template->getImagePath()));
            $this->tpl->setVariable("TXT_IMG", $badge_image_template->getImage());
        }


        if ($this->has_write) {
            $ilCtrl->setParameter($this->getParentObject(), "tid", $badge_image_template->getId());
            $url = $ilCtrl->getLinkTarget($this->getParentObject(), "editImageTemplate");
            $ilCtrl->setParameter($this->getParentObject(), "tid", "");

            $this->tpl->setVariable("TXT_EDIT", $lng->txt("edit"));
            $this->tpl->setVariable("URL_EDIT", $url);
        }
    }
}
