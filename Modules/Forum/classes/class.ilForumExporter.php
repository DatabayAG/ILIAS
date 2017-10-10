<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("./Services/Export/classes/class.ilXmlExporter.php");

/**
 * Exporter class for sessions
 *
 * @author Stefan Meyer <meyer@leifos.com>
 * @version $Id: $
 * @ingroup ModulesForum
 */
class ilForumExporter extends ilXmlExporter
{
	private $ds;

	/**
	 * Initialisation
	 */
	public function init()
	{
		
	}


	/**
	 * Get xml representation
	 *
	 * @param	string		entity
	 * @param	string		target release
	 * @param	string		id
	 * @return	string		xml string
	 */
	public function getXmlRepresentation($a_entity, $a_schema_version, $a_id)
	{
		$xml = '';

		include_once 'Modules/Forum/classes/class.ilForumXMLWriter.php';
		if(ilObject::_lookupType($a_id) == 'frm')
		{
			$writer = new ilForumXMLWriter();
			$writer->setForumId($a_id);
			ilUtil::makeDirParents($this->getAbsoluteExportDirectory());
			$writer->setFileTargetDirectories($this->getRelativeExportDirectory(), $this->getAbsoluteExportDirectory());
			$writer->start();
			$xml .= $writer->getXml();
		}

		return $xml;
	}

	/**
	 * Returns schema versions that the component can export to.
	 * ILIAS chooses the first one, that has min/max constraints which
	 * fit to the target release. Please put the newest on top.
	 *
	 * @return
	 */
	function getValidSchemaVersions($a_entity)
	{
		return array(
			"4.1.0" => array(
				"namespace"    => "http://www.ilias.de/Modules/Forum/frm/4_1",
				"xsd_file"     => "ilias_frm_4_1.xsd",
				"uses_dataset" => false,
				"min"          => "4.1.0",
				"max"          => "4.4.999"
			),
			"4.5.0" => array(
				"namespace"    => "http://www.ilias.de/Modules/Forum/frm/4_5",
				"xsd_file"     => "ilias_frm_4_5.xsd",
				"uses_dataset" => false,
				"min"          => "4.5.0",
				"max"          => "5.0.999"
			),
			"5.1.0" => array(
				"namespace"    => "http://www.ilias.de/Modules/Forum/frm/5_1",
				"xsd_file"     => "ilias_frm_5_1.xsd",
				"uses_dataset" => false,
				"min"          => "5.1.0",
				"max"          => ""
			)
		);
	}

	/**
	 * @param $a_id
	 * @return array
	 */
	protected function getActiveAdvMDRecords($a_id)
	{
		include_once('Services/AdvancedMetaData/classes/class.ilAdvancedMDRecord.php');
		$active = array();
		$sel_globals = ilAdvancedMDRecord::getObjRecSelection($a_id, ilForumMetaData::FORUM_TYPE_POST);

		foreach(ilAdvancedMDRecord::_getActivatedRecordsByObjectType("frm", ilForumMetaData::FORUM_TYPE_POST) as $record_obj)
		{
			if ($record_obj->getParentObject() == $a_id || in_array($record_obj->getRecordId(), $sel_globals))
			{
				$active[] = $record_obj->getRecordId();
			}
		}

		return $active;
	}

	/**
	 * @param $a_entity
	 * @param $a_target_release
	 * @param $a_ids
	 * @return array
	 */
	public function getXmlExportTailDependencies($a_entity, $a_target_release, $a_ids)
	{
		$adv_meta_data_ids = array();
		$dependencies = array();

		foreach($a_ids as $id)
		{
			$rec_ids = $this->getActiveAdvMDRecords($id);
			if(sizeof($rec_ids))
			{
				foreach($rec_ids as $rec_id)
				{
					$adv_meta_data_ids[] = $id.":".$rec_id;
				}
			}
		}
		if(sizeof($adv_meta_data_ids))
		{
			$dependencies[] = array(
				"component" => "Services/AdvancedMetaData",
				"entity" => "advmd",
				"ids" => $adv_meta_data_ids
			);
		}

		return $dependencies;
	}
}
?>