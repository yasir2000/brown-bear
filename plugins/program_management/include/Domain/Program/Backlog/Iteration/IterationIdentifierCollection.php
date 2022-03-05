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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\VerifyIsVisibleArtifact;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

/**
 * @psalm-immutable
 */
final class IterationIdentifierCollection
{
    /**
     * @param IterationIdentifier[] $iterations
     */
    private function __construct(private array $iterations)
    {
    }

    public function getIterations(): array
    {
        return $this->iterations;
    }

    public static function fromProgramIncrement(
        SearchIterations $iteration_searcher,
        VerifyIsVisibleArtifact $visibility_verifier,
        ProgramIncrementIdentifier $program_increment,
        UserIdentifier $user,
    ): self {
        $iterations = IterationIdentifier::buildCollectionFromProgramIncrement(
            $iteration_searcher,
            $visibility_verifier,
            $program_increment,
            $user
        );
        return new self($iterations);
    }
}
