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
 * Class ilTCPDFGenerator
 *
 * @author Maximilian Becker <mbecker@databay.de>
 * @version $Id$
 */
class ilTCPDFGenerator
{
    public static function generatePDF(ilPDFGenerationJob $job) : void
    {
        // create new PDF document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // set document information
        $pdf->SetCreator($job->getCreator());
        $pdf->SetAuthor($job->getAuthor());
        $pdf->SetTitle($job->getTitle());
        $pdf->SetSubject($job->getSubject());
        $pdf->SetKeywords($job->getKeywords());

        //$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 006', PDF_HEADER_STRING); // TODO
        $pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN)); // TODO
        $pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA)); // TODO
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED); // TODO
        $pdf->SetMargins($job->getMarginLeft(), $job->getMarginTop(), $job->getMarginRight());
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER); // TODO
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER); // TODO
        $pdf->SetAutoPageBreak($job->getAutoPageBreak(), $job->getMarginBottom());
        $pdf->setImageScale($job->getImageScale());
        $pdf->SetFont('dejavusans', '', 10); // TODO

        $pdf->setSpacesRE('/[^\S\xa0]/'); // Fixing unicode/PCRE-mess #17547

        /* // TODO
        // set some language-dependent strings (optional)
        if (file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf->setLanguageArray($l);
        }
        */
        // set font

        foreach ($job->getPages() as $page) {
            $page = ' ' . $page;
            $pdf->AddPage();
            $pdf->writeHTML($page, true, false, true, false, '');
        }
        $result = $pdf->Output($job->getFilename(), $job->getOutputMode()); // (I - Inline, D - Download, F - File)

        if (in_array($job->getOutputMode(), array('I', 'D'))) {
            exit();
        }
    }
}
