<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

use Tuleap\PullRequest\GitReference\GitPullRequestReference;
use Tuleap\PullRequest\PullRequest;
use Tuleap\REST\JsonCast;
use GitRepository;
use Codendi_HTMLPurifier;

class PullRequestRepresentation extends PullRequestMinimalRepresentation
{
    public const ROUTE = parent::ROUTE;

    public const COMMENTS_ROUTE = 'comments';
    public const INLINE_ROUTE   = 'inline-comments';
    public const LABELS_ROUTE   = 'labels';
    public const FILES_ROUTE    = 'files';
    public const DIFF_ROUTE     = 'file_diff';
    public const TIMELINE_ROUTE = 'timeline';

    public const STATUS_ABANDON = 'abandon';
    public const STATUS_MERGE   = 'merge';
    public const STATUS_REVIEW  = 'review';

    public const NO_FASTFORWARD_MERGE = 'no_fastforward';
    public const FASTFORWARD_MERGE    = 'fastforward';
    public const CONFLICT_MERGE       = 'conflict';
    public const UNKNOWN_MERGE        = 'unknown-merge-status';

    /**
     * @var string {@type string}
     */
    public $description;

    /**
     * @var string {@type string}
     */
    public $reference_src;

    /**
     * @var string {@type string}
     */
    public $reference_dest;

    /**
     * @var string
     */
    public $head_reference;

    /**
     * @var string {@type string}
     */
    public $status;

    /**
     * @var array {@type array}
     */
    public $resources;

    /**
     * @var bool {@type bool}
     */
    public $user_can_merge;

    /**
     * @var bool {@type bool}
     */
    public $user_can_abandon;

    /**
     * @var bool {@type bool}
     */
    public $user_can_update_labels;

    /**
     * @var string {@type string}
     */
    public $merge_status;

    /**
     * @var array {@type PullRequestShortStatRepresentation}
     */
    public $short_stat;

    /**
     * @var string {@type string}
     */
    public $last_build_status;

    /**
     * @var string {@type string}
     */
    public $last_build_date;

    /**
     * @var string {@type string}
     */
    public $raw_title;

    /**
     * @var string {@type string}
     */
    public $raw_description;

    public function build(
        PullRequest $pull_request,
        GitRepository $repository,
        GitRepository $repository_dest,
        GitPullRequestReference $git_reference,
        $user_can_merge,
        $user_can_abandon,
        $user_can_update_labels,
        $last_build_status_name,
        $last_build_date,
        PullRequestShortStatRepresentation $pr_short_stat_representation,
    ) {
        $this->buildMinimal($pull_request, $repository, $repository_dest);

        $project_id        = $repository->getProjectId();
        $purifier          = Codendi_HTMLPurifier::instance();
        $this->description = $purifier->purify($pull_request->getDescription(), CODENDI_PURIFIER_BASIC, $project_id);

        $this->reference_src  = $pull_request->getSha1Src();
        $this->reference_dest = $pull_request->getSha1Dest();
        $this->head_reference = $git_reference->getGitHeadReference();
        $this->status         = $this->expandStatusName($pull_request->getStatus());

        $this->last_build_status = $last_build_status_name;
        $this->last_build_date   = JsonCast::toDate($last_build_date);

        $this->user_can_update_labels = $user_can_update_labels;
        $this->user_can_merge         = $user_can_merge;
        $this->user_can_abandon       = $user_can_abandon;
        $this->merge_status           = $this->expandMergeStatusName($pull_request->getMergeStatus());

        $this->short_stat = $pr_short_stat_representation;

        $this->raw_title       = $pull_request->getTitle();
        $this->raw_description = $pull_request->getDescription();

        $this->resources = [
            self::COMMENTS_ROUTE => [
                'uri' => $this->uri . '/' . self::COMMENTS_ROUTE,
            ],
            self::INLINE_ROUTE => [
                'uri' => $this->uri . '/' . self::INLINE_ROUTE,
            ],
            self::LABELS_ROUTE => [
                'uri' => $this->uri . '/' . self::LABELS_ROUTE,
            ],
            self::FILES_ROUTE => [
                'uri' => $this->uri . '/' . self::FILES_ROUTE,
            ],
            self::DIFF_ROUTE => [
                'uri' => $this->uri . '/' . self::DIFF_ROUTE,
            ],
            self::TIMELINE_ROUTE => [
                'uri' => $this->uri . '/' . self::TIMELINE_ROUTE,
            ],
        ];
    }

    private function expandStatusName($status_acronym)
    {
        $status_name = [
            PullRequest::STATUS_ABANDONED => self::STATUS_ABANDON,
            PullRequest::STATUS_MERGED    => self::STATUS_MERGE,
            PullRequest::STATUS_REVIEW    => self::STATUS_REVIEW,
        ];

        return $status_name[$status_acronym];
    }

    private function expandMergeStatusName($merge_status_acronym)
    {
        $status_name = [
            PullRequest::NO_FASTFORWARD_MERGE => self::NO_FASTFORWARD_MERGE,
            PullRequest::FASTFORWARD_MERGE    => self::FASTFORWARD_MERGE,
            PullRequest::CONFLICT_MERGE       => self::CONFLICT_MERGE,
            PullRequest::UNKNOWN_MERGE        => self::UNKNOWN_MERGE,
        ];

        return $status_name[$merge_status_acronym];
    }
}
