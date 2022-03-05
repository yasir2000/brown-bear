<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\Git\Repository\Settings;

use Feedback;
use HTTPRequest;
use Tuleap\Git\Repository\RepositoryFromRequestRetriever;
use Tuleap\Git\Webhook\WebhookDao;
use Valid_UInt;

class WebhookDeleteController extends WebhookController
{
    /**
     * @var WebhookDao
     */
    private $dao;

    public function __construct(RepositoryFromRequestRetriever $repository_retriever, WebhookDao $dao)
    {
        parent::__construct($repository_retriever);
        $this->dao = $dao;
    }

    public function removeWebhook(HTTPRequest $request)
    {
        $repository   = $this->getRepositoryUserCanAdministrate($request);
        $redirect_url = $this->getWebhookSettingsURL($repository);

        $this->checkCSRF($redirect_url);

        $webhook_id = $this->getId($request, $redirect_url);

        if ($this->dao->deleteByRepositoryIdAndWebhookId($repository->getId(), $webhook_id)) {
            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                dgettext('tuleap-git', 'Webhook removed')
            );
        } else {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-git', 'Error while removing the webhook :(')
            );
        }

        $GLOBALS['Response']->redirect($redirect_url);
    }

    private function getId(HTTPRequest $request, $redirect_url)
    {
        $valid_id = new Valid_UInt('webhook_id');
        $valid_id->required();
        if (! $request->valid($valid_id)) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-git', 'Empty required parameter(s)')
            );
            $GLOBALS['Response']->redirect($redirect_url);
        }

        return $request->get('webhook_id');
    }
}
