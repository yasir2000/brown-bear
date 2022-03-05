<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\SVN\Repository;

use Tuleap\SVN\Dao;

final class SvnCoreAccess
{
    private const CORE_VIEWVC_URI_BASE_PATH = '/svn/viewvc.php/';

    private const ADMIN_URI_MATCH = [
        'general_settings' => 'settings',
        'immutable_tags'   => 'display-immutable-tag',
        'access_control'   => 'access-control',
        'notification'     => 'settings',
    ];

    /**
     * @var Dao
     */
    private $dao;

    public function __construct(Dao $dao)
    {
        $this->dao = $dao;
    }

    public function process(\Tuleap\SVN\SvnCoreAccess $svn_core_access): void
    {
        $repo_id = $this->dao->getCoreRepositoryId($svn_core_access->project);
        if ($repo_id !== null && ($uri = $this->getMatchingUri($svn_core_access, $repo_id))) {
            $svn_core_access->setRedirectUri($uri);
        }
    }

    private function getMatchingUri(\Tuleap\SVN\SvnCoreAccess $svn_core_access, int $repo_id): ?string
    {
        if ($svn_core_access->requested_uri === \SVN_SOAPServer::FAKE_URL) {
            return 'access forbidden';
        }
        $url = parse_url($svn_core_access->requested_uri);
        if (isset($url['path'], $url['query']) && $url['path'] === '/svn/admin/') {
            parse_str($url['query'], $query);
            return $this->getMatchingUriAdmin($query, $svn_core_access, $repo_id);
        }
        if (isset($url['path'], $url['query']) && strpos($url['path'], self::CORE_VIEWVC_URI_BASE_PATH) === 0) {
            return $this->getMatchingUriViewVc($svn_core_access, $url['path'], $url['query']);
        }
        if (isset($url['path']) && $url['path'] === '/svn/') {
            return $this->getMatchingUriView($svn_core_access, $repo_id);
        }
        return null;
    }

    private function getMatchingUriView(\Tuleap\SVN\SvnCoreAccess $svn_core_access, int $repo_id): string
    {
        return (CoreRepository::buildActiveRepository($svn_core_access->project, $repo_id))->getHtmlPath();
    }

    private function getMatchingUriAdmin(array $query, \Tuleap\SVN\SvnCoreAccess $svn_core_access, int $repo_id): ?string
    {
        if (isset($query['func'], self::ADMIN_URI_MATCH[$query['func']])) {
            return SVN_BASE_URL . '/?' . http_build_query(
                [
                    'group_id' => (int) $svn_core_access->project->getID(),
                    'action'   => self::ADMIN_URI_MATCH[$query['func']],
                    'repo_id'  => $repo_id,
                ]
            );
        }
        return SVN_BASE_URL . '/?' . http_build_query(
            [
                'group_id' => (int) $svn_core_access->project->getID(),
                'action'   => 'settings',
                'repo_id'  => $repo_id,
            ]
        );
    }

    private function getMatchingUriViewVc(\Tuleap\SVN\SvnCoreAccess $svn_core_access, string $path, string $query): string
    {
        $directory = substr($path, strlen(self::CORE_VIEWVC_URI_BASE_PATH));
        return SVN_BASE_URL . '/' . $directory . '?' . $query;
    }
}
