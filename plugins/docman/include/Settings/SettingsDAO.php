<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

namespace Tuleap\Docman\Settings;

final class SettingsDAO extends \Tuleap\DB\DataAccessObject implements DAOSettings
{
    public function searchFileNamePatternFromProjectId(int $project_id): ?string
    {
        $sql = "SELECT filename_pattern FROM plugin_docman_project_settings WHERE group_id=?";
        $row = $this->getDB()->first($sql, $project_id);
        return $row[0];
    }

    public function saveFilenamePattern(int $project_id, ?string $pattern): void
    {
        $this->getDB()->update(
            'plugin_docman_project_settings',
            [
                'filename_pattern' => $pattern,
            ],
            ['group_id' => $project_id]
        );
    }
}
