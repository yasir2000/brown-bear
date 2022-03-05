<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\Events;

use Tuleap\Project\Event\ProjectServiceBeforeActivation;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class ProjectServiceBeforeActivationProxyTest extends TestCase
{
    private ProjectServiceBeforeActivationProxy $proxy;
    private ProjectServiceBeforeActivation $event;

    protected function setUp(): void
    {
        $this->event = new ProjectServiceBeforeActivation(
            new \Project(['group_id' => 101, 'group_name' => 'A project', 'unix_group_name' => 'a_project', 'icon_codepoint' => '']),
            \program_managementPlugin::SERVICE_SHORTNAME,
            UserTestBuilder::buildWithDefaults()
        );

        $this->proxy = ProjectServiceBeforeActivationProxy::fromEvent($this->event);
    }

    public function testItBuildsFromEvent(): void
    {
        $this->assertSame($this->event->getProject()->getID(), $this->proxy->getProjectIdentifier()->getId());
        $this->assertSame($this->event->getUser()->getID(), $this->proxy->getUserIdentifier()->getId());
    }

    public function testItVerifyEventIsForService(): void
    {
        $this->assertTrue($this->proxy->isForServiceShortName(\program_managementPlugin::SERVICE_SHORTNAME));
    }

    public function testItVerifyEventIsNotForService(): void
    {
        $this->assertFalse($this->proxy->isForServiceShortName('other_service'));
    }

    public function testItPreventsServiceUsage(): void
    {
        $this->proxy->preventActivation('A message');
        $this->assertSame('A message', $this->event->getWarningMessage());
        $this->assertTrue($this->event->doesPluginSetAValue());
    }
}
