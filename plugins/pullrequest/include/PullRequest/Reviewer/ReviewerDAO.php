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

namespace Tuleap\PullRequest\Reviewer;

use ParagonIE\EasyDB\EasyDB;
use Tuleap\DB\DataAccessObject;

class ReviewerDAO extends DataAccessObject
{
    public function searchReviewers(int $pull_request_id): array
    {
        return $this->getDB()->run(
            'SELECT user.*
                       FROM plugin_pullrequest_reviewer_change_user
                       JOIN plugin_pullrequest_reviewer_change ON (plugin_pullrequest_reviewer_change.change_id = plugin_pullrequest_reviewer_change_user.change_id)
                       JOIN user ON (user.user_id = plugin_pullrequest_reviewer_change_user.user_id)
                       WHERE plugin_pullrequest_reviewer_change.pull_request_id = ?
                       GROUP BY user.user_id
                       HAVING SUM(IF(plugin_pullrequest_reviewer_change_user.is_removal = FALSE, 1, -1)) = 1',
            $pull_request_id
        );
    }

    public function setReviewers(
        int $pull_request_id,
        int $user_doing_the_change_id,
        int $change_timestamp,
        int ...$user_ids,
    ): ?int {
        return $this->getDB()->tryFlatTransaction(function (EasyDB $db) use (
            $pull_request_id,
            $user_doing_the_change_id,
            $change_timestamp,
            $user_ids
        ): ?int {
            $current_reviewer_ids = array_map(
                static function (array $user_row): int {
                    return $user_row['user_id'];
                },
                $this->searchReviewers($pull_request_id)
            );
            $added_reviewer_ids   = array_diff($user_ids, $current_reviewer_ids);
            $removed_reviewer_ids = array_diff($current_reviewer_ids, $user_ids);

            if (empty($added_reviewer_ids) && empty($removed_reviewer_ids)) {
                return null;
            }

            $change_id = (int) $db->insertReturnId(
                'plugin_pullrequest_reviewer_change',
                [
                    'pull_request_id' => $pull_request_id,
                    'user_id'         => $user_doing_the_change_id,
                    'change_date'     => $change_timestamp,
                ]
            );

            $change_user_rows = [];
            foreach ($added_reviewer_ids as $reviewer_id) {
                $change_user_rows[] = [
                    'change_id'  => $change_id,
                    'user_id'    => $reviewer_id,
                    'is_removal' => false,
                ];
            }
            foreach ($removed_reviewer_ids as $reviewer_id) {
                $change_user_rows[] = [
                    'change_id'  => $change_id,
                    'user_id'    => $reviewer_id,
                    'is_removal' => true,
                ];
            }
            $db->insertMany(
                'plugin_pullrequest_reviewer_change_user',
                $change_user_rows
            );

            return $change_id;
        });
    }
}
