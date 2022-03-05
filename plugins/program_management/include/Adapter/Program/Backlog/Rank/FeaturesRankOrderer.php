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
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\Rank;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureCanNotBeRankedWithItselfException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Rank\OrderFeatureRank;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\FeaturesToReorder;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;

final class FeaturesRankOrderer implements OrderFeatureRank
{
    public function __construct(private \Tracker_Artifact_PriorityManager $priority_manager)
    {
        $this->priority_manager->enableExceptionsOnError();
    }

    /**
     * @throws FeatureCanNotBeRankedWithItselfException
     */
    public function reorder(FeaturesToReorder $order, string $context_id, ProgramIdentifier $program): void
    {
        try {
            if ($order->isBefore()) {
                $this->priority_manager->moveListOfArtifactsBefore(
                    $order->getIds(),
                    $order->getComparedTo(),
                    $context_id,
                    $program->getID()
                );

                return;
            }

            $this->priority_manager->moveListOfArtifactsAfter(
                $order->getIds(),
                $order->getComparedTo(),
                $context_id,
                $program->getID()
            );
        } catch (\Tracker_Artifact_Exception_CannotRankWithMyself $e) {
            throw new FeatureCanNotBeRankedWithItselfException($e);
        }
    }
}
