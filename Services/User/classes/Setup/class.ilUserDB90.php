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

use ILIAS\Setup;
use ILIAS\Refinery;
use ILIAS\Setup\Environment;
use ILIAS\Setup\Objective;
use ILIAS\UI;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class ilUserDB90 implements ilDatabaseUpdateSteps
{
    protected ilDBInterface $db;

    public function prepare(ilDBInterface $db): void
    {
        $this->db = $db;
    }


    /**
     * creates a column "rid" that is used to reference d IRSS Resource for a Profile Picture
     */
    public function step_1(): void
    {
        if (!$this->db->tableColumnExists('usr_data', 'rid')) {
            $this->db->addTableColumn(
                'usr_data',
                'rid',
                [
                    'type' => 'text',
                    'notnull' => false,
                    'length' => 64,
                    'default' => ''
                ]
            );
        }
    }

    /**
     * Modifies the 'passwd' field in table 'usr_data' to accept longer passwords
     */
    public function step_2(): void
    {
        if ($this->db->tableColumnExists('usr_data', 'passwd')) {
            $this->db->modifyTableColumn(
                'usr_data',
                'passwd',
                [
                    'type' => 'text',
                    'length' => 100,
                    'notnull' => false,
                    'default' => null
                ]
            );
        }
    }

    public function step_3(): void
    {
        $this->db->modifyTableColumn(
            'usr_sess_istorage',
            'session_id',
            [
                'type' => ilDBConstants::T_TEXT,
                'length' => '256'
            ]
        );
    }

    /**
     * Remove the special charactor selector settings from the user preferences
     */
    public function step_4(): void
    {
        $this->db->manipulate("DELETE FROM usr_pref WHERE keyword LIKE 'char_selector%'");
    }
}
