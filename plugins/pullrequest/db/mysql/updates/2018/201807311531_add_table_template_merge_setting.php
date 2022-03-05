<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

class b201807311531_add_table_template_merge_setting extends ForgeUpgrade_Bucket // phpcs:ignore
{
    public function description()
    {
        return 'Add the table plugin_pullrequest_template_merge_setting';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->db->createTable(
            'plugin_pullrequest_template_merge_setting',
            'CREATE TABLE IF NOT EXISTS plugin_pullrequest_template_merge_setting (
                project_id INT(11) NOT NULL PRIMARY KEY,
                merge_commit_allowed BOOLEAN NOT NULL
            );'
        );
    }
}
