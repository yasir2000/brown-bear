<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\TestManagement\REST\v1;

/**
 * @psalm-immutable
 */
class PATCHExecutionRepresentation
{
    /**
     * @var bool True to update the execution to use latest version of definition {@required false}
     */
    public $force_use_latest_definition_version;

    /**
     * @var array Results of steps {@type \Tuleap\TestManagement\REST\v1\StepResultRepresentation} {@required false} {@min 1}
     */
    public $steps_results;
}
