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

namespace Tuleap\OAuth2ServerCore\Grant\AuthorizationCode;

use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\OAuth2ServerCore\OAuth2ServerException;

final class OAuth2AuthCodeExpiredException extends \RuntimeException implements OAuth2ServerException
{
    public function __construct(SplitToken $auth_code)
    {
        parent::__construct(sprintf('The OAuth2 authorization code #%d has expired', $auth_code->getID()));
    }
}
