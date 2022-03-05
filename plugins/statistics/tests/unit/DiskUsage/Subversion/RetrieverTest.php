<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\Statistics\DiskUsage\Subversion;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Statistics_DiskUsageDao;

final class RetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Statistics_DiskUsageDao
     */
    private $dao;
    /**
     * @var Retriever
     */
    private $retriever;

    protected function setUp(): void
    {
        $this->dao       = \Mockery::mock(Statistics_DiskUsageDao::class);
        $this->retriever = new Retriever($this->dao);
    }

    public function testReturnsValueGivenByTheDB(): void
    {
        $this->dao->shouldReceive('getLastSizeForService')->andReturn(['size' => '10']);

        self::assertEquals(10, $this->retriever->getLastSizeForProject(\Project::buildForTest()));
    }

    public function testReturns0WhenNoValueExistsInDB(): void
    {
        $this->dao->shouldReceive('getLastSizeForService')->andReturn(false);

        self::assertEquals(0, $this->retriever->getLastSizeForProject(\Project::buildForTest()));
    }
}
