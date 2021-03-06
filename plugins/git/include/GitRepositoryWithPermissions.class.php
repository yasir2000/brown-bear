<?php
/*
 * Copyright BrownBear (c) 2011, 2012, 2013 - Present. All rights reserved.
 *
 * Tuleap and BrownBear names and logos are registrated trademarks owned by
 * BrownBear SAS. All other trademarks or names are properties of their respective
 * owners.
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


/**
 * Git Repository with its permissions
 */
class GitRepositoryWithPermissions
{
    private $repository;
    private $permissions = [
        Git::PERM_READ          => [],
        Git::PERM_WRITE         => [],
        Git::PERM_WPLUS         => [],
        Git::SPECIAL_PERM_ADMIN => [],
    ];

    public function __construct(GitRepository $repository, array $permissions = [])
    {
        $this->repository = $repository;
        if (count($permissions) > 0) {
            $this->permissions = $permissions;
        }
    }

    public function addUGroupForPermissionType($permission_type, $ugroup_id)
    {
        if (! isset($this->permissions[$permission_type])) {
            throw new RuntimeException('Invalid GIT permission type ' . $permission_type);
        }
        $this->permissions[$permission_type][] = $ugroup_id;
    }

    /**
     * @return GitRepository
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * Permissions for the repository
     * permission_type => array of ugroup id that can access the repo
     *
     * @return Array
     */
    public function getPermissions()
    {
        return $this->permissions;
    }
}
