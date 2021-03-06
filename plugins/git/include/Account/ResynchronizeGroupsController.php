<?php
/**
 * Copyright (c) BrownBear, 2020-Present. All Rights Reserved.
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

namespace Tuleap\Git\Account;

use CSRFSynchronizerToken;
use Feedback;
use Git_Driver_Gerrit_MembershipManager;
use Git_RemoteServer_GerritServerFactory;
use HTTPRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;

class ResynchronizeGroupsController implements DispatchableWithRequest
{
    /**
     * @var Git_RemoteServer_GerritServerFactory
     */
    private $gerrit_server_factory;
    /**
     * @var Git_Driver_Gerrit_MembershipManager
     */
    private $membership_manager;
    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf_token;

    public function __construct(CSRFSynchronizerToken $csrf_token, Git_RemoteServer_GerritServerFactory $gerrit_server_factory, Git_Driver_Gerrit_MembershipManager $membership_manager)
    {
        $this->csrf_token            = $csrf_token;
        $this->gerrit_server_factory = $gerrit_server_factory;
        $this->membership_manager    = $membership_manager;
    }

    /**
     * @inheritDoc
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $user = $request->getCurrentUser();
        if ($user->isAnonymous()) {
            throw new ForbiddenException();
        }

        $this->csrf_token->check(AccountGerritController::URL);

        if (! $this->gerrit_server_factory->hasRemotesSetUp()) {
            throw new ForbiddenException();
        }

        $this->membership_manager->addUserToAllTheirGroups($user);
        $layout->addFeedback(Feedback::INFO, dgettext('tuleap-git', 'Command sent to remote gerrit servers'));

        $layout->redirect(AccountGerritController::URL);
    }
}
