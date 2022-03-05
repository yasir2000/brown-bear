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
 */

declare(strict_types=1);

namespace Tuleap\OAuth2ServerCore\RefreshToken;

use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\OAuth2ServerCore\App\OAuth2App;
use Tuleap\OAuth2ServerCore\OAuth2ServerException;

final class OAuth2RefreshTokenDoesNotCorrespondToExpectedAppException extends \RuntimeException implements OAuth2ServerException
{
    public function __construct(SplitToken $refresh_token, OAuth2App $expected_app, int $refresh_token_app_id)
    {
        parent::__construct(
            sprintf(
                'The OAuth2 refresh token #%d is associated with app %d, expected app #%d',
                $refresh_token->getID(),
                $refresh_token_app_id,
                $expected_app->getId()
            )
        );
    }
}
