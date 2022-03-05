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

namespace Tuleap\PullRequest\MergeSetting;

use Tuleap\DB\DataAccessObject;

class MergeSettingDAO extends DataAccessObject
{
    public function getMergeSettingByRepositoryID($repository_id)
    {
        return $this->getDB()->row(
            'SELECT merge_commit_allowed FROM plugin_pullrequest_merge_setting WHERE repository_id = ?',
            $repository_id
        );
    }

    public function getMergeSettingByProjectID($project_id)
    {
        return $this->getDB()->row(
            'SELECT merge_commit_allowed FROM plugin_pullrequest_template_merge_setting WHERE project_id = ?',
            $project_id
        );
    }

    public function duplicateRepositoryMergeSettings($base_repository_id, $forked_repository_id)
    {
        $sql = "INSERT INTO plugin_pullrequest_merge_setting (repository_id, merge_commit_allowed)
                SELECT  ?, merge_commit_allowed
                FROM plugin_pullrequest_merge_setting
                WHERE repository_id = ?";

        return $this->getDB()->single($sql, [$forked_repository_id, $base_repository_id]);
    }

    public function inheritFromTemplate($repository_id, $project_id)
    {
        $sql = "INSERT INTO plugin_pullrequest_merge_setting (repository_id, merge_commit_allowed)
                SELECT  ?, merge_commit_allowed
                FROM plugin_pullrequest_template_merge_setting
                WHERE project_id = ?";

        $this->getDB()->single($sql, [$repository_id, $project_id]);
    }

    public function duplicateFromProjectTemplate($template_project_id, $project_id)
    {
        $sql = "INSERT INTO plugin_pullrequest_template_merge_setting (project_id, merge_commit_allowed)
                SELECT  ?, merge_commit_allowed
                FROM plugin_pullrequest_template_merge_setting
                WHERE project_id = ?";

        $this->getDB()->single($sql, [$project_id, $template_project_id]);
    }

    public function save($repository_id, $merge_commit_allowed)
    {
        $sql = "INSERT INTO plugin_pullrequest_merge_setting (repository_id, merge_commit_allowed)
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE merge_commit_allowed = ?";

        $this->getDB()->run($sql, $repository_id, $merge_commit_allowed, $merge_commit_allowed);
    }

    public function saveDefaultSettings($project_id, $merge_commit_allowed)
    {
        $sql = "INSERT INTO plugin_pullrequest_template_merge_setting (project_id, merge_commit_allowed)
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE merge_commit_allowed = ?";

        $this->getDB()->run($sql, $project_id, $merge_commit_allowed, $merge_commit_allowed);
    }
}
