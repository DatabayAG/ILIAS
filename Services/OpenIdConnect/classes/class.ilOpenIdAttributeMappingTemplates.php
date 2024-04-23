<?php

declare(strict_types=1);

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


class ilOpenIdAttributeMappingTemplate
{
    public const OPEN_ID_TEMPLATES = [
        "template_1" => 'template_1',
        "template_2" => 'template_2',
        "template_3" => 'template_3'
    ];
    /**
     * @param string $a_class
     * @return array<string, string>
     */
    public static function _getMappingRulesByClass(string $a_class): array
    {
        $mapping_rule = [];

        switch ($a_class) {
            case 'template_1':
                $mapping_rule['firstname'] = 'givenName';
                $mapping_rule['institution'] = 'o';
                $mapping_rule['department'] = 'departmentNumber';
                $mapping_rule['phone_home'] = 'homePhone';
                $mapping_rule['phone_mobile'] = 'mobile';
                $mapping_rule['email'] = 'mail';
                $mapping_rule['photo'] = 'jpegPhoto';
                // no break since it inherits from organizationalPerson and person

            case 'template_2':
                $mapping_rule['fax'] = 'facsimileTelephoneNumber';
                $mapping_rule['title'] = 'title';
                $mapping_rule['street'] = 'street';
                $mapping_rule['zipcode'] = 'postalCode';
                $mapping_rule['city'] = 'l';
                $mapping_rule['country'] = 'st';
                // no break since it inherits from person

            case 'template_3':
                $mapping_rule['lastname'] = 'sn';
                $mapping_rule['phone_office'] = 'telephoneNumber';
                break;
        }

        return $mapping_rule;
    }
}
