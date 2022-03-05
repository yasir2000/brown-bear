<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Kanban;

class ColumnIdentifier
{
    public const BACKLOG_COLUMN = 'backlog';
    public const ARCHIVE_COLUMN = 'archive';

    private $column_id;

    public function __construct($column_id)
    {
        $this->column_id = $column_id;
    }

    public function isBacklog()
    {
        return $this->column_id === self::BACKLOG_COLUMN;
    }

    public function isArchive()
    {
        return $this->column_id === self::ARCHIVE_COLUMN;
    }

    /**
     * @return String
     */
    public function getColumnId()
    {
        return $this->column_id;
    }
}