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

namespace Tuleap\Project\Registration\Template;

use PFUser;
use Project;
use ProjectManager;
use Tuleap\Project\REST\v1\ProjectPostRepresentation;
use Tuleap\Test\PHPUnit\TestCase;

final class TemplateFromProjectForCreationTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ProjectManager
     */
    private $project_manager;
    /**
     * @var PFUser&\PHPUnit\Framework\MockObject\MockObject
     */
    private $user;

    protected function setUp(): void
    {
        $this->project_manager = $this->createMock(ProjectManager::class);
        $this->user            = $this->createMock(PFUser::class);
    }

    public function testGetTemplateFromProjectForCreationFromRESTRepresentation(): void
    {
        $representation = ProjectPostRepresentation::build(123);

        $expected_project = $this->mockForSuccessfulValidation($representation->template_id);

        $template_from_project_for_creation = TemplateFromProjectForCreation::fromRESTRepresentation(
            $representation,
            $this->user,
            $this->project_manager
        );

        self::assertEquals($expected_project, $template_from_project_for_creation->getProject());
    }

    private function mockForSuccessfulValidation(int $project_id): Project
    {
        $project = $this->createMock(Project::class);
        $project->method('getID')->willReturn($project_id);
        $project->method('isError')->willReturn(false);
        $project->method('isActive')->willReturn(false);
        $project->method('isTemplate')->willReturn(true);

        $this->project_manager->method('getProject')->with($project_id)->willReturn($project);

        return $project;
    }

    public function testGetTemplateFromProjectForCreationFromGlobalProjectTemplate(): void
    {
        self::assertEquals(Project::ADMIN_PROJECT_ID, TemplateFromProjectForCreation::fromGlobalProjectAdminTemplate()->getProject()->getID());
    }

    public function testGetTemplateFromProjectForCreationIsNotValidWhenProjectToUseAsTemplateDoesNotExist(): void
    {
        $representation = ProjectPostRepresentation::build(404);

        $project = $this->createMock(Project::class);
        $project->method('getID')->willReturn($representation->template_id);
        $project->method('isError')->willReturn(true);

        $this->project_manager->method('getProject')->with($representation->template_id)->willReturn($project);

        $this->expectException(ProjectTemplateIDInvalidException::class);
        TemplateFromProjectForCreation::fromRESTRepresentation(
            $representation,
            $this->user,
            $this->project_manager
        );
    }

    public function testGetTemplateFromProjectForCreationIsNotValidWhenProjectToUseAsTemplateIsNotActiveAndNotMarkedAsTemplate(): void
    {
        $representation = ProjectPostRepresentation::build(124);

        $project = $this->createMock(Project::class);
        $project->method('getID')->willReturn($representation->template_id);
        $project->method('isError')->willReturn(false);
        $project->method('isActive')->willReturn(false);
        $project->method('isTemplate')->willReturn(false);

        $this->project_manager->method('getProject')->with($representation->template_id)->willReturn($project);

        $this->expectException(ProjectTemplateNotActiveException::class);
        TemplateFromProjectForCreation::fromRESTRepresentation(
            $representation,
            $this->user,
            $this->project_manager
        );
    }

    public function testGetTemplateFromProjectForCreationIsNotValidWhenProjectToUseIsActiveButTheUserRequestingTheCreationIsNotOneOfTheProjectAdmins(): void
    {
        $representation = ProjectPostRepresentation::build(125);

        $project = $this->createMock(Project::class);
        $project->method('getID')->willReturn($representation->template_id);
        $project->method('isError')->willReturn(false);
        $project->method('isActive')->willReturn(true);
        $project->method('isTemplate')->willReturn(false);

        $this->project_manager->method('getProject')->with($representation->template_id)->willReturn($project);
        $this->user->method('isAdmin')->with($representation->template_id)->willReturn(false);
        $this->user->method('getId')->willReturn(102);

        $this->expectException(InsufficientPermissionToUseProjectAsTemplateException::class);
        TemplateFromProjectForCreation::fromRESTRepresentation(
            $representation,
            $this->user,
            $this->project_manager
        );
    }
}
