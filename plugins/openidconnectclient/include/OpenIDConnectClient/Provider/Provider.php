<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\OpenIDConnectClient\Provider;

interface Provider
{
    /**
     * @psalm-mutation-free
     */
    public function getId(): int;

    public function getName(): string;

    public function getClientId(): string;

    public function getClientSecret(): string;

    public function isUniqueAuthenticationEndpoint(): bool;

    public function getIcon(): string;

    public function getColor(): string;

    public function getAuthorizationEndpoint(): string;

    public function getTokenEndpoint(): string;

    public function getUserInfoEndpoint(): string;

    public function getJWKSEndpoint(): ?string;

    public function getRedirectUri(): string;
}
