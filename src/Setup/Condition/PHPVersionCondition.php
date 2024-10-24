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

namespace ILIAS\Setup\Condition;

use ILIAS\Setup;

class PHPVersionCondition extends ExternalConditionObjective
{
    public function __construct(string $which)
    {
        parent::__construct(
            "PHP version >= $which",
            static fn (Setup\Environment $env): bool => version_compare(PHP_VERSION, $which, ">="),
            "ILIAS " . ILIAS_VERSION_NUMERIC . " requires PHP $which or later."
        );
    }
}
