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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\Timebox;

use Tuleap\ProgramManagement\Domain\Program\Backlog\TimeboxIdentifier;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFullArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\TimeboxIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;

final class UserCanUpdateTimeboxVerifierTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&Artifact
     */
    private $artifact;
    private TimeboxIdentifier $artifact_identifier;
    private UserIdentifierStub $user_identifier;

    protected function setUp(): void
    {
        $this->artifact            = $this->createStub(Artifact::class);
        $this->artifact_identifier = TimeboxIdentifierStub::withId(1);
        $this->user_identifier     = UserIdentifierStub::buildGenericUser();
    }

    private function getVerifier(): UserCanUpdateTimeboxVerifier
    {
        return new UserCanUpdateTimeboxVerifier(
            RetrieveFullArtifactStub::withArtifact($this->artifact),
            RetrieveUserStub::withGenericUser()
        );
    }

    public function testItReturnsTrue(): void
    {
        $this->artifact->method('userCanUpdate')->willReturn(true);

        self::assertTrue($this->getVerifier()->canUserUpdate($this->artifact_identifier, $this->user_identifier));
    }

    public function testItReturnsFalse(): void
    {
        $this->artifact->method('userCanUpdate')->willReturn(false);

        self::assertFalse($this->getVerifier()->canUserUpdate($this->artifact_identifier, $this->user_identifier));
    }
}
