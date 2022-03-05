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

namespace Tuleap\PullRequest\Notification;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\PullRequest\Notification\Strategy\PullRequestNotificationStrategy;

final class EventSubjectToNotificationListenerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    public function testBuildsEventSubjectToNotificationListener(): void
    {
        $strategy = \Mockery::mock(PullRequestNotificationStrategy::class);
        $builder  = \Mockery::mock(NotificationToProcessBuilder::class);

        $listener = new EventSubjectToNotificationListener(
            $strategy,
            $builder
        );

        $this->assertSame($strategy, $listener->getNotificationStrategy());
        $this->assertSame($builder, $listener->getNotificationToProcessBuilder());
    }
}
