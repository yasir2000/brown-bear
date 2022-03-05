<?php
/**
 * Copyright (c) Enalean SAS - 2014 - Present. All rights reserved
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

class b201402191017_add_mwgroups_table extends \Tuleap\ForgeUpgrade\Bucket
{
    /**
     * Description of the bucket
     *
     * @return String
     */
    public function description()
    {
        return <<<EOT
Create plugin_mediawiki_tuleap_mwgroups table
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
     * Creation of the table
     *
     * @return void
     */
    public function up()
    {
        $sql = "CREATE TABLE plugin_mediawiki_tuleap_mwgroups (
                    mw_group_name ENUM( 'anonymous', 'user', 'bot', 'sysop', 'bureaucrat' ) NOT NULL DEFAULT 'anonymous',
                    real_name varbinary(32) NOT NULL DEFAULT '',
                    INDEX idx_mw_group_name (mw_group_name)
                )";
        $this->execDB($sql, "Cannot create table");

        $sql = "INSERT INTO plugin_mediawiki_tuleap_mwgroups(mw_group_name, real_name)
                VALUES
                    ('anonymous', '*'),
                    ('user', 'user'),
                    ('user', 'autoconfirmed'),
                    ('user', 'emailconfirmed'),
                    ('bot', 'bot'),
                    ('sysop', 'sysop'),
                    ('bureaucrat', 'bureaucrat')";
        $this->execDB($sql, "Cannot init table");
    }

    private function execDB($sql, $message)
    {
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException($message . implode(', ', $this->db->dbh->errorInfo()));
        }
    }
}
