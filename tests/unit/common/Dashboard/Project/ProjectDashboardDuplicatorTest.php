<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\Dashboard\Project;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Project;
use Tuleap\Dashboard\Widget\DashboardWidget;
use Tuleap\Dashboard\Widget\DashboardWidgetColumn;
use Tuleap\Dashboard\Widget\DashboardWidgetLine;
use Tuleap\Project\MappingRegistry;

class ProjectDashboardDuplicatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var ProjectDashboardDuplicator
     */
    private $duplicator;

    /**
     * @var Project
     */
    private $template_project;

    /**
     * @var Project
     */
    private $new_project;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|DisabledProjectWidgetsChecker
     */
    private $checker;

    protected function setUp(): void
    {
        $this->dao              = \Mockery::spy(\Tuleap\Dashboard\Project\ProjectDashboardDao::class);
        $this->retriever        = \Mockery::spy(\Tuleap\Dashboard\Project\ProjectDashboardRetriever::class);
        $this->widget_dao       = \Mockery::spy(\Tuleap\Dashboard\Widget\DashboardWidgetDao::class);
        $this->widget_retriever = \Mockery::spy(\Tuleap\Dashboard\Widget\DashboardWidgetRetriever::class);
        $this->widget_factory   = \Mockery::spy(\Tuleap\Widget\WidgetFactory::class);
        $this->checker          = \Mockery::mock(DisabledProjectWidgetsChecker::class);

        $this->duplicator = new ProjectDashboardDuplicator(
            $this->dao,
            $this->retriever,
            $this->widget_dao,
            $this->widget_retriever,
            $this->widget_factory,
            $this->checker
        );

        $this->template_project = \Mockery::spy(\Project::class, ['getID' => 101, 'getUnixName' => false, 'isPublic' => false]);
        $this->new_project      = \Mockery::spy(\Project::class, ['getID' => 102, 'getUnixName' => false, 'isPublic' => false]);
    }

    public function testItDuplicatesEachDasboards()
    {
        $dashboard_01 = new ProjectDashboard(1, 101, 'dashboard');
        $dashboard_02 = new ProjectDashboard(2, 101, 'dashboard 2');

        $this->widget_retriever->shouldReceive('getAllWidgets')->andReturns([]);

        $this->retriever->shouldReceive('getAllProjectDashboards')->with($this->template_project)->andReturns([$dashboard_01, $dashboard_02]);

        $this->dao->shouldReceive('duplicateDashboard')->times(2);

        $this->duplicator->duplicate($this->template_project, $this->new_project, new MappingRegistry([]));
    }

    public function testItDuplicatesEachLinesForADashboard()
    {
        $dashboard = new ProjectDashboard(1, 101, 'dashboard');

        $this->retriever->shouldReceive('getAllProjectDashboards')->with($this->template_project)->andReturns([$dashboard]);

        $line_01 = new DashboardWidgetLine(1, 'one-column', []);
        $line_02 = new DashboardWidgetLine(2, 'one-column', []);

        $this->widget_retriever->shouldReceive('getAllWidgets')->with(1, 'project')->andReturns([$line_01, $line_02]);

        $this->widget_dao->shouldReceive('duplicateLine')->times(2);

        $this->duplicator->duplicate($this->template_project, $this->new_project, new MappingRegistry([]));
    }

    public function testItDuplicatesEachColumnsForALine()
    {
        $dashboard = new ProjectDashboard(1, 101, 'dashboard');

        $this->retriever->shouldReceive('getAllProjectDashboards')->with($this->template_project)->andReturns([$dashboard]);

        $column_01 = new DashboardWidgetColumn(1, 1, []);
        $column_02 = new DashboardWidgetColumn(2, 2, []);

        $line = new DashboardWidgetLine(
            1,
            'two-columns',
            [$column_01, $column_02]
        );

        $this->widget_retriever->shouldReceive('getAllWidgets')->with(1, 'project')->andReturns([$line]);

        $this->widget_dao->shouldReceive('duplicateColumn')->times(2);

        $this->duplicator->duplicate($this->template_project, $this->new_project, new MappingRegistry([]));
    }

    public function testItDuplicatesEachWidgetForAColumn()
    {
        $dashboard = new ProjectDashboard(1, 101, 'dashboard');

        $this->retriever->shouldReceive('getAllProjectDashboards')->with($this->template_project)->andReturns([$dashboard]);

        $widget_01 = new DashboardWidget(1, 'projectimageviewer', 1, 1, 1, 0);
        $widget_02 = new DashboardWidget(2, 'projectcontacts', 0, 1, 2, 0);

        $column = new DashboardWidgetColumn(1, 1, [$widget_01, $widget_02]);
        $line   = new DashboardWidgetLine(
            1,
            'one-column',
            [$column]
        );

        $this->widget_retriever->shouldReceive('getAllWidgets')->with(1, 'project')->andReturns([$line]);

        $widget_instance_01 = \Mockery::spy(\Widget::class);
        $widget_instance_02 = \Mockery::spy(\Widget::class);

        $this->widget_factory->shouldReceive('getInstanceByWidgetName')->with('projectimageviewer')->andReturns($widget_instance_01);
        $this->widget_factory->shouldReceive('getInstanceByWidgetName')->with('projectcontacts')->andReturns($widget_instance_02);

        $this->widget_dao->shouldReceive('duplicateWidget')->times(2);
        $widget_instance_01->shouldReceive('cloneContent')->once();
        $widget_instance_02->shouldReceive('cloneContent')->once();

        $this->checker->shouldReceive('isWidgetDisabled')
            ->with($widget_instance_01, ProjectDashboardController::DASHBOARD_TYPE)
            ->once()
            ->andReturnFalse();

        $this->checker->shouldReceive('isWidgetDisabled')
            ->with($widget_instance_02, ProjectDashboardController::DASHBOARD_TYPE)
            ->once()
            ->andReturnFalse();

        $this->duplicator->duplicate($this->template_project, $this->new_project, new MappingRegistry([]));
    }

    public function testItDoesNotDuplicateDisabledProjectWidgetForAColumn()
    {
        $dashboard = new ProjectDashboard(1, 101, 'dashboard');

        $this->retriever->shouldReceive('getAllProjectDashboards')->with($this->template_project)->andReturns([$dashboard]);

        $widget_01 = new DashboardWidget(1, 'projectimageviewer', 1, 1, 1, 0);
        $widget_02 = new DashboardWidget(2, 'projectcontacts', 0, 1, 2, 0);

        $column = new DashboardWidgetColumn(1, 1, [$widget_01, $widget_02]);
        $line   = new DashboardWidgetLine(
            1,
            'one-column',
            [$column]
        );

        $this->widget_retriever->shouldReceive('getAllWidgets')->with(1, 'project')->andReturns([$line]);

        $widget_instance_01 = \Mockery::spy(\Widget::class);
        $widget_instance_02 = \Mockery::spy(\Widget::class);

        $this->widget_factory->shouldReceive('getInstanceByWidgetName')->with('projectimageviewer')->andReturns($widget_instance_01);
        $this->widget_factory->shouldReceive('getInstanceByWidgetName')->with('projectcontacts')->andReturns($widget_instance_02);

        $this->widget_dao->shouldReceive('duplicateWidget')->once();
        $widget_instance_01->shouldReceive('cloneContent')->once();
        $widget_instance_02->shouldReceive('cloneContent')->never();

        $this->checker->shouldReceive('isWidgetDisabled')
            ->with($widget_instance_01, ProjectDashboardController::DASHBOARD_TYPE)
            ->once()
            ->andReturnFalse();

        $this->checker->shouldReceive('isWidgetDisabled')
            ->with($widget_instance_02, ProjectDashboardController::DASHBOARD_TYPE)
            ->once()
            ->andReturnTrue();

        $this->duplicator->duplicate($this->template_project, $this->new_project, new MappingRegistry([]));
    }

    public function testItDoesNotDuplicateUnknownWidgetForAColumn()
    {
        $dashboard = new ProjectDashboard(1, 101, 'dashboard');

        $this->retriever->shouldReceive('getAllProjectDashboards')->with($this->template_project)->andReturns([$dashboard]);

        $widget = new DashboardWidget(1, 'projectimageviewer', 1, 1, 1, 0);

        $column = new DashboardWidgetColumn(1, 1, [$widget]);
        $line   = new DashboardWidgetLine(
            1,
            'one-column',
            [$column]
        );

        $this->widget_retriever->shouldReceive('getAllWidgets')->with(1, 'project')->andReturns([$line]);

        $this->widget_factory->shouldReceive('getInstanceByWidgetName')->with('projectimageviewer')->andReturns(null);

        $this->widget_dao->shouldReceive('duplicateWidget')->never();

        $this->checker->shouldReceive('isWidgetDisabled')->never();

        $this->duplicator->duplicate($this->template_project, $this->new_project, new MappingRegistry([]));
    }
}
