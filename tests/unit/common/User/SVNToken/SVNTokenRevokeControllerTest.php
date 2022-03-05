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

use Feedback;
use Mockery as M;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\LayoutInspector;
use Tuleap\Test\Builders\UserTestBuilder;

final class SVNTokenRevokeControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use M\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private $csrf_token;
    private $svn_token_handler;
    private $controller;

    protected function setUp(): void
    {
        $this->csrf_token        = M::mock(\CSRFSynchronizerToken::class);
        $this->svn_token_handler = M::mock(\SVN_TokenHandler::class);

        $this->controller = new SVNTokenRevokeController($this->csrf_token, $this->svn_token_handler);
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
        $this->svn_token_handler->shouldReceive('deleteSVNTokensForUser');

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser(UserTestBuilder::aUser()->withId(120)->build())->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItDeletesSelectedToken(): void
    {
        $user = UserTestBuilder::aUser()->withId(120)->build();
        $this->csrf_token->shouldReceive('check');

        $this->svn_token_handler->shouldReceive('deleteSVNTokensForUser')->with($user, ['2', '5'])->once()->andReturnTrue();

        $layout_inspector = new LayoutInspector();

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($user)->withParam('svn-tokens-selected', ['2', '5'])->build(),
            LayoutBuilder::buildWithInspector($layout_inspector),
            []
        );

        $feedback = $layout_inspector->getFeedback();
        $this->assertCount(1, $feedback);
        $this->assertEquals(Feedback::INFO, $feedback[0]['level']);
        $this->assertEquals('/account/keys-tokens', $layout_inspector->getRedirectUrl());
    }


    public function testItFailsAtDeletingSelectedToken(): void
    {
        $user = UserTestBuilder::aUser()->withId(120)->build();
        $this->csrf_token->shouldReceive('check');

        $this->svn_token_handler->shouldReceive('deleteSVNTokensForUser')->andReturnFalse();

        $layout_inspector = new LayoutInspector();

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($user)->withParam('svn-tokens-selected', ['2', '5'])->build(),
            LayoutBuilder::buildWithInspector($layout_inspector),
            []
        );

        $feedback = $layout_inspector->getFeedback();
        $this->assertCount(1, $feedback);
        $this->assertEquals(Feedback::ERROR, $feedback[0]['level']);
        $this->assertEquals('/account/keys-tokens', $layout_inspector->getRedirectUrl());
    }
}
