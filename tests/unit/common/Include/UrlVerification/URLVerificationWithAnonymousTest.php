<?php
/**
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

declare(strict_types=1);

namespace Tuleap;

use ForgeAccess;
use ForgeConfig;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

final class URLVerificationWithAnonymousTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;
    use ForgeConfigSandbox;

    private $urlVerification;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\PFUser
     */
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $em = \Mockery::spy(\EventManager::class);

        $this->user = \Mockery::mock(\PFUser::class);

        $this->urlVerification = \Mockery::mock(\URLVerification::class)->makePartial(
        )->shouldAllowMockingProtectedMethods();
        $this->urlVerification->shouldReceive('getEventManager')->andReturns($em);
        $this->urlVerification->shouldReceive('getCurrentUser')->andReturns($this->user);
    }

    public function testVerifyRequestAnonymousWhenScriptException(): void
    {
        $server = [
            'SERVER_NAME' => 'example.com',
            'SCRIPT_NAME' => '/account/login.php',
        ];
        $this->user->shouldReceive('isAnonymous')->andReturns(true);

        $this->urlVerification->verifyRequest($server);
        $chunks = $this->urlVerification->getUrlChunks();

        $this->assertFalse(isset($chunks['script']));
    }

    public function testVerifyRequestAnonymousWhenAllowed(): void
    {
        $server = [
            'SERVER_NAME' => 'example.com',
            'SCRIPT_NAME' => '',
            'REQUEST_URI' => '/',
        ];
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);

        $this->user->shouldReceive('isAnonymous')->andReturns(true);

        $this->urlVerification->verifyRequest($server);
        $chunks = $this->urlVerification->getUrlChunks();

        $this->assertFalse(isset($chunks['script']));
    }

    public function testVerifyRequestAuthenticatedWhenAnonymousAllowed(): void
    {
        $server = [
            'SERVER_NAME' => 'example.com',
            'SCRIPT_NAME' => '',
        ];
        $this->user->shouldReceive('isAnonymous')->andReturns(false);

        $this->urlVerification->verifyRequest($server);
        $chunks = $this->urlVerification->getUrlChunks();

        $this->assertFalse(isset($chunks['script']));
    }

    public function testVerifyRequestAnonymousWhenNotAllowedAtRoot(): void
    {
        $server = [
            'SERVER_NAME' => 'example.com',
            'SCRIPT_NAME' => '',
            'REQUEST_URI' => '/',
        ];
        $this->user->shouldReceive('isAnonymous')->andReturns(true);

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);
        ForgeConfig::set('sys_default_domain', 'example.com');

        $this->urlVerification->verifyRequest($server);
        $chunks = $this->urlVerification->getUrlChunks();

        $this->assertEquals('/account/login.php?return_to=%2Fmy%2F', $chunks['script']);
    }

    public function testVerifyRequestAnonymousWhenNotAllowedWithScript(): void
    {
        $server = [
            'SERVER_NAME' => 'example.com',
            'SCRIPT_NAME' => '',
            'REQUEST_URI' => '/script/',
        ];
        $this->user->shouldReceive('isAnonymous')->andReturns(true);

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);
        ForgeConfig::set('sys_default_domain', 'example.com');

        $this->urlVerification->verifyRequest($server);
        $chunks = $this->urlVerification->getUrlChunks();

        $this->assertEquals('/account/login.php?return_to=%2Fscript%2F', $chunks['script']);
    }

    public function testVerifyRequestAnonymousWhenNotAllowedWithLightView(): void
    {
        $server = [
            'SERVER_NAME' => 'example.com',
            'SCRIPT_NAME' => '',
            'REQUEST_URI' => '/script?pv=2',
        ];
        $this->user->shouldReceive('isAnonymous')->andReturns(true);

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);
        ForgeConfig::set('sys_default_domain', 'example.com');

        $this->urlVerification->verifyRequest($server);
        $chunks = $this->urlVerification->getUrlChunks();

        $this->assertEquals('/account/login.php?return_to=%2Fscript%3Fpv%3D2&pv=2', $chunks['script']);
    }

    public function testVerifyRequestAuthenticatedWhenAnonymousNotAllowed(): void
    {
        $server = [
            'SERVER_NAME' => 'example.com',
            'SCRIPT_NAME' => '',
        ];
        $this->user->shouldReceive('isAnonymous')->andReturns(false);

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);

        $this->urlVerification->verifyRequest($server);
        $chunks = $this->urlVerification->getUrlChunks();

        $this->assertFalse(isset($chunks['script']));
    }
}
