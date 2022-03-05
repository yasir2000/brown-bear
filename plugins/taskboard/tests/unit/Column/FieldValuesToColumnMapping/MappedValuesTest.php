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

namespace Tuleap\Taskboard\Column\FieldValuesToColumnMapping;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

final class MappedValuesTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var MappedValues */
    private $mapped_values;

    protected function setUp(): void
    {
        $this->mapped_values = new MappedValues([123, 456]);
    }

    public function testGetFirstValue(): void
    {
        $this->assertSame(123, $this->mapped_values->getFirstValue());
    }

    public function testGetValueIds(): void
    {
        $this->assertSame([123, 456], $this->mapped_values->getValueIds());
    }

    public function testIsEmpty(): void
    {
        $this->assertFalse($this->mapped_values->isEmpty());
    }
}
