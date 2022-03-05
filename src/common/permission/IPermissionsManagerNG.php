<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/**
 * This interface describe all methods needed to replace the old permissions.php
 */
interface IPermissionsManagerNG
{
    /**
     * @return int[]
     */
    public function getAuthorizedUGroupIdsForProject(Project $project, $object_id, $permission_type);
    /**
     * @return int[]
     */
    public function getAuthorizedUGroupIdsForProjectWithoutDefaultValues(Project $project, $object_id, $permission_type);
    public function savePermissions(Project $project, $object_id, $permission_type, array $ugroup_ids);
}