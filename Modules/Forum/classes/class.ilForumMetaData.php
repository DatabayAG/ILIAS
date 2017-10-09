<?php
require_once 'Services/ADT/classes/class.ilADTFactory.php';
require_once 'Services/AdvancedMetaData/classes/class.ilAdvancedMDValues.php';
require_once 'Services/AdvancedMetaData/classes/class.ilAdvancedMDRecord.php';
require_once 'Services/AdvancedMetaData/classes/class.ilAdvancedMDValues.php';

class ilForumMetaData
{
	const FORUM_TYPE_POST = 'frmp';
	
	/**
	 * @param $ref_id
	 * @param $sub_id
	 * @return array
	 */
	public static function getMetadataAsKeyValue($ref_id, $sub_id)
	{

		$old_dt = ilDatePresentation::useRelativeDates();
		ilDatePresentation::setUseRelativeDates(false);
		$key_value = array();
		/** @var ilAdvancedMDRecord $record */
		foreach(ilAdvancedMDRecord::_getSelectedRecordsByObject('frm', $ref_id, self::FORUM_TYPE_POST) as $record)
		{
			$val = new ilAdvancedMDValues($record->getRecordId(), $ref_id, self::FORUM_TYPE_POST, $sub_id);
			$val->read();

			/** @var ilAdvancedMDFieldDefinition[] $def */
			$def      = $val->getDefinitions();
			/** @var $element ilADT */
			foreach($val->getADTGroup()->getElements() as $element_id => $element)
			{
				if($element instanceof ilADTLocation)
				{
					continue;
				}
				if($element->isNull())
				{
					$value = '-';
				}
				else
				{
					$value = ilADTFactory::getInstance()->getPresentationBridgeForInstance($element);
					$value = $value->getHTML();
				}
				$key_value[$def[$element_id]->getTitle()] = $value;
			}

		}

		ilDatePresentation::setUseRelativeDates($old_dt);
		return $key_value;
	}
}