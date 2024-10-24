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
 * @author  Niels Theen <ntheen@databay.de>
 */
class ilCertificateScormTemplateDeleteAction implements ilCertificateDeleteAction
{
    private readonly ilSetting $setting;

    public function __construct(
        private readonly ilCertificateTemplateDeleteAction $deleteAction,
        ?ilSetting $setting = null
    ) {
        if (null === $setting) {
            $setting = new ilSetting('scorm');
        }
        $this->setting = $setting;
    }

    public function delete(int $templateId, int $objectId): void
    {
        $this->deleteAction->delete($templateId, $objectId);

        $this->setting->delete('certificate_' . $objectId);
    }
}
