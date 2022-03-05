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

class b201704041400_add_recently_visited_table extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description()
    {
        return 'Add table to store the the recently visited artifacts';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = 'CREATE TABLE plugin_tracker_recently_visited (
                  user_id INT(11) NOT NULL,
                  artifact_id INT(11) NOT NULL,
                  created_on INT(11) UNSIGNED NOT NULL,
                  PRIMARY KEY(user_id, artifact_id)
                ) ENGINE=InnoDB';

        $this->db->createTable('plugin_tracker_recently_visited', $sql);
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('plugin_tracker_recently_visited')) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException('plugin_tracker_recently_visited table is missing');
        }
    }
}
