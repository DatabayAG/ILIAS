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

namespace ILIAS\Refinery\To\Transformation;

use ILIAS\Refinery\DeriveApplyToFromTransform;
use ILIAS\Refinery\DeriveInvokeFromTransform;
use ILIAS\Refinery\Constraint;
use ILIAS\Refinery\ProblemBuilder;
use UnexpectedValueException;

class StringTransformation implements Constraint
{
    use DeriveApplyToFromTransform;
    use DeriveInvokeFromTransform;
    use ProblemBuilder;

    /**
     * @inheritDoc
     */
    public function transform($from): string
    {
        $this->check($from);
        return (string) $from;
    }

    /**
     * @inheritDoc
     */
    public function getError(): string
    {
        return 'The value MUST be of type string.';
    }

    /**
     * @inheritDoc
     */
    public function check($value)
    {
        if (!$this->accepts($value)) {
            throw new UnexpectedValueException($this->getErrorMessage($value));
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function accepts($value): bool
    {
        return is_string($value);
    }

    /**
     * @inheritDoc
     */
    public function problemWith($value): ?string
    {
        if (!$this->accepts($value)) {
            return $this->getErrorMessage($value);
        }

        return null;
    }
}
