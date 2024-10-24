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
 * @author		Björn Heyser <bheyser@databay.de>
 * @version		$Id$
 *
 * @package     Modules/Test
 *
 * @ilCtrl_Calls ilTestEvalObjectiveOrientedGUI: ilAssQuestionPageGUI
 * @ilCtrl_Calls ilTestEvalObjectiveOrientedGUI: ilTestResultsToolbarGUI
 */
class ilTestEvalObjectiveOrientedGUI extends ilTestServiceGUI
{
    public function executeCommand()
    {
        $this->ctrl->saveParameter($this, "active_id");

        switch ($this->ctrl->getNextClass($this)) {
            case 'ilassquestionpagegui':
                $forwarder = new ilAssQuestionPageCommandForwarder();
                $forwarder->setTestObj($this->object);
                $forwarder->forward();
                break;

            default:
                $cmd = $this->ctrl->getCmd('showVirtualPass') . 'Cmd';
                $this->$cmd();
        }
    }

    public function showVirtualPassSetTableFilterCmd()
    {
        $tableGUI = $this->buildPassDetailsOverviewTableGUI($this, 'showVirtualPass');
        $tableGUI->initFilter();
        $tableGUI->resetOffset();
        $tableGUI->writeFilterToSession();
        $this->showVirtualPassCmd();
    }

    public function showVirtualPassResetTableFilterCmd()
    {
        $tableGUI = $this->buildPassDetailsOverviewTableGUI($this, 'showVirtualPass');
        $tableGUI->initFilter();
        $tableGUI->resetOffset();
        $tableGUI->resetFilter();
        $this->showVirtualPassCmd();
    }

    private function showVirtualPassCmd()
    {
        $testSession = $this->testSessionFactory->getSession();

        if (!$this->object->getShowPassDetails()) {
            $executable = $this->object->isExecutable($testSession, $testSession->getUserId());

            if ($executable["executable"]) {
                $this->ctrl->redirectByClass("ilobjtestgui", "infoScreen");
            }
        }

        $toolbar = $this->buildUserTestResultsToolbarGUI();
        $toolbar->build();

        $virtualSequence = $this->service->buildVirtualSequence($testSession);
        $userResults = $this->service->getVirtualSequenceUserResults($virtualSequence);

        $objectivesAdapter = ilLOTestQuestionAdapter::getInstance($testSession);

        $objectivesList = $this->buildQuestionRelatedObjectivesList($objectivesAdapter, $virtualSequence);
        $objectivesList->loadObjectivesTitles();

        $testResultHeaderLabelBuilder = new ilTestResultHeaderLabelBuilder($this->lng, $this->objCache);

        $testResultHeaderLabelBuilder->setObjectiveOrientedContainerId($testSession->getObjectiveOrientedContainerId());
        $testResultHeaderLabelBuilder->setUserId($testSession->getUserId());
        $testResultHeaderLabelBuilder->setTestObjId($this->object->getId());
        $testResultHeaderLabelBuilder->setTestRefId($this->object->getRefId());
        $testResultHeaderLabelBuilder->initObjectiveOrientedMode();

        $tpl = new ilTemplate('tpl.il_as_tst_virtual_pass_details.html', false, false, 'Modules/Test');

        $command_solution_details = "";
        if ($this->object->getShowSolutionDetails()) {
            $command_solution_details = "outCorrectSolution";
        }

        $questionAnchorNav = $listOfAnswers = $this->object->canShowSolutionPrintview();

        if ($listOfAnswers) {
            $list_of_answers = $this->getPassListOfAnswers(
                $userResults,
                $testSession->getActiveId(),
                null,
                $this->object->getShowSolutionListComparison(),
                false,
                false,
                false,
                true,
                $objectivesList,
                $testResultHeaderLabelBuilder
            );
            $tpl->setVariable("LIST_OF_ANSWERS", $list_of_answers);
        }

        foreach ($objectivesList->getObjectives() as $loId => $loTitle) {
            $userResultsForLO = $objectivesList->filterResultsByObjective($userResults, $loId);

            $overviewTableGUI = $this->getPassDetailsOverviewTableGUI(
                $userResultsForLO,
                $testSession->getActiveId(),
                null,
                $this,
                "showVirtualPass",
                $command_solution_details,
                $questionAnchorNav,
                $objectivesList,
                false
            );
            $overviewTableGUI->setTitle(
                $testResultHeaderLabelBuilder->getVirtualPassDetailsHeaderLabel(
                    $objectivesList->getObjectiveTitleById($loId)
                )
            );

            $loStatus = new ilTestLearningObjectivesStatusGUI($this->lng, $this->ctrl, $this->testrequest);
            $loStatus->setCrsObjId($this->getObjectiveOrientedContainer()->getObjId());
            $loStatus->setUsrId($testSession->getUserId());
            $lostatus = $loStatus->getHTML($loId);

            $tpl->setCurrentBlock('pass_details');
            $tpl->setVariable("PASS_DETAILS", $overviewTableGUI->getHTML());
            $tpl->setVariable("LO_STATUS", $lostatus);
            $tpl->parseCurrentBlock();
        }

        $this->populateContent($this->ctrl->getHTML($toolbar) . $tpl->get());
    }
}
