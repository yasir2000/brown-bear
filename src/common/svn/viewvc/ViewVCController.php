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
 *
 */

declare(strict_types=1);

namespace Tuleap\SvnCore\ViewVC;

use EventManager;
use HTTPRequest;
use ProjectManager;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\SVN\SvnCoreAccess;
use Valid_String;

class ViewVCController implements DispatchableWithRequest
{
    public function __construct(private ViewVCProxy $viewvc_proxy)
    {
    }

    /**
     * Is able to process a request routed by FrontRouter
     *
     * @param array       $variables
     * @throws NotFoundException
     * @throws ForbiddenException
     * @return void
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        if (! user_isloggedin()) {
            throw new ForbiddenException();
        }
        $vRoot = new Valid_String('root');
        $vRoot->required();
        if (! $request->valid($vRoot)) {
            exit_no_group();
        }
        $root            = $request->get('root');
        $project_manager = ProjectManager::instance();
        $project         = $project_manager->getProjectByUnixName($root);
        if (! $project) {
            throw new NotFoundException();
        }

        $svn_core_access = EventManager::instance()->dispatch(new SvnCoreAccess($project, $_SERVER['REQUEST_URI'], $layout));
        $svn_core_access->redirect();

        $this->viewvc_proxy->displayContent($project, $request, $this->fixPathInfo($variables));
    }

    private function fixPathInfo(array $variables): string
    {
        if (isset($variables['path']) && $variables['path'] !== '') {
            return $this->addTrailingSlash($this->addLeadingSlash($variables['path']));
        }
        return '/';
    }

    private function addLeadingSlash(string $path): string
    {
        if ($path[0] !== '/') {
            return '/' . $path;
        }
        return $path;
    }

    private function addTrailingSlash(string $path): string
    {
        if (strrpos($path, "/") !== (strlen($path) - 1)) {
            return $path . '/';
        }
        return $path;
    }
}
