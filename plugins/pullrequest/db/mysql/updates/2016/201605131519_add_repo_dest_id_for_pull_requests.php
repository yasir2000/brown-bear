<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

class b201605131519_add_repo_dest_id_for_pull_requests extends ForgeUpgrade_Bucket // phpcs:ignore
{
    public function description()
    {
        return <<<EOT
Add repo_dest_id to pull requests to manage pull requests between forks.
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "ALTER TABLE plugin_pullrequest_review ADD (repo_dest_id INT(11) NOT NULL);";
        $this->executeSql($sql);
    }

    public function executeSql($sql)
    {
        $result = $this->db->dbh->exec($sql);
        if ($result === false) {
            $error_message = implode(', ', $this->db->dbh->errorInfo());
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException($error_message);
        }
    }
}
