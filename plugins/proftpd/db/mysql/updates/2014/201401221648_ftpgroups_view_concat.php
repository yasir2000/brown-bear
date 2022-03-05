<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class b201401221648_ftpgroups_view_concat extends \Tuleap\ForgeUpgrade\Bucket
{
    /**
     * Description of the bucket
     *
     * @return String
     */
    public function description()
    {
        return <<<EOT
Update ftpgroups view to have exactly one line per group
EOT;
    }

    /**
     * Get the API
     *
     * @return void
     */
    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    /**
     * Creation of the column
     *
     * @return void
     */
    public function up()
    {
        $sql = "CREATE OR REPLACE VIEW ftpgroups AS
            (
                SELECT unix_group_name as groupname, group_id+1000 as gid, GROUP_CONCAT(user_name) as members
                FROM `groups`
                    JOIN user_group USING (group_id)
                    JOIN user USING (user_id)
                WHERE `groups`.status = 'A'
                    AND user.status IN ('A', 'R')
                    AND user_id > 100
                GROUP BY gid
            )
            UNION
            (
                SELECT LOWER(CONCAT(`groups`.unix_group_name, '-', ugroup.name)) as groupname, ugroup_id+10000 as gid, GROUP_CONCAT(user_name) as members
                FROM ugroup
                    JOIN `groups` USING (group_id)
                    LEFT JOIN ugroup_user USING (ugroup_id)
                    LEFT JOIN user USING (user_id)
                WHERE `groups`.status = 'A'
                    AND user_id > 100
                    AND (user.user_id IS NULL OR user.status IN ('A', 'R'))
                GROUP BY gid
            )
            UNION
            (
                SELECT user_name as groupname, unix_uid+20000 as gid, user_name
                FROM user
                WHERE status IN ('A', 'R')
                    AND user_id > 100
                GROUP BY gid
            );";
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException('Cannot update view');
        }
    }
}
