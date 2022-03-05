<?php
/**
 * Copyright (c) Enalean 2016 - Present. All rights reserved
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

class b201604181408_force_refresh_codendi_svnroot extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description()
    {
        return 'Force refresh of codendi_svnroot file to change the svnroot in Location';
    }

    public function up()
    {
        exec('/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/src/utils/svn/force_refresh_codendi_svnroot.php');
    }
}
