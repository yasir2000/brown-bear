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
 *
 */

namespace Tuleap\User\SVNToken;

use Mockery as M;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\Cryptography\Symmetric\EncryptionKey;
use Tuleap\Cryptography\Symmetric\SymmetricCrypto;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\LayoutInspector;
use Tuleap\Test\Builders\UserTestBuilder;

final class SVNTokenCreateControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use M\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private $csrf_token;
    private $svn_token_handler;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|KeyFactory
     */
    private $key_factory;
    private $controller;

    protected function setUp(): void
    {
        $this->csrf_token        = M::mock(\CSRFSynchronizerToken::class);
        $this->svn_token_handler = M::mock(\SVN_TokenHandler::class);
        $this->key_factory       = M::mock(KeyFactory::class);

        $this->controller = new SVNTokenCreateController($this->csrf_token, $this->svn_token_handler, $this->key_factory);
    }

    protected function tearDown(): void
    {
        unset($_SESSION);
    }

    public function testItForbidsAnonymous(): void
    {
        $this->expectException(ForbiddenException::class);

        $this->controller->process(
            HTTPRequestBuilder::get()->withAnonymousUser()->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItChecksCSRFToken(): void
    {
        $this->csrf_token->shouldReceive('check')->with('/account/keys-tokens')->once();
        $this->svn_token_handler->shouldReceive('generateSVNTokenForUser');

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser(UserTestBuilder::aUser()->withId(120)->build())->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItGeneratesTheKeyForUser(): void
    {
        $user = UserTestBuilder::aUser()->withId(120)->build();
        $this->csrf_token->shouldReceive('check');

        $key = new EncryptionKey(new ConcealedString(\random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES)));
        $this->key_factory->shouldReceive('getEncryptionKey')->andReturn($key);

        $this->svn_token_handler->shouldReceive('generateSVNTokenForUser')->once()->with($user, 'Some comment')->andReturn(new ConcealedString('tlp-tk-blabla'));

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($user)->withParam('svn-token-description', 'Some comment')->build(),
            LayoutBuilder::build(),
            []
        );

        $this->assertEquals('tlp-tk-blabla', SymmetricCrypto::decrypt($_SESSION['last_svn_token'], $key));
    }

    public function testKeyGenerationFails(): void
    {
        $user = UserTestBuilder::aUser()->withId(120)->build();
        $this->csrf_token->shouldReceive('check');

        $this->svn_token_handler->shouldReceive('generateSVNTokenForUser')->andReturnNull();

        $layout_inspector = new LayoutInspector();

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($user)->withParam('svn-token-description', 'Some comment')->build(),
            LayoutBuilder::buildWithInspector($layout_inspector),
            []
        );

        $feedback = $layout_inspector->getFeedback();
        $this->assertCount(1, $feedback);
        $this->assertEquals(\Feedback::ERROR, $feedback[0]['level']);

        $this->assertEquals('/account/keys-tokens', $layout_inspector->getRedirectUrl());
    }
}
