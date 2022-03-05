<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Git\Events;

use GitRepository;
use Tuleap\Event\Dispatchable;

class AfterRepositoryForked implements Dispatchable
{
    public const NAME = 'afterRepositoryForked';

    /**
     * @var GitRepository
     */
    private $base_repository;

    /**
     * @var GitRepository
     */
    private $forked_repository;

    public function __construct(GitRepository $base_repository, GitRepository $forked_repository)
    {
        $this->base_repository   = $base_repository;
        $this->forked_repository = $forked_repository;
    }

    /**
     * @return GitRepository
     */
    public function getBaseRepository()
    {
        return $this->base_repository;
    }

    /**
     * @return GitRepository
     */
    public function getForkedRepository()
    {
        return $this->forked_repository;
    }
}
