<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\Tracker\Creation\JiraImporter;

use Closure;
use Psr\Log\LoggerInterface;
use Rule_Regexp;
use Tuleap\Cryptography\ConcealedString;
use Valid_LocalURI;

class ClientWrapperBuilder
{
    /**
     * @param Closure(JiraCredentials, LoggerInterface):JiraClient $build_wrapper
     */
    public function __construct(private Closure $build_wrapper)
    {
    }

    /**
     * @throws JiraConnectionException
     */
    public function buildFromRequest(\HTTPRequest $request, LoggerInterface $logger): JiraClient
    {
        $body = $request->getJsonDecodedBody();

        if (! isset($body->credentials)) {
            throw JiraConnectionException::credentialsKeyIsMissing();
        }

        if (
            ! isset($body->credentials->server_url)
            || ! isset($body->credentials->user_email)
            || ! isset($body->credentials->token)
        ) {
            throw JiraConnectionException::credentialsValuesAreMissing();
        }
        $jira_server = $body->credentials->server_url;
        $jira_user   = $body->credentials->user_email;
        $jira_token  = new ConcealedString($body->credentials->token);

        $valid_http = new Rule_Regexp(Valid_LocalURI::URI_REGEXP);
        if (! $valid_http->isValid($jira_server)) {
            throw JiraConnectionException::urlIsInvalid();
        }

        $jira_credentials = new JiraCredentials($jira_server, $jira_user, $jira_token);

        return $this->build($jira_credentials, $logger);
    }

    public function build(JiraCredentials $jira_credentials, LoggerInterface $logger): JiraClient
    {
        $fn = $this->build_wrapper;
        return $fn($jira_credentials, $logger);
    }
}
