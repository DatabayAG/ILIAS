<?php
/******************************************************************************
 *
 * This file is part of ILIAS, a powerful learning management system.
 *
 * ILIAS is licensed with the GPL-3.0, you should have received a copy
 * of said license along with the source code.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 *      https://www.ilias.de
 *      https://github.com/ILIAS-eLearning
 *
 *****************************************************************************/
/**
 * Class ilObjPDFGeneration
 * @author Michael Jansen <mjansen@databay.de>
 */
class ilObjPDFGeneration extends ilObject2
{
    /**
     *
     */
    protected function initType() : void
    {
        $this->type = 'pdfg';
    }
}
