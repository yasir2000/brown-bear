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
 */

declare(strict_types=1);

namespace Tuleap\Http\Response;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use Psr\Http\Message\ServerRequestInterface;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Server\NullServerRequest;

final class BinaryFileResponseBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    public function testFileResponseCanBeBuiltFromFilepath(): void
    {
        $builder = new BinaryFileResponseBuilder(HTTPFactoryBuilder::responseFactory(), HTTPFactoryBuilder::streamFactory());

        $directory = vfsStream::setup()->url();
        $file      = $directory . '/my_file';
        file_put_contents($file, 'ABCD');

        $request = Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getHeaderLine')->with('Range')->andReturn('');

        $response = $builder->fromFilePath($request, $file);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('ABCD', $response->getBody()->getContents());
        $this->assertEquals(filesize($file), (int) $response->getHeaderLine('Content-Length'));
        $this->assertEquals('bytes', $response->getHeaderLine('Accept-Ranges'));
        $this->assertTrue($response->hasHeader('Content-Type'));
        $this->assertEquals('private', $response->getHeaderLine('Cache-Control'));
        $this->assertEquals('no-cache', $response->getHeaderLine('Pragma'));
    }

    public function testFileResponseCanBeBuiltFromACallback(): void
    {
        $builder = new BinaryFileResponseBuilder(HTTPFactoryBuilder::responseFactory(), HTTPFactoryBuilder::streamFactory());

        $callback = static function (): void {
            echo 'Foo';
        };

        $response = $builder->fromCallback(Mockery::mock(ServerRequestInterface::class), $callback, 'archive.zip', 'application/zip');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertFalse($response->hasHeader('Content-Length'));
        $this->assertFalse($response->hasHeader('Accept-Ranges'));
        $this->assertEquals('application/zip', $response->getHeaderLine('Content-Type'));
        $this->assertEquals('private', $response->getHeaderLine('Cache-Control'));
        $this->assertEquals('no-cache', $response->getHeaderLine('Pragma'));
    }

    public function testResponseInstructsToNotCachePubliclyTheAnswer(): void
    {
        $builder = new BinaryFileResponseBuilder(HTTPFactoryBuilder::responseFactory(), HTTPFactoryBuilder::streamFactory());

        $file = vfsStream::setup()->url() . '/file';
        touch($file);

        $response = $builder->fromFilePath(new NullServerRequest(), $file);

        $this->assertEquals('private', $response->getHeaderLine('Cache-Control'));
        $this->assertEquals('no-cache', $response->getHeaderLine('Pragma'));
    }

    /**
     * @testWith [4, "bytes=0-", "bytes 0-3/4", "4"]
     *           [4, "bytes=1-", "bytes 1-3/4", "3"]
     *           [4, "bytes=0-0", "bytes 0-0/4", "1"]
     *           [4, "bytes=2-3", "bytes 2-3/4", "2"]
     */
    public function testPartialFileResponse(
        int $total_content_size,
        string $range_header,
        string $expected_content_range_header,
        string $expected_content_length_header,
    ): void {
        $builder = new BinaryFileResponseBuilder(HTTPFactoryBuilder::responseFactory(), HTTPFactoryBuilder::streamFactory());

        $directory = vfsStream::setup()->url();
        $file      = $directory . '/my_file';
        file_put_contents($file, str_repeat('A', $total_content_size));

        $request = Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getHeaderLine')->with('Range')->andReturn($range_header);

        $response = $builder->fromFilePath($request, $file);

        $this->assertEquals(206, $response->getStatusCode());
        $this->assertEquals($expected_content_range_header, $response->getHeaderLine('Content-Range'));
        $this->assertEquals($expected_content_length_header, $response->getHeaderLine('Content-Length'));
    }

    /**
     * @testWith ["xxxxxx=0-"]
     *           ["bytes=0-999999999999"]
     *           ["bytes=-0"]
     */
    public function testPartialFileResponseWithUnsupportedRangeHeader(string $range_header): void
    {
        $builder = new BinaryFileResponseBuilder(HTTPFactoryBuilder::responseFactory(), HTTPFactoryBuilder::streamFactory());

        $directory = vfsStream::setup()->url();
        $file      = $directory . '/my_file';
        file_put_contents($file, 'AAA');

        $request = Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getHeaderLine')->with('Range')->andReturn($range_header);

        $response = $builder->fromFilePath($request, $file);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertFalse($response->hasHeader('Content-Range'));
    }
}
