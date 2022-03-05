<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Redirections;

use Tuleap\ProgramManagement\Domain\Redirections\ProgramRedirectionParameters;
use Tuleap\Test\PHPUnit\TestCase;

final class ProgramRedirectionParametersProxyTest extends TestCase
{
    public function testItBuildsAProxyAroundProgramApp(): void
    {
        $request = $this->buildCodendiRequest();

        $proxy = ProgramRedirectionParametersProxy::buildFromCodendiRequest($request);
        self::assertSame(ProgramRedirectionParameters::REDIRECT_AFTER_CREATE_ACTION, $proxy->getValue());
        self::assertTrue($proxy->needsRedirectionAfterCreate());
        self::assertFalse($proxy->needsRedirectionAfterUpdate());
    }

    private function buildCodendiRequest(): \Codendi_Request
    {
        return new \Codendi_Request(
            [
                ProgramRedirectionParameters::FLAG => ProgramRedirectionParameters::REDIRECT_AFTER_CREATE_ACTION,
            ],
            null
        );
    }
}
