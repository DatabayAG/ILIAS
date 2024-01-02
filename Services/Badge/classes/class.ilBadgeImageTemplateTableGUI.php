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

        $this->getItems();
    }

    public function getItems(): void
    {
        $data = array();

        foreach (ilBadgeImageTemplate::getInstances() as $template) {
            $data[] = array(
                "id" => $template->getId(),
                "title" => $template->getTitle(),
                "path" => $template->getImagePath(),
                "file" => $template->getImage(),
                "file_rid" => $template->getImageRid()
            );
        }

        $this->setData($data);
    }

    protected function fillRow(array $a_set): void
    {
        $lng = $this->lng;
        $ilCtrl = $this->ctrl;

        if ($this->has_write) {
            $this->tpl->setVariable("VAL_ID", $a_set["id"]);
        }

        $this->tpl->setVariable("TXT_TITLE", $a_set["title"]);
        if($a_set['file_rid']) {
            $this->tpl->setVariable("TXT_IMG", $a_set["file_rid"]);
            $img = $this->getImageFromResourceId($a_set["file_rid"]);
            $this->tpl->setVariable("VAL_IMG", $img);
        } else {
            $this->tpl->setVariable("VAL_IMG", ilWACSignedPath::signFile($a_set["path"]));
            $this->tpl->setVariable("TXT_IMG", $a_set["file"]);
        }


        if ($this->has_write) {
            $ilCtrl->setParameter($this->getParentObject(), "tid", $a_set["id"]);
            $url = $ilCtrl->getLinkTarget($this->getParentObject(), "editImageTemplate");
            $ilCtrl->setParameter($this->getParentObject(), "tid", "");

            $this->tpl->setVariable("TXT_EDIT", $lng->txt("edit"));
            $this->tpl->setVariable("URL_EDIT", $url);
        }
    }

    public function getImageFromResourceId(?string $image_rid, int $badge_id = null) : string
    {
        $image_src = '';

        if ($image_rid !== null) {
            $identification = $this->resource_storage->manage()->find($image_rid);
            if ($identification !== null) {
                $image_src = $this->resource_storage->consume()->src($identification)->getSrc();
            }
        } else {
            if($badge_id !== null) {
                $badge = new ilBadge($badge_id);
                $image_src = $badge->getImage();
            }
        }

        return $image_src;
    }
}
