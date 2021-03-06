<?php
/**
 * Copyright (c) BrownBear, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\MonoMilestone;

class ScrumForMonoMilestoneEnabler
{
    /**
     * @var ScrumForMonoMilestoneDao
     */
    private $scrum_mono_milestone_dao;

    public function __construct(ScrumForMonoMilestoneDao $scrum_mono_milestone_dao)
    {
        $this->scrum_mono_milestone_dao = $scrum_mono_milestone_dao;
    }

    public function enableScrumForMonoMilestones($project_id)
    {
        $this->scrum_mono_milestone_dao->enableScrumForMonoMilestones($project_id);
    }
}
