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

namespace Tuleap\PullRequest;

use ParagonIE\EasyDB\EasyStatement;
use Tuleap\DB\DataAccessObject;
use Tuleap\PullRequest\Criterion\ISearchOnStatus;

class Dao extends DataAccessObject
{
    public function searchByPullRequestId($pull_request_id)
    {
        $sql = 'SELECT *
                FROM plugin_pullrequest_review
                WHERE id = ?';

        return $this->getDB()->row($sql, $pull_request_id);
    }

    public function searchByReferences($repo_src_id, $sha1_src, $repo_dest_id, $sha1_dest)
    {
        $sql = 'SELECT *
                FROM plugin_pullrequest_review
                WHERE repository_id = ?
                  AND sha1_src = ?
                  AND repo_dest_id = ?
                  AND sha1_dest = ?';

        return $this->getDB()->row($sql, $repo_src_id, $sha1_src, $repo_dest_id, $sha1_dest);
    }

    public function searchRepositoriesWithOpenPullRequests(array $repository_ids)
    {
        $ids_stmt = EasyStatement::open()->in('?*', $repository_ids);

        $sql = "SELECT repository_id, repo_dest_id
                FROM plugin_pullrequest_review
                WHERE status = ?
                  AND (repository_id IN ($ids_stmt) OR repo_dest_id IN ($ids_stmt))";
        return $this->getDB()->safeQuery($sql, array_merge([PullRequest::STATUS_REVIEW], $ids_stmt->values(), $ids_stmt->values()));
    }

    public function searchNbOfOpenedPullRequestsForRepositoryId($repository_id)
    {
        $sql = 'SELECT count(*) AS open_pr FROM plugin_pullrequest_review
                WHERE (repository_id= ? OR repo_dest_id = ?) AND status= ?';
        return $this->getDB()->single($sql, [$repository_id, $repository_id, PullRequest::STATUS_REVIEW]);
    }

    public function searchOpenedBySourceBranch($repository_id, $branch_name)
    {
        $sql = 'SELECT * FROM plugin_pullrequest_review
                WHERE repository_id=? AND branch_src=? AND status=?';
        return $this->getDB()->run($sql, $repository_id, $branch_name, PullRequest::STATUS_REVIEW);
    }

    public function searchOpenedByDestinationBranch($repository_id, $branch_name)
    {
        $sql = 'SELECT * FROM plugin_pullrequest_review
                WHERE repo_dest_id=? AND branch_dest=? AND status=?';
        return $this->getDB()->run($sql, $repository_id, $branch_name, PullRequest::STATUS_REVIEW);
    }

    public function searchNbOfPullRequestsByStatusForRepositoryId($repository_id)
    {
        $sql = 'SELECT status, COUNT(*) as nb
                FROM plugin_pullrequest_review
                WHERE repository_id = ?
                   OR repo_dest_id = ?
                GROUP BY status';

        return $this->getDB()->run($sql, $repository_id, $repository_id);
    }

    public function create(
        $repository_id,
        $title,
        $description,
        $user_id,
        $creation_date,
        $branch_src,
        $sha1_src,
        $repo_dest_id,
        $branch_dest,
        $sha1_dest,
        $merge_status,
    ) {
        $this->getDB()->insert(
            'plugin_pullrequest_review',
            [
                'repository_id' => $repository_id,
                'title'         => $title,
                'description'   => $description,
                'user_id'       => $user_id,
                'creation_date' => $creation_date,
                'branch_src'    => $branch_src,
                'sha1_src'      => $sha1_src,
                'repo_dest_id'  => $repo_dest_id,
                'branch_dest'   => $branch_dest,
                'sha1_dest'     => $sha1_dest,
                'merge_status'  => $merge_status,
            ]
        );

        return $this->getDB()->lastInsertId();
    }

    public function updateSha1Src($pull_request_id, $sha1_src)
    {
        $this->getDB()->run('UPDATE plugin_pullrequest_review SET sha1_src=? WHERE id=?', $sha1_src, $pull_request_id);
    }

    public function updateSha1Dest($pull_request_id, $sha1_dest)
    {
        $this->getDB()->run('UPDATE plugin_pullrequest_review SET sha1_dest=? WHERE id=?', $sha1_dest, $pull_request_id);
    }

    public function updateMergeStatus($pull_request_id, $merge_status)
    {
        $sql = 'UPDATE plugin_pullrequest_review SET merge_status=? WHERE id=?';
        $this->getDB()->run($sql, $merge_status, $pull_request_id);
    }

    public function getPaginatedPullRequests(
        $repository_id,
        ISearchOnStatus $criterion,
        $limit,
        $offset,
    ) {
        $where_status_statement = $this->getStatusStatements($criterion);

        $sql = "SELECT SQL_CALC_FOUND_ROWS *
                FROM plugin_pullrequest_review
                WHERE (repository_id = ? OR repo_dest_id = ?)
                AND $where_status_statement
                LIMIT ?
                OFFSET ?";

        $parameters   =  array_merge([$repository_id, $repository_id], $where_status_statement->values());
        $parameters[] = $limit;
        $parameters[] = $offset;

        return $this->getDB()->safeQuery($sql, $parameters);
    }

    /**
     * @return EasyStatement
     */
    private function getStatusStatements(ISearchOnStatus $criterion)
    {
        $statement = EasyStatement::open();

        if ($criterion->shouldRetrieveOpenPullRequests() && $criterion->shouldRetrieveClosedPullRequests()) {
            return $statement;
        }

        if ($criterion->shouldRetrieveOpenPullRequests()) {
            $statement->andIn('status IN (?*)', [PullRequest::STATUS_REVIEW]);
        }

        if ($criterion->shouldRetrieveClosedPullRequests()) {
            $statement->andIn('status IN (?*)', [PullRequest::STATUS_ABANDONED, PullRequest::STATUS_MERGED]);
        }

        return $statement;
    }

    public function markAsAbandoned($pull_request_id)
    {
        $sql = 'UPDATE plugin_pullrequest_review
                SET status = ?
                WHERE id = ?';

        $this->getDB()->run($sql, PullRequest::STATUS_ABANDONED, $pull_request_id);
    }

    public function markAsMerged($pull_request_id)
    {
        $sql = 'UPDATE plugin_pullrequest_review
                SET status = ?
                WHERE id = ?';

        $this->getDB()->run($sql, PullRequest::STATUS_MERGED, $pull_request_id);
    }

    public function updateTitleAndDescription($pull_request_id, $new_title, $new_description)
    {
        $sql = 'UPDATE plugin_pullrequest_review
                SET title = ?,
                    description = ?
                WHERE id = ?';

        $this->getDB()->run($sql, $new_title, $new_description, $pull_request_id);
    }

    public function deleteAllPullRequestsOfRepository($repository_id)
    {
        $sql = 'DELETE pr, label, comments, inline, event
                FROM plugin_pullrequest_review AS pr
                    LEFT JOIN plugin_pullrequest_label AS label ON (
                        pr.id = label.pull_request_id
                    )
                    LEFT JOIN plugin_pullrequest_comments AS comments ON (
                        pr.id = comments.pull_request_id
                    )
                    LEFT JOIN plugin_pullrequest_inline_comments AS inline ON (
                        pr.id = inline.pull_request_id
                    )
                    LEFT JOIN plugin_pullrequest_timeline_event AS event ON (
                        pr.id = event.pull_request_id
                    )
                WHERE pr.repository_id = ?';

        $this->getDB()->run($sql, $repository_id);
    }
}
