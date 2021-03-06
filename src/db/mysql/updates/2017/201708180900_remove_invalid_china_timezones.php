<?php
/**
 * Copyright (c) BrownBear, 2017 - Present. All Rights Reserved.
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

class b201708180900_remove_invalid_china_timezones extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description()
    {
        return 'Remove invalid China timezones';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = 'UPDATE user SET timezone="Asia/Shanghai" WHERE timezone="China/Beijing" OR timezone="China/Shanghai"';

        if (! $this->db->dbh->query($sql)) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException(
                'The migration of invalid China timezones has not been successfully done'
            );
        }
    }
}
