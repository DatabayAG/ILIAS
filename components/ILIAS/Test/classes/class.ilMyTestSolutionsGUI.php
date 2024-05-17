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

use ILIAS\Test\RequestDataCollector;

use ILIAS\TestQuestionPool\Questions\GeneralQuestionPropertiesRepository;

/**
 * Class ilMyTestSolutionsGUI
 *
 * @author    Björn Heyser <info@bjoernheyser.de>
 * @version    $Id$
 *
 * @package components\ILIAS/Test
 *
 * @ilCtrl_Calls ilMyTestSolutionsGUI: ilTestEvaluationGUI
 * @ilCtrl_Calls ilMyTestSolutionsGUI: ilAssQuestionPageGUI
 */
class ilMyTestSolutionsGUI
{
    public function __construct(
        private readonly ?ilObjTest $test_obj,
        private readonly ilTestAccess $test_access,
        private readonly ilTestObjectiveOrientedContainer $objective_parent,
        private readonly ilLanguage $lng,
        private readonly ilCtrlInterface $ctrl,
        private readonly ilGlobalTemplateInterface $tpl,
        private readonly GeneralQuestionPropertiesRepository $questionrepository,
        private readonly RequestDataCollector $testrequest
    ) {
    }

    public function executeCommand(): void
    {
        switch ($this->ctrl->getNextClass()) {
            case "iltestevaluationgui":
                $gui = new ilTestEvaluationGUI($this->test_obj);
                $this->ctrl->forwardCommand($gui);
                break;

            case 'ilassquestionpagegui':
                $forwarder = new ilAssQuestionPageCommandForwarder(
                    $this->test_obj,
                    $this->lng,
                    $this->ctrl,
                    $this->tpl,
                    $this->questionrepository,
                    $this->testrequest
                );
                $forwarder->forward();
                break;
        }
    }
}
