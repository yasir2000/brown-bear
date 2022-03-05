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

namespace Tuleap\Upload;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Tus\TusFileInformation;

class FileBeingUploadedLockerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    /**
     * @var string
     */
    private $tmp_dir;

    protected function setUp(): void
    {
        $path = sys_get_temp_dir() . '/' . bin2hex(random_bytes(8));
        mkdir($path);
        $this->tmp_dir = $path;
    }

    protected function tearDown(): void
    {
        $folders = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->tmp_dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($folders as $folder) {
            if ($folder->isDir()) {
                rmdir($folder->getPathname());
            } else {
                unlink($folder->getPathname());
            }
        }
        rmdir($this->tmp_dir);
    }

    public function testALockCanOnlyBeAcquiredOnce(): void
    {
        \ForgeConfig::set('tmp_dir', $this->tmp_dir);
        $path_allocator = \Mockery::mock(PathAllocator::class);
        $path_allocator
            ->shouldReceive('getPathForItemBeingUploaded')
            ->andReturn("$this->tmp_dir/12");
        $locker = new FileBeingUploadedLocker($path_allocator);

        $file_information = \Mockery::mock(TusFileInformation::class);
        $file_information->shouldReceive('getID')->andReturns(12);

        $this->assertTrue($locker->lock($file_information));
        $this->assertFalse($locker->lock($file_information));
    }

    public function testALockCanBeAcquiredAgainAfterHavingBeenReleased(): void
    {
        \ForgeConfig::set('tmp_dir', $this->tmp_dir);
        $path_allocator = \Mockery::mock(PathAllocator::class);
        $path_allocator
            ->shouldReceive('getPathForItemBeingUploaded')
            ->andReturn("$this->tmp_dir/12");
        $locker = new FileBeingUploadedLocker($path_allocator);

        $file_information = \Mockery::mock(TusFileInformation::class);
        $file_information->shouldReceive('getID')->andReturns(12);

        $this->assertTrue($locker->lock($file_information));
        $locker->unlock($file_information);
        $this->assertTrue($locker->lock($file_information));
    }
}
