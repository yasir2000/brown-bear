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

namespace Tuleap\PullRequest\REST\v1;

use Git_Exec;
use GitRepositoryFactory;
use Tuleap\Git\GitPHP\Project;
use Tuleap\Git\REST\v1\GitCommitRepresentationBuilder;
use Tuleap\PullRequest\PullRequest;

class PullRequestsCommitRepresentationFactory
{
    /**
     * @var Git_Exec
     */
    private $git_exec;
    /**
     * @var Project
     */
    private $project;
    /**
     * @var GitRepositoryFactory
     */
    private $repository_factory;
    /**
     * @var GitCommitRepresentationBuilder
     */
    private $commit_representation_builder;

    public function __construct(
        Git_Exec $git_exec,
        Project $project,
        GitRepositoryFactory $repository_factory,
        GitCommitRepresentationBuilder $commit_representation_builder,
    ) {
        $this->git_exec                      = $git_exec;
        $this->project                       = $project;
        $this->repository_factory            = $repository_factory;
        $this->commit_representation_builder = $commit_representation_builder;
    }

    /**
     * @return PullRequestGitCommitRepresentationPartialCollection
     * @throws \Git_Command_Exception
     */
    public function getPullRequestCommits(PullRequest $pull_request, $limit, $offset)
    {
        $all_references = $this->git_exec->revList($pull_request->getSha1Dest(), $pull_request->getSha1Src());
        $total_size     = count($all_references);

        $all_references = array_slice($all_references, $offset, $limit);

        $repository_destination_id = $pull_request->getRepoDestId();
        $repository_destination    = $this->repository_factory->getRepositoryById($repository_destination_id);

        $commits = [];
        foreach ($all_references as $reference) {
            $commits[] = $this->project->GetCommit($reference);
        }

        $commit_representation_collection = $this->commit_representation_builder->buildCollection(
            $repository_destination,
            ...$commits
        );

        return new PullRequestGitCommitRepresentationPartialCollection(
            $commit_representation_collection->getWholeCollectionAsArray(),
            $total_size
        );
    }
}
