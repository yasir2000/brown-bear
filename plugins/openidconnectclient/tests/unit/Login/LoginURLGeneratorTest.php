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

namespace Tuleap\OpenIDConnectClient\Login;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\OpenIDConnectClient\Provider\Provider;

final class LoginURLGeneratorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    private const BASE_URL    = '/base';
    private const PROVIDER_ID = 1;

    /**
     * @dataProvider dataProviderReturnTo
     */
    public function testGeneratesLoginURL(?string $return_to, string $expected_url): void
    {
        $login_url_generator = new LoginURLGenerator(self::BASE_URL);

        self::assertEquals($expected_url, $login_url_generator->getLoginURL(self::buildProvider(), $return_to));
    }

    public function dataProviderReturnTo(): array
    {
        return [
            'No return_to'           => [null, self::BASE_URL . '/login_to/' . self::PROVIDER_ID],
            'Empty return_to'        => ['', self::BASE_URL . '/login_to/' . self::PROVIDER_ID],
            'return_to with a value' => ['/my/', self::BASE_URL . '/login_to/' . self::PROVIDER_ID . '?return_to=%2Fmy%2F'],
        ];
    }

    private static function buildProvider(): Provider
    {
        $provider = \Mockery::mock(Provider::class);
        $provider->shouldReceive('getId')->andReturn(self::PROVIDER_ID);

        return $provider;
    }
}
