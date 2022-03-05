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
 *
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Artifact;

use Cocur\Slugify\SlugifyInterface;
use Tuleap\Gitlab\Artifact\Action\CreateBranchPrefixDao;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Tracker\Artifact\Artifact;

class BranchNameCreatorFromArtifact
{
    private SlugifyInterface $slugify;
    private CreateBranchPrefixDao $create_branch_prefix_dao;

    public function __construct(SlugifyInterface $slugify, CreateBranchPrefixDao $create_branch_prefix_dao)
    {
        $this->slugify                  = $slugify;
        $this->create_branch_prefix_dao = $create_branch_prefix_dao;
    }

    public function getBaseBranchName(Artifact $artifact): string
    {
        return sprintf('tuleap-%d%s', $artifact->getId(), $this->getSlugifiedArtifactTitle($artifact));
    }

    public function getFullBranchName(Artifact $artifact, GitlabRepositoryIntegration $integration): string
    {
        $prefix = $this->create_branch_prefix_dao->searchCreateBranchPrefixForIntegration($integration->getId());

        return $prefix . $this->getBaseBranchName($artifact);
    }

    private function getSlugifiedArtifactTitle(Artifact $artifact): string
    {
        $artifact_title = $artifact->getTitle();
        if ($artifact_title === null || $artifact_title === '') {
            return '';
        }

        return '-' . $this->slugify->slugify($artifact_title, '-');
    }
}
