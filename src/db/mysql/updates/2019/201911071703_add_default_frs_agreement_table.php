<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

declare(strict_types=1);

class b201911071703_add_default_frs_agreement_table extends ForgeUpgrade_Bucket // phpcs:ignore
{
    public function description(): string
    {
        return 'Add tables for frs download agreement default';
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        $sql = "CREATE TABLE frs_download_agreement_default (
            project_id int(11) NOT NULL,
            agreement_id INT(11) NOT NULL,
            PRIMARY KEY (project_id)
        )";
        $this->db->createTable('frs_download_agreement_default', $sql);
    }
}
