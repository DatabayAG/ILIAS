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

namespace ILIAS\Export\ImportHandler\I\File\XML\Manifest;

use ILIAS\Export\ImportHandler\File\XML\Manifest\ilExportObjectType;
use ILIAS\Export\ImportHandler\I\File\XML\Export\ilCollectionInterface as ilXMLExportFileCollectionInterface;
use ILIAS\Export\ImportHandler\I\File\XML\ilHandlerInterface as ilXMLFileHandlerInterface;
use ILIAS\Export\ImportHandler\I\File\XML\Manifest\ilHandlerCollectionInterface as ilManifestXMLFileHandlerCollectionInterface;
use ILIAS\Export\ImportStatus\I\ilCollectionInterface as ilImportStatusHandlerCollectionInterface;
use SplFileInfo;

interface ilHandlerInterface extends ilXMLFileHandlerInterface
{
    public function withFileInfo(SplFileInfo $file_info): ilHandlerInterface;

    public function getExportObjectType(): ilExportObjectType;

    public function validateManifestXML(): ilImportStatusHandlerCollectionInterface;

    public function findXMLFileHandlers(): ilXMLExportFileCollectionInterface;

    public function findManifestXMLFileHandlers(): ilManifestXMLFileHandlerCollectionInterface;
}
