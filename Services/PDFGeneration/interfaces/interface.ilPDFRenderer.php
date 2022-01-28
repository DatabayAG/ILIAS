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
interface ilPDFRenderer
{
    /**
     *
     * @return void
     * @param mixed[] $config
     */
    public function generatePDF(string $service, string $purpose, array $config, \ilPDFGenerationJob $job);


    /**
     * Prepare the content processing at the beginning of a PDF generation request
     * Should be used to initialize the processing of latex code
     * The PDF renderers require different image formats generated by the MathJax service
     *
     * @return void
     */
    public function prepareGenerationRequest(string $service, string $purpose);
}
