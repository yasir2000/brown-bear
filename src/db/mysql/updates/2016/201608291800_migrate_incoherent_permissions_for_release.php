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

class b201608291800_migrate_incoherent_permissions_for_release extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description()
    {
        return 'Migrate FRS permissions who are inconsistent';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $anonymous_permission = 1;

        $sql = "DELETE permissions.* FROM frs_global_permissions
                INNER JOIN frs_package ON frs_package.group_id = frs_global_permissions.project_id
                INNER JOIN permissions ON permissions.object_id = CAST(frs_package.package_id AS CHAR)
                WHERE frs_global_permissions.permission_type = 'FRS_READ'
                AND frs_global_permissions.ugroup_id != $anonymous_permission
                AND permissions.permission_type = 'RELEASE_READ'
                AND permissions.ugroup_id = $anonymous_permission";

        if ($this->db->dbh->exec($sql) === false) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException("Error while migrating incoherent permissions");
        }
    }
}
