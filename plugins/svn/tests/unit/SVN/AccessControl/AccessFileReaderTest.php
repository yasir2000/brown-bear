<?php
/**
* Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\SVN\AccessControl;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\SVN\Repository\Repository;

class AccessFileReaderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var Repository */
    private $repository;

    /** @var AccessFileReader */
    private $reader;

    protected function setUp(): void
    {
        parent::setUp();
        $fixtures_dir = __DIR__ . '/_fixtures';

        $this->repository = \Mockery::mock(Repository::class);
        $this->repository->shouldReceive('getSystemPath')->andReturn($fixtures_dir);

        $this->reader = new AccessFileReader();
    }

    public function testItReadsTheDefaultBlock(): void
    {
        $this->assertMatchesRegularExpression(
            '/le default/',
            $this->reader->readDefaultBlock($this->repository)
        );
    }

    public function testItReadsTheContentBlock(): void
    {
        $this->assertMatchesRegularExpression(
            '/le content/',
            $this->reader->readContentBlock($this->repository)
        );
    }

    public function testItDoesNotContainDelimiters(): void
    {
        $this->assertDoesNotMatchRegularExpression(
            '/# BEGIN CODENDI DEFAULT SETTINGS/',
            $this->reader->readDefaultBlock($this->repository)
        );
    }
}
