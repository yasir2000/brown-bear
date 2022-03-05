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

namespace Tuleap\Tracker\REST\FormElement;

/**
 * @psalm-immutable
 */
class PermissionsForGroupsRepresentation
{
    /**
     * @var array {@type Tuleap\Project\REST\UserGroupRepresentation}
     */
    public $can_submit = [];
    /**
     * @var array {@type Tuleap\Project\REST\UserGroupRepresentation}
     */
    public $can_read = [];
    /**
     * @var array {@type Tuleap\Project\REST\UserGroupRepresentation}
     */
    public $can_update = [];

    public function __construct(array $can_read, array $can_submit, array $can_update)
    {
        $this->can_read   = $can_read;
        $this->can_submit = $can_submit;
        $this->can_update = $can_update;
    }
}
