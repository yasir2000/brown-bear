<?php
/**
 * Copyright (c) Enalean 2015 - Present. All rights reserved
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

class b201512091100_create_tracker_notification_assigned_to_table extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description()
    {
        return 'Add plugin_tracker_notification_assigned_to table';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE plugin_tracker_notification_assigned_to (
                    project_id INT(11) NOT NULL PRIMARY KEY
                ) ENGINE=InnoDB;";

        $this->db->createTable('plugin_tracker_notification_assigned_to', $sql);
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('plugin_tracker_notification_assigned_to')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('Table plugin_tracker_notification_assigned_to not created');
        }
    }
}
