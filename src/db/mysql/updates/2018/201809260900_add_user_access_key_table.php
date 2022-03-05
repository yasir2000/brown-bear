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
 *
 */

class b201809260900_add_user_access_key_table extends ForgeUpgrade_Bucket // phpcs:ignore
{
    public function description()
    {
        return 'Add Project Note Widget table';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = 'CREATE TABLE user_access_key (
                  id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
                  user_id INT(11) NOT NULL,
                  verifier VARCHAR(255) NOT NULL,
                  creation_date INT(11) UNSIGNED NOT NULL,
                  description TEXT,
                  last_usage INT(11) UNSIGNED DEFAULT NULL,
                  last_ip VARCHAR(45) DEFAULT NULL
                );';

        $this->db->createTable('user_access_key', $sql);
    }
}
