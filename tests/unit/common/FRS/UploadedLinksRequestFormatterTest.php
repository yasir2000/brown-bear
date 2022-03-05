<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\FRS;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

final class UploadedLinksRequestFormatterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItExtractsOneArrayFromLinksProvidedInRequest(): void
    {
        $request = \Mockery::spy(\HTTPRequest::class);
        $request->shouldReceive('get')->with('uploaded-link-name')->andReturns(['test', '']);
        $request->shouldReceive('get')->with('uploaded-link')->andReturns(['http://example.com', 'ftp://example.com']);
        $request->shouldReceive('validArray')->andReturns(true);

        $formatter      = new UploadedLinksRequestFormatter();
        $expected_links = [
            ['link' => 'http://example.com', 'name' => 'test'],
            ['link' => 'ftp://example.com', 'name' => ''],
        ];

        $this->assertSame($expected_links, $formatter->formatFromRequest($request));
    }

    public function testItThrowsAnExceptionWhenRequestDoesNotProvideCorrectInput(): void
    {
        $request = \Mockery::spy(\HTTPRequest::class);
        $request->shouldReceive('get')->with('uploaded-link-name')->andReturns(['test']);
        $request->shouldReceive('get')->with('uploaded-link')->andReturns(['http://example.com', 'https://example.com']);
        $request->shouldReceive('validArray')->andReturns(true);

        $this->expectException('Tuleap\FRS\UploadedLinksInvalidFormException');
        $formatter = new UploadedLinksRequestFormatter();
        $formatter->formatFromRequest($request);
    }

    public function testItDoesNotAcceptInvalidLinks(): void
    {
        $request = \Mockery::spy(\HTTPRequest::class);
        $request->shouldReceive('get')->with('uploaded-link-name')->andReturns(['invalid']);
        $request->shouldReceive('get')->with('uploaded-link')->andReturns(['example.com']);
        $request->shouldReceive('validArray')->andReturns(true);

        $formatter = new UploadedLinksRequestFormatter();

        $this->expectException('Tuleap\FRS\UploadedLinksInvalidFormException');
        $formatter->formatFromRequest($request);
    }

    public function testItDoesNotEmptyLinks(): void
    {
        $request = \Mockery::spy(\HTTPRequest::class);
        $request->shouldReceive('get')->with('uploaded-link-name')->andReturns([""]);
        $request->shouldReceive('get')->with('uploaded-link')->andReturns([""]);
        $request->shouldReceive('validArray')->andReturns(true);

        $formatter      = new UploadedLinksRequestFormatter();
        $expected_links = [];

        $this->assertSame($expected_links, $formatter->formatFromRequest($request));
    }
}
