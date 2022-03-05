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

namespace Tuleap\PullRequest\InlineComment;

use Tuleap\DB\DataAccessObject;

class Dao extends DataAccessObject
{
    /**
     * @psalm-return array{id:int,pull_request_id:int,user_id:int,post_date:int,file_path:string,unidiff_offset:int,content:string,is_outdated:0|1}|null
     */
    public function searchByID(int $inline_comment_id): ?array
    {
        return $this->getDB()->row(
            'SELECT id, pull_request_id, user_id, post_date, file_path, unidiff_offset, content, is_outdated
            FROM plugin_pullrequest_inline_comments
            WHERE id = ?',
            $inline_comment_id
        );
    }

    public function searchUpToDateByFilePath($pull_request_id, $file_path)
    {
        $sql = 'SELECT * FROM plugin_pullrequest_inline_comments
                WHERE pull_request_id=?
                AND file_path=? AND is_outdated=false';

        return $this->getDB()->run($sql, $pull_request_id, $file_path);
    }

    public function searchUpToDateByPullRequestId($pull_request_id)
    {
        $sql = 'SELECT * FROM plugin_pullrequest_inline_comments
                WHERE pull_request_id=? AND is_outdated=false';

        return $this->getDB()->run($sql, $pull_request_id);
    }

    public function searchAllByPullRequestId($pull_request_id)
    {
        $sql = 'SELECT SQL_CALC_FOUND_ROWS *
                FROM plugin_pullrequest_inline_comments
                WHERE pull_request_id = ?';

        return $this->getDB()->run($sql, $pull_request_id);
    }

    public function insert($pull_request_id, $user_id, $file_path, $post_date, $unidiff_offset, $content, $position): int
    {
        $this->getDB()->insert(
            'plugin_pullrequest_inline_comments',
            [
                'pull_request_id' => $pull_request_id,
                'user_id'         => $user_id,
                'file_path'       => $file_path,
                'post_date'       => $post_date,
                'unidiff_offset'  => $unidiff_offset,
                'content'         => $content,
                'position'        => $position,
            ]
        );

        return (int) $this->getDB()->lastInsertId();
    }

    public function updateComment($comment_id, $unidiff_offset, $is_outdated)
    {
        $sql = 'UPDATE plugin_pullrequest_inline_comments
            SET unidiff_offset=?, is_outdated=?
            WHERE id=?';

        $this->getDB()->run($sql, $unidiff_offset, $is_outdated, $comment_id);
    }
}
