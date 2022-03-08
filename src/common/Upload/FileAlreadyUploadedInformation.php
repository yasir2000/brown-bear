<?php
/**
 * Copyright (c) BrownBear, 2019 - Present. All Rights Reserved.
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

use Tuleap\Tus\TusFileInformation;

final class FileAlreadyUploadedInformation implements TusFileInformation
{
    /**
     * @var int
     */
    private $id;
    /**
     * @var int
     */
    private $length;
    /**
     * @var string
     */
    private $name;

    public function __construct(int $id, string $name, int $length)
    {
        $this->id = $id;
        if ($length < 0) {
            throw new \UnexpectedValueException('The length must be positive');
        }
        $this->length = $length;
        $this->name   = $name;
    }

    public function getID(): int
    {
        return $this->id;
    }

    public function getLength(): int
    {
        return $this->length;
    }

    public function getOffset(): int
    {
        return $this->length;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
