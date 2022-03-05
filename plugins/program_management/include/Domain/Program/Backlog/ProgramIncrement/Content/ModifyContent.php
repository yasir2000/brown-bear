<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Content;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureCanNotBeRankedWithItselfException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureHasPlannedUserStoryException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\NotAllowedToPrioritizeException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementHasNoProgramException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Plan\FeatureCannotBePlannedInProgramIncrementException;
use Tuleap\ProgramManagement\Domain\Program\Plan\InvalidFeatureIdInProgramIncrementException;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProgramAccessException;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ProgramManagement\Domain\Program\ProgramTrackerException;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

/**
 * I add and/or reorder the contents of a Program Increment
 */
interface ModifyContent
{
    /**
     * @throws AddFeatureException
     * @throws FeatureCanNotBeRankedWithItselfException
     * @throws FeatureCannotBePlannedInProgramIncrementException
     * @throws FeatureHasPlannedUserStoryException
     * @throws FeatureNotFoundException
     * @throws InvalidFeatureIdInProgramIncrementException
     * @throws NotAllowedToPrioritizeException
     * @throws ProgramIncrementNotFoundException
     * @throws ProgramTrackerException
     * @throws RemoveFeatureException
     * @throws FeatureException
     * @throws ProgramIncrementHasNoProgramException
     * @throws AddOrOrderMustBeSetException
     * @throws ProjectIsNotAProgramException
     * @throws ProgramAccessException
     */
    public function modifyContent(int $program_increment_id, ContentChange $content_change, UserIdentifier $user): void;
}
