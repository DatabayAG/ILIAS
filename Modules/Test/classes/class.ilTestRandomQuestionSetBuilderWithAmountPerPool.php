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
 */
class ilTestRandomQuestionSetBuilderWithAmountPerPool extends ilTestRandomQuestionSetBuilder
{
    // hey: fixRandomTestBuildable - improvment of improved pass build check
    /**
     * @return bool
     */
    public function checkBuildableNewer(): bool
    {
        $isBuildable = true;

        $quantitiesDistribution = new ilTestRandomQuestionsQuantitiesDistribution(
            $this->db,
            $this,
            $this->sourcePoolDefinitionList
        );
        $quantitiesDistribution->initialise();

        // perhaps not every with every BUT every with any next ??!
        // perhaps exactly like this !!? I dont know :-)
        // it should be about vice versa rule conflict reporting

        foreach ($this->sourcePoolDefinitionList as $definition) {
            /** @var ilTestRandomQuestionSetSourcePoolDefinition $definition */

            $quantityCalculation = $quantitiesDistribution->calculateQuantities($definition);

            if ($quantityCalculation->isRequiredAmountGuaranteedAvailable()) {
                continue;
            }

            $isBuildable = false;

            $this->checkMessages[] = $quantityCalculation->getDistributionReport($this->lng);
        }

        return $isBuildable;
    }
    // hey.

    /**
     * @return bool
     */
    public function checkBuildable(): bool
    {
        // hey: fixRandomTestBuildable - improved the buildable check improvement
        return $this->checkBuildableNewer();
        // hey.

        $questionStage = $this->getSrcPoolDefListRelatedQuestUniqueCollection($this->sourcePoolDefinitionList);

        if ($questionStage->isSmallerThan($this->sourcePoolDefinitionList->getQuestionAmount())) {
            return false;
        }

        return true;
    }

    public function performBuild(ilTestSession $testSession)
    {
        $questionSet = new ilTestRandomQuestionSetQuestionCollection();

        foreach ($this->sourcePoolDefinitionList as $definition) {
            /** @var ilTestRandomQuestionSetSourcePoolDefinition $definition */

            $requiredQuestionAmount = $definition->getQuestionAmount();

            $potentialQuestionStage = $this->getSrcPoolDefRelatedQuestCollection($definition);

            $actualQuestionStage = $potentialQuestionStage->getRelativeComplementCollection($questionSet);

            if ($actualQuestionStage->isGreaterThan($requiredQuestionAmount)) {
                $questions = $this->fetchQuestionsFromStageRandomly($actualQuestionStage, $requiredQuestionAmount);
            } else {
                // fau: fixRandomTestBuildable - log missing questions for a random test rule
                if ($actualQuestionStage->isSmallerThan($requiredQuestionAmount)) {
                    if (!isset($translator)) {
                        $translator = new ilTestQuestionFilterLabelTranslater($this->db, $this->lng);
                        $translator->loadLabels($this->sourcePoolDefinitionList);
                    }
                    $this->log->write("RANDOM TEST: missing questions for: "
                        . implode(" - ", array($definition->getPoolTitle(), $translator->getTaxonomyFilterLabel($definition->getMappedTaxonomyFilter()))));
                }
                // fau.
                $questions = $actualQuestionStage;
            }

            $questionSet->mergeQuestionCollection($questions);
        }

        $requiredQuestionAmount = $this->sourcePoolDefinitionList->getQuestionAmount();

        if ($questionSet->isSmallerThan($requiredQuestionAmount)) {
            $missingQuestionCount = $questionSet->getMissingCount($requiredQuestionAmount);
            // fau: fixRandomTestBuildable - avoid already chosen questions being used as fillers
            $potentialQuestionStage = $this->getSrcPoolDefListRelatedQuestUniqueCollection($this->sourcePoolDefinitionList);
            $actualQuestionStage = $potentialQuestionStage->getRelativeComplementCollection($questionSet);
            $questions = $this->fetchQuestionsFromStageRandomly($actualQuestionStage, $missingQuestionCount);
            // fau.
            $questionSet->mergeQuestionCollection($questions);
        }

        $this->handleQuestionOrdering($questionSet);

        $this->storeQuestionSet($testSession, $questionSet);
    }
}
