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
 * Class ilTestAnswerOptionalQuestionsConfirmationGUITest
 * @author Marvin Beym <mbeym@databay.de>
 */
class ilTestAnswerOptionalQuestionsConfirmationGUITest extends ilTestBaseTestCase
{
    protected $lng_mock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lng_mock = $this->createMock(ilLanguage::class);
    }

    public function test_instantiateObject_shouldReturnInstance(): void
    {
        $instance = new ilTestAnswerOptionalQuestionsConfirmationGUI($this->lng_mock);

        $this->assertInstanceOf(ilTestAnswerOptionalQuestionsConfirmationGUI::class, $instance);
    }

    public function testGetAndSetCancelCmd(): void
    {
        $expect = "testCancelCmd";

        $gui = new ilTestAnswerOptionalQuestionsConfirmationGUI($this->lng_mock);

        $gui->setCancelCmd($expect);

        $this->assertEquals($expect, $gui->getCancelCmd());
    }

    public function testGetAndSetConfirmCmd(): void
    {
        $expect = "testConfirmCmd";

        $gui = new ilTestAnswerOptionalQuestionsConfirmationGUI($this->lng_mock);

        $gui->setConfirmCmd($expect);

        $this->assertEquals($expect, $gui->getConfirmCmd());
    }
}
