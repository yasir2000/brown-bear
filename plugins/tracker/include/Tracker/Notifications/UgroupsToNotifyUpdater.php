<?php
/**
 * Copyright Enalean (c) 2017 - Present. All rights reserved.
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

namespace Tuleap\Tracker\Notifications;

use ForgeAccess;
use Project;

class UgroupsToNotifyUpdater
{
    /**
     * @var UgroupsToNotifyDao
     */
    private $dao;

    public function __construct(UgroupsToNotifyDao $dao)
    {
        $this->dao = $dao;
    }

    public function updateProjectAccess(int $project_id, string $old_access, string $new_access): void
    {
        if ($new_access === Project::ACCESS_PRIVATE || $new_access === Project::ACCESS_PRIVATE_WO_RESTRICTED) {
            $this->dao->disableAnonymousRegisteredAuthenticated($project_id);
        }

        if ($new_access === Project::ACCESS_PUBLIC && $old_access === Project::ACCESS_PUBLIC_UNRESTRICTED) {
            $this->dao->disableAuthenticated($project_id);
        }
    }

    public function updateSiteAccess(string $old_value): void
    {
        if ($old_value === ForgeAccess::ANONYMOUS) {
            $this->dao->updateAllAnonymousAccessToRegistered();
        }

        if ($old_value === ForgeAccess::RESTRICTED) {
            $this->dao->updateAllAuthenticatedAccessToRegistered();
        }
    }
}
