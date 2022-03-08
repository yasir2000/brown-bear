<?php
/**
 * Copyright (c) BrownBear, 2014-Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\REST\v1\Kanban;

/**
 * @psalm-immutable
 */
class KanbanArchiveInfoRepresentation
{
    /**
     * @var string
     */
    public $label;

    /**
     * @var bool
     */
    public $is_open;

    public function __construct($label, $is_open)
    {
        $this->label   = $label;
        $this->is_open = $is_open;
    }
}
