<?php

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */


/**
 * Class ilObjLTIConsumer
 *
 * @author      Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author      Björn Heyser <info@bjoernheyser.de>
 *
 * @package     Modules/LTIConsumer
 */
class ilObjLTIConsumerAccess extends ilObjectAccess
{
	public static function _getCommands()
	{
		$commands = array
		(
			array(
				"permission" => "read",
				"cmd" => "infoScreen",
				"lang_var" => "",
				"default" => true
			),
			array(
				'permission' => 'write',
				'cmd' => 'ilLTIConsumerSettingsGUI::showSettings',
				'lang_var' => 'settings'
			)
		);
		
		return $commands;
	}
	
	/**
	 * @param int $a_obj_id
	 * @return bool
	 */
	public static function _isOffline($a_obj_id)
	{
		global $DIC; /* @var \ILIAS\DI\Container $DIC */
		
		$query = "
			SELECT		COUNT(*) cnt
			FROM		lti_consumer_settings
			WHERE		obj_id = %s
			AND			offline_status = 1
		";
		
		$res = $DIC->database()->queryF( $query, array('integer'), array($a_obj_id) );
		$row = $DIC->database()->fetchAssoc($res);
		
		return (bool)$row['cnt'];
	}
	
}
