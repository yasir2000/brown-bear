<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Project\Service;

use EventManager;
use Service;
use ServiceManager;
use Tuleap\GlobalLanguageMock;
use Tuleap\Layout\ServiceUrlCollector;

final class ServicesPresenterBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ServiceManager
     */
    private $service_manager;
    private ServicesPresenterBuilder $builder;
    private \Project $project;
    /**
     * @var \CSRFSynchronizerToken|\PHPUnit\Framework\MockObject\Stub
     */
    private $csrf_token;
    /**
     * @var \PFUser|\PHPUnit\Framework\MockObject\Stub
     */
    private $user;
    /**
     * @var EventManager|\PHPUnit\Framework\MockObject\Stub
     */
    private $event_manager;

    protected function setUp(): void
    {
        $this->project = $this->createStub(\Project::class);
        $this->project->method('getMinimalRank');
        $this->project->method('getID');
        $this->user = $this->createStub(\PFUser::class);
        $this->user->method('isSuperUser')->willReturn(false);

        $this->csrf_token = $this->createStub(\CSRFSynchronizerToken::class);
        $this->csrf_token->method('getTokenName');
        $this->csrf_token->method('getToken');

        $this->service_manager = $this->createStub(ServiceManager::class);

        $event_manager = new class extends EventManager
        {
            public function processEvent($event_name, $params = [])
            {
                if ($event_name instanceof ServiceDisabledCollector) {
                    $event_name->setIsDisabled("Disabled by plugin");
                }
                if ($event_name instanceof ServiceUrlCollector) {
                    $event_name->setUrl("/external_url");
                }
                return $event_name;
            }
        };
        $this->builder = new ServicesPresenterBuilder($this->service_manager, $event_manager);
    }

    public function testItBuildServiceWithoutAdminAndSummaryServices(): void
    {
        $admin_service = new Service(
            $this->project,
            [
                'short_name' => 'admin',
                'service_id' => 10,
                'is_active' => true,
            ]
        );

        $summary_service = new Service(
            $this->project,
            [
                'short_name' => 'summary',
                'service_id' => 20,
                'is_active' => true,
            ]
        );

        $tracker_service = new Service(
            $this->project,
            [
                'short_name' => 'tracker',
                'service_id' => 102,
                'is_active' => true,
                'label' => 'Tracker',
                'description' => 'description',
                'is_used' => true,
                'is_in_iframe' => false,
                'rank' => 200,
                'scope' => 'project',
                'group_id' => 101,
            ]
        );

        $this->service_manager->expects(self::once())->method('getListOfAllowedServicesForProject')->willReturn([$admin_service, $summary_service, $tracker_service]);

        $service_presenter = $this->builder->build($this->project, $this->csrf_token, $this->user);
        self::assertCount(1, $service_presenter->services);
        self::assertEquals('Tracker', $service_presenter->services[0]->label);
        self::assertStringContainsString('/external_url', $service_presenter->services[0]->service_json);
    }

    public function testItCanBeDisabledByPlugins(): void
    {
        $admin_service = new Service(
            $this->project,
            [
                'short_name' => 'admin',
                'service_id' => 10,
                'is_active' => true,
            ]
        );

        $summary_service = new Service(
            $this->project,
            [
                'short_name' => 'summary',
                'service_id' => 20,
                'is_active' => true,
            ]
        );

        $tracker_service = new Service(
            $this->project,
            [
                'short_name' => 'tracker',
                'service_id' => 102,
                'is_active' => true,
                'label' => 'Tracker',
                'description' => 'description',
                'is_used' => true,
                'is_in_iframe' => false,
                'rank' => 200,
                'scope' => 'project',
                'group_id' => 101,
            ]
        );


        $this->service_manager->expects(self::once())->method('getListOfAllowedServicesForProject')->willReturn([$admin_service, $summary_service, $tracker_service]);

        $service_presenter = $this->builder->build($this->project, $this->csrf_token, $this->user);
        self::assertCount(1, $service_presenter->services);
        self::assertStringContainsString('"is_disabled_reason":"Disabled by plugin"', $service_presenter->services[0]->service_json);
    }
}
