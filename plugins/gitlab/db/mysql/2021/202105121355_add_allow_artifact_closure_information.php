<?php
/**
 * Copyright (c) BrownBear, 2021 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class b202105121355_add_allow_artifact_closure_information extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Add allow artifact closure information in the table plugin_gitlab_repository_project';
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        $this->db->alterTable(
            'plugin_gitlab_repository_project',
            'tuleap',
            'allow_artifact_closure',
            'ALTER TABLE plugin_gitlab_repository_project
                ADD COLUMN allow_artifact_closure TINYINT(1) NOT NULL DEFAULT 0'
        );
    }
}
