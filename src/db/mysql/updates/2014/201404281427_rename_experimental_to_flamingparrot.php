<?php
/**
 * Copyright (c) Enalean SAS 2014 - Present. All rights reserved
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

/**
 * Rename Experimental to FlamingParrot
 */
class b201404281427_rename_experimental_to_flamingparrot extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description()
    {
        return "Rename Experimental to FlamingParrot";
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "UPDATE user SET theme = 'FlamingParrot' WHERE theme = 'Experimental'";

        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException('An error occured while renaming Experimental to FlamingParrot.');
        }
    }
}
