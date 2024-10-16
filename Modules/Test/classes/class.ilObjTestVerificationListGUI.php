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

/**
 * Class ilObjTestVerificationListGUI
 * @author Jörg Lützenkirchen <luetzenkirchen@leifos.com>
 */
class ilObjTestVerificationListGUI extends ilObjectListGUI
{
    public function init(): void
    {
        $this->delete_enabled = true;
        $this->cut_enabled = true;
        $this->copy_enabled = true;
        $this->subscribe_enabled = false;
        $this->link_enabled = false;
        $this->info_screen_enabled = false;
        $this->type = 'tstv';
        $this->gui_class_name = ilObjTestVerificationGUI::class;

        $this->commands = ilObjTestVerificationAccess::_getCommands();
    }

    public function getProperties(): array
    {
        global $DIC;
        $lng = $DIC['lng'];

        return [
            [
                'alert' => false,
                'property' => $lng->txt('type'),
                'value' => $lng->txt('wsp_list_tstv')
            ]
        ];
    }
}
