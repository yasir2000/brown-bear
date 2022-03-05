<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\HudsonGit\Git\Administration;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Project;

class AdministrationPaneBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Project
     */
    private $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Mockery::mock(Project::class);
        $this->project->shouldReceive('getUnixName')->andReturn('test');
    }

    public function testItBuildsAPane()
    {
        $pane = AdministrationPaneBuilder::buildPane($this->project);

        $this->assertEquals('Jenkins', $pane->getPaneName());
        $this->assertStringContainsString(
            "/test/administration/jenkins",
            $pane->getUrl()
        );
        $this->assertFalse($pane->isActive());
    }

    public function testItBuildsAnActivePane()
    {
        $pane = AdministrationPaneBuilder::buildActivePane($this->project);

        $this->assertEquals('Jenkins', $pane->getPaneName());
        $this->assertStringContainsString(
            "/test/administration/jenkins",
            $pane->getUrl()
        );
        $this->assertTrue($pane->isActive());
    }
}
