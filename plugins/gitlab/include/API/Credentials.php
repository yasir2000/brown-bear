<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\API;

use Tuleap\Gitlab\Repository\Token\IntegrationApiToken;

class Credentials
{
    /**
     * @var string
     */
    private $gitlab_server_url;

    /**
     * @var IntegrationApiToken
     */
    private $bot_api_token;

    public function __construct(string $gitlab_server_url, IntegrationApiToken $bot_api_token)
    {
        $this->gitlab_server_url = $gitlab_server_url;
        $this->bot_api_token     = $bot_api_token;
    }

    public function getGitlabServerUrl(): string
    {
        return $this->gitlab_server_url;
    }

    public function getBotApiToken(): IntegrationApiToken
    {
        return $this->bot_api_token;
    }
}
