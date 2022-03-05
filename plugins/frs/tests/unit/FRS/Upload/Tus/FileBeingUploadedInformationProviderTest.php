<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\FRS\Upload\Tus;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Psr\Http\Message\ServerRequestInterface;
use Tuleap\FRS\Upload\FileOngoingUploadDao;
use Tuleap\FRS\Upload\UploadPathAllocator;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\REST\RESTCurrentUserMiddleware;

class FileBeingUploadedInformationProviderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    public function testFileInformationCanBeProvided(): void
    {
        $path_allocator = new UploadPathAllocator();
        $dao            = \Mockery::mock(FileOngoingUploadDao::class);
        $current_user   = \Mockery::mock(\PFUser::class);
        $data_store     = new FileBeingUploadedInformationProvider($path_allocator, $dao);

        $dao->shouldReceive('searchFileOngoingUploadByIDUserIDAndExpirationDate')->andReturns(
            [
                'file_size' => 123456,
                'name'      => 'readme.md',
            ]
        );

        $item_id        = 12;
        $server_request = (new NullServerRequest())->withAttribute('id', (string) $item_id)
            ->withAttribute(RESTCurrentUserMiddleware::class, $current_user);
        $current_user->shouldReceive('getID')->andReturn('102');

        $file_information = $data_store->getFileInformation($server_request);

        $this->assertSame($item_id, $file_information->getID());
        $this->assertSame(123456, $file_information->getLength());
        $this->assertSame(0, $file_information->getOffset());
    }

    public function testFileInformationCannotBeFoundIfRequestAttributesAreMissing(): void
    {
        $data_store = new FileBeingUploadedInformationProvider(
            new UploadPathAllocator(),
            \Mockery::mock(FileOngoingUploadDao::class)
        );

        $request = \Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getAttribute')->andReturns(null);

        $this->assertNull($data_store->getFileInformation($request));
    }

    public function testFileInformationCannotBeFoundIfThereIsNotAValidEntryInTheDatabase(): void
    {
        $dao        = \Mockery::mock(FileOngoingUploadDao::class);
        $data_store = new FileBeingUploadedInformationProvider(new UploadPathAllocator(), $dao);

        $dao->shouldReceive('searchFileOngoingUploadByIDUserIDAndExpirationDate')->andReturns([]);

        $current_user = \Mockery::mock(\PFUser::class);
        $current_user->shouldReceive('getId')->andReturn('102');
        $server_request = (new NullServerRequest())->withAttribute('id', '12')
            ->withAttribute(RESTCurrentUserMiddleware::class, $current_user);

        $this->assertNull($data_store->getFileInformation($server_request));
    }
}
