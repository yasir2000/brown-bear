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

namespace Tuleap\ProjectOwnership\ProjectOwner;

use Tuleap\DB\DBTransactionExecutor;

class ProjectOwnerUpdater
{
    /**
     * @var ProjectOwnerDAO
     */
    private $dao;
    /**
     * @var DBTransactionExecutor
     */
    private $transaction_executor;

    public function __construct(ProjectOwnerDAO $dao, DBTransactionExecutor $transaction_executor)
    {
        $this->dao                  = $dao;
        $this->transaction_executor = $transaction_executor;
    }

    public function updateProjectOwner(\Project $project, \PFUser $new_project_owner)
    {
        $this->transaction_executor->execute(
            function () use ($project, $new_project_owner) {
                if (! $new_project_owner->isAdmin($project->getID())) {
                    throw new OnlyProjectAdministratorCanBeSetAsProjectOwnerException($new_project_owner);
                }
                $this->dao->save($project->getID(), $new_project_owner->getId());
            }
        );
    }
}
