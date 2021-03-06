<?php
/**
 * Copyright (c) BrownBear, 2020-Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard;

use Project;
use Tuleap\Event\Dispatchable;

final class BlockScrumAccess implements Dispatchable
{
    public const NAME = 'blockScrumAccess';

    /**
     * @var Project
     * @psalm-readonly
     */
    private $project;
    /**
     * @var bool
     */
    private $scrum_access_enabled = true;

    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    /**
     * @psalm-mutation-free
     */
    public function getProject(): Project
    {
        return $this->project;
    }

    /**
     * @psalm-mutation-free
     */
    public function isScrumAccessEnabled(): bool
    {
        return $this->scrum_access_enabled;
    }

    public function disableScrumAccess(): void
    {
        $this->scrum_access_enabled = false;
    }
}
