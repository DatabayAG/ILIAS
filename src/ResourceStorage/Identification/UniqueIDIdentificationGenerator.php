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

namespace ILIAS\ResourceStorage\Identification;

/**
 * Class UniqueIDIdentificationGenerator
 * @author Fabian Schmid <fabian@sr.solutions.ch>
 * @internal
 */
class UniqueIDIdentificationGenerator implements IdentificationGenerator
{
    use UUIDStringTrait;

    /**
     * @throws \Exception
     */
    public function getUniqueResourceIdentification(): ResourceIdentification
    {
        $unique_id = null;
        try {
            $unique_id = $this->factory->uuid4AsString();
        } catch (\Exception $e) {
            throw new \LogicException('Generating uuid failed: ' . $e->getMessage(), $e->getCode(), $e);
        } finally {
            return new ResourceIdentification($unique_id);
        }
    }
}
