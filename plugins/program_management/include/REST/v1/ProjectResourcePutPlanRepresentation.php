<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\ProgramManagement\REST\v1;

/**
 * @psalm-immutable
 */
final class ProjectResourcePutPlanRepresentation
{
    /**
     * @var int {@required true}
     */
    public $program_increment_tracker_id;
    /**
     * @var array {@type int}
     */
    public $plannable_tracker_ids;
    /**
     * @var PlanPutPermissions {@type \Tuleap\ProgramManagement\REST\v1\PlanPutPermissions} {@required true}
     */
    public $permissions;
    /**
     * @var string | null {@required false} {@max 255}
     */
    public $program_increment_label = null;
    /**
     * @var string | null {@required false} {@max 255}
     */
    public $program_increment_sub_label = null;
    /**
     * @var ProjectResourcePutPlanIterationRepresentation | null {@type \Tuleap\ProgramManagement\REST\v1\ProjectResourcePutPlanIterationRepresentation} {@required false}
     */
    public $iteration = null;
}
