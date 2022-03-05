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

namespace Tuleap\Dashboard\Project;

use PFUser;
use Project;
use Tuleap\Dashboard\Widget\DashboardWidgetDao;

class WidgetMinimizor
{
    /**
     * @var DashboardWidgetDao
     */
    private $dao;

    public function __construct(DashboardWidgetDao $dao)
    {
        $this->dao = $dao;
    }

    public function minimize(PFUser $user, Project $project, $dashboard_id, $dashboard_type, $widget_id)
    {
        $this->checkUserCanMinimize($user, $project);
        $this->dao->minimizeWidget($project->getID(), $dashboard_id, $dashboard_type, $widget_id);
    }

    public function maximize(PFUser $user, Project $project, $dashboard_id, $dashboard_type, $widget_id)
    {
        $this->checkUserCanMinimize($user, $project);
        $this->dao->maximizeWidget($project->getID(), $dashboard_id, $dashboard_type, $widget_id);
    }

    private function checkUserCanMinimize(PFUser $user, Project $project)
    {
        if (! $user->isAdmin($project->getID())) {
            throw new UserCanNotUpdateProjectDashboardException();
        }
    }
}
