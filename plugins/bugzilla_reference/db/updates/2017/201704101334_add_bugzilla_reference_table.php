<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

class b201704101334_add_bugzilla_reference_table extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description()
    {
        return <<<EOT
Add Bugzilla reference table
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE plugin_bugzilla_reference (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    keyword VARCHAR(255) NOT NULL,
    server VARCHAR(255) NOT NULL,
    username VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    are_followup_private TINYINT(1),
    INDEX keyword_idx(keyword(5))
) ENGINE=InnoDB";

        $this->db->createTable('plugin_bugzilla_reference', $sql);
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('plugin_bugzilla_reference')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('plugin_bugzilla_reference is missing');
        }
    }
}
