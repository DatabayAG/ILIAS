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
interface ilCertificatePlaceholderValues
{
    /**
     * This method MUST return an array that contains the
     * actual data for the given user of the given object.
     * ilInvalidCertificateException MUST be thrown if the
     * data could not be determined or the user did NOT
     * achieve the certificate.
     * @return array<string, string> - [PLACEHOLDER] => 'actual value'
     * @throws ilInvalidCertificateException
     */
    public function getPlaceholderValues(int $userId, int $objId): array;

    /**
     * This method is different then the 'getPlaceholderValues' method, this
     * method is used to create a placeholder value array containing dummy values
     * that is used to create a preview certificate.
     * @return array<string, string> - [PLACEHOLDER] => 'actual value'
     */
    public function getPlaceholderValuesForPreview(int $userId, int $objId): array;
}
