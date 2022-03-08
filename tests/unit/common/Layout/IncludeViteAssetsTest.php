<?php
/**
 * Copyright (c) BrownBear, 2021-Present. All Rights Reserved.
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

namespace Tuleap\Layout;

use org\bovigo\vfs\vfsStream;
use Tuleap\Test\PHPUnit\TestCase;

final class IncludeViteAssetsTest extends TestCase
{
    private string $assets_dir_path;

    protected function setUp(): void
    {
        $this->assets_dir_path = vfsStream::setup()->url() . '/assets';
        mkdir($this->assets_dir_path);
    }

    public function testItReturnsFileURLWithHashedName(): void
    {
        file_put_contents($this->assets_dir_path . '/manifest.json', '{"file.js": {"file": "file-hashed.js"}}');
        $include_assets = new IncludeViteAssets($this->assets_dir_path, '/path/to');

        $this->assertEquals('/path/to/file-hashed.js', $include_assets->getFileURL('file.js'));
    }

    public function testItRaisesManifestExceptionIfThereIsNoManifestFile(): void
    {
        $include_assets = new IncludeViteAssets($this->assets_dir_path, '/path/to');

        $this->expectException(IncludeAssetsManifestException::class);

        $include_assets->getFileURL('some_file.js');
    }

    public function testItRaisesExceptionWhenTheRequestedFileDoesNotExist(): void
    {
        file_put_contents($this->assets_dir_path . '/manifest.json', '{}');
        $include_assets = new IncludeViteAssets($this->assets_dir_path, '/path/to');

        $this->expectException(IncludeAssetsException::class);

        $include_assets->getFileURL('some_file.js');
    }

    public function testItDoesNotDoubleTrailingSlashInFileURL(): void
    {
        file_put_contents($this->assets_dir_path . '/manifest.json', '{"file.js": {"file": "file-hashed.js"}}');
        $include_assets = new IncludeViteAssets($this->assets_dir_path, '/path/to/');

        $this->assertEquals('/path/to/file-hashed.js', $include_assets->getFileURL('file.js'));
    }
}
