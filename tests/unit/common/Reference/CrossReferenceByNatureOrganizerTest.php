<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Reference;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use Project;
use Project_AccessException;
use ProjectManager;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Test\Builders\CrossReferencePresenterBuilder;

class CrossReferenceByNatureOrganizerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItMovesOneCrossReferenceToASection(): void
    {
        $a_ref       = CrossReferencePresenterBuilder::get(1)->withType('git')->build();
        $another_ref = CrossReferencePresenterBuilder::get(2)->withType('git')->build();

        $nature_collection = new NatureCollection();
        $nature_collection->addNature('git', new Nature('git', 'fas fa-tlp-versioning-git', 'Git', true));
        $nature_collection->addNature('other', new Nature('other', '', 'Other', true));

        $project_access_checker = Mockery::mock(ProjectAccessChecker::class)
            ->shouldReceive('checkUserCanAccessProject')
            ->getMock();

        $project_manager = Mockery::mock(ProjectManager::class, ['getProject' => Mockery::mock(Project::class)]);

        $organizer = new CrossReferenceByNatureOrganizer(
            $project_manager,
            $project_access_checker,
            [$a_ref, $another_ref],
            $nature_collection,
            Mockery::mock(PFUser::class),
        );

        $organizer->moveCrossReferenceToSection($a_ref, 'cloudy/stable');

        self::assertEquals([$another_ref], $organizer->getCrossReferencePresenters());
        self::assertEquals(
            [
                new CrossReferenceNaturePresenter(
                    'Git',
                    'fas fa-tlp-versioning-git',
                    [
                        new CrossReferenceSectionPresenter('cloudy/stable', [$a_ref]),
                    ],
                ),
            ],
            $organizer->getNatures()
        );
    }

    public function testItMoveCrossReferenceEvenIfItHasBeenPimped(): void
    {
        $a_ref       = CrossReferencePresenterBuilder::get(1)->withType('git')->build();
        $another_ref = CrossReferencePresenterBuilder::get(2)->withType('git')->build();

        $nature_collection = new NatureCollection();
        $nature_collection->addNature('git', new Nature('git', 'fas fa-tlp-versioning-git', 'Git', true));
        $nature_collection->addNature('other', new Nature('other', '', 'Other', true));

        $project_access_checker = Mockery::mock(ProjectAccessChecker::class)
            ->shouldReceive('checkUserCanAccessProject')
            ->getMock();

        $project_manager = Mockery::mock(ProjectManager::class, ['getProject' => Mockery::mock(Project::class)]);

        $organizer = new CrossReferenceByNatureOrganizer(
            $project_manager,
            $project_access_checker,
            [$a_ref, $another_ref],
            $nature_collection,
            Mockery::mock(PFUser::class),
        );

        $pimped_reference = $a_ref->withTitle("My new title", null);
        $organizer->moveCrossReferenceToSection(
            $pimped_reference,
            'cloudy/stable'
        );

        self::assertEquals([$another_ref], $organizer->getCrossReferencePresenters());
        self::assertEquals(
            [
                new CrossReferenceNaturePresenter(
                    'Git',
                    'fas fa-tlp-versioning-git',
                    [
                        new CrossReferenceSectionPresenter('cloudy/stable', [$pimped_reference]),
                    ],
                ),
            ],
            $organizer->getNatures()
        );
    }

    public function testItMovesTwoCrossReferencesToSameSection(): void
    {
        $a_ref       = CrossReferencePresenterBuilder::get(1)->withType('git')->build();
        $another_ref = CrossReferencePresenterBuilder::get(2)->withType('git')->build();

        $nature_collection = new NatureCollection();
        $nature_collection->addNature('git', new Nature('git', 'fas fa-tlp-versioning-git', 'Git', true));
        $nature_collection->addNature('other', new Nature('other', '', 'Other', true));

        $project_access_checker = Mockery::mock(ProjectAccessChecker::class)
            ->shouldReceive('checkUserCanAccessProject')
            ->getMock();

        $project_manager = Mockery::mock(ProjectManager::class, ['getProject' => Mockery::mock(Project::class)]);

        $organizer = new CrossReferenceByNatureOrganizer(
            $project_manager,
            $project_access_checker,
            [$a_ref, $another_ref],
            $nature_collection,
            Mockery::mock(PFUser::class),
        );

        $organizer->moveCrossReferenceToSection($a_ref, 'cloudy/stable');
        $organizer->moveCrossReferenceToSection($another_ref, 'cloudy/stable');

        self::assertEquals([], $organizer->getCrossReferencePresenters());
        self::assertEquals(
            [
                new CrossReferenceNaturePresenter(
                    'Git',
                    'fas fa-tlp-versioning-git',
                    [
                        new CrossReferenceSectionPresenter('cloudy/stable', [$a_ref, $another_ref]),
                    ],
                ),
            ],
            $organizer->getNatures()
        );
    }

    public function testItMovesTwoCrossReferencesToDifferentSections(): void
    {
        $a_ref       = CrossReferencePresenterBuilder::get(1)->withType('git')->build();
        $another_ref = CrossReferencePresenterBuilder::get(2)->withType('git')->build();

        $nature_collection = new NatureCollection();
        $nature_collection->addNature('git', new Nature('git', 'fas fa-tlp-versioning-git', 'Git', true));
        $nature_collection->addNature('other', new Nature('other', '', 'Other', true));

        $project_access_checker = Mockery::mock(ProjectAccessChecker::class)
            ->shouldReceive('checkUserCanAccessProject')
            ->getMock();

        $project_manager = Mockery::mock(ProjectManager::class, ['getProject' => Mockery::mock(Project::class)]);

        $organizer = new CrossReferenceByNatureOrganizer(
            $project_manager,
            $project_access_checker,
            [$a_ref, $another_ref],
            $nature_collection,
            Mockery::mock(PFUser::class),
        );

        $organizer->moveCrossReferenceToSection($a_ref, 'cloudy/stable');
        $organizer->moveCrossReferenceToSection($another_ref, 'tuleap/stable');

        self::assertEquals([], $organizer->getCrossReferencePresenters());
        self::assertEquals(
            [
                new CrossReferenceNaturePresenter(
                    'Git',
                    'fas fa-tlp-versioning-git',
                    [
                        new CrossReferenceSectionPresenter('cloudy/stable', [$a_ref]),
                        new CrossReferenceSectionPresenter('tuleap/stable', [$another_ref]),
                    ],
                ),
            ],
            $organizer->getNatures()
        );
    }

    public function testItMovesTwoCrossReferencesToDifferentNatures(): void
    {
        $a_ref       = CrossReferencePresenterBuilder::get(1)->withType('git')->build();
        $another_ref = CrossReferencePresenterBuilder::get(2)->withType('tracker')->build();

        $nature_collection = new NatureCollection();
        $nature_collection->addNature('git', new Nature('git', 'fas fa-tlp-versioning-git', 'Git', true));
        $nature_collection->addNature('tracker', new Nature('tracker', 'fas fa-list-ol', 'Trackers', true));
        $nature_collection->addNature('other', new Nature('other', '', 'Other', true));

        $project_access_checker = Mockery::mock(ProjectAccessChecker::class)
            ->shouldReceive('checkUserCanAccessProject')
            ->getMock();

        $project_manager = Mockery::mock(ProjectManager::class, ['getProject' => Mockery::mock(Project::class)]);

        $organizer = new CrossReferenceByNatureOrganizer(
            $project_manager,
            $project_access_checker,
            [$a_ref, $another_ref],
            $nature_collection,
            Mockery::mock(PFUser::class),
        );

        $organizer->moveCrossReferenceToSection($a_ref, 'cloudy/stable');
        $organizer->moveCrossReferenceToSection($another_ref, "");

        self::assertEquals([], $organizer->getCrossReferencePresenters());
        self::assertEquals(
            [
                new CrossReferenceNaturePresenter(
                    'Git',
                    'fas fa-tlp-versioning-git',
                    [
                        new CrossReferenceSectionPresenter('cloudy/stable', [$a_ref]),
                    ],
                ),
                new CrossReferenceNaturePresenter(
                    'Trackers',
                    'fas fa-list-ol',
                    [
                        new CrossReferenceSectionPresenter('', [$another_ref]),
                    ],
                ),
            ],
            $organizer->getNatures()
        );
    }

    public function testItIgnoresCrossReferenceIfRequestedNatureIsNotFound(): void
    {
        $a_ref = CrossReferencePresenterBuilder::get(1)->withType('git')->build();

        $nature_collection = new NatureCollection();
        $nature_collection->addNature('other', new Nature('other', '', 'Other', true));

        $project_access_checker = Mockery::mock(ProjectAccessChecker::class)
            ->shouldReceive('checkUserCanAccessProject')
            ->getMock();

        $project_manager = Mockery::mock(ProjectManager::class, ['getProject' => Mockery::mock(Project::class)]);

        $organizer = new CrossReferenceByNatureOrganizer(
            $project_manager,
            $project_access_checker,
            [$a_ref],
            $nature_collection,
            Mockery::mock(PFUser::class),
        );

        $organizer->moveCrossReferenceToSection($a_ref, 'cloudy/stable');

        self::assertEquals([], $organizer->getCrossReferencePresenters());
        self::assertEquals([], $organizer->getNatures());
    }

    public function testItIgnoresCrossReferenceIfUserCannotAccessToProject(): void
    {
        $a_ref = CrossReferencePresenterBuilder::get(1)->withType('git')->build();

        $nature_collection = new NatureCollection();
        $nature_collection->addNature('git', new Nature('git', 'fas fa-tlp-versioning-git', 'Git', true));
        $nature_collection->addNature('other', new Nature('other', '', 'Other', true));

        $current_user = Mockery::mock(PFUser::class);
        $project      = Mockery::mock(Project::class);

        $project_access_checker = Mockery::mock(ProjectAccessChecker::class)
            ->shouldReceive('checkUserCanAccessProject')
            ->with($current_user, $project)
            ->andThrow(Mockery::mock(Project_AccessException::class))
            ->getMock();

        $project_manager = Mockery::mock(ProjectManager::class, ['getProject' => $project]);

        $organizer = new CrossReferenceByNatureOrganizer(
            $project_manager,
            $project_access_checker,
            [$a_ref],
            $nature_collection,
            $current_user,
        );

        $organizer->moveCrossReferenceToSection($a_ref, 'cloudy/stable');

        self::assertEquals([], $organizer->getCrossReferencePresenters());
        self::assertEquals([], $organizer->getNatures());
    }

    public function testItOrganiseRemainingCrossReferences(): void
    {
        $a_ref       = CrossReferencePresenterBuilder::get(1)->withType('git')->build();
        $another_ref = CrossReferencePresenterBuilder::get(2)->withType('wiki')->build();

        $nature_collection = new NatureCollection();
        $nature_collection->addNature('git', new Nature('git', 'fas fa-tlp-versioning-git', 'Git', true));
        $nature_collection->addNature('wiki', new Nature('wiki', 'fas fa-wiki', 'Wiki', true));
        $nature_collection->addNature('other', new Nature('other', '', 'Other', true));

        $project_access_checker = Mockery::mock(ProjectAccessChecker::class)
            ->shouldReceive('checkUserCanAccessProject')
            ->getMock();

        $project_manager = Mockery::mock(ProjectManager::class, ['getProject' => Mockery::mock(Project::class)]);

        $organizer = new CrossReferenceByNatureOrganizer(
            $project_manager,
            $project_access_checker,
            [$a_ref, $another_ref],
            $nature_collection,
            Mockery::mock(PFUser::class),
        );

        $organizer->moveCrossReferenceToSection($a_ref, 'cloudy/stable');
        $organizer->organizeRemainingCrossReferences();

        self::assertEquals([], $organizer->getCrossReferencePresenters());
        self::assertEquals(
            [
                new CrossReferenceNaturePresenter(
                    'Git',
                    'fas fa-tlp-versioning-git',
                    [
                        new CrossReferenceSectionPresenter('cloudy/stable', [$a_ref]),
                    ],
                ),
                new CrossReferenceNaturePresenter(
                    'Wiki',
                    'fas fa-wiki',
                    [
                        new CrossReferenceSectionPresenter('', [$another_ref]),
                    ],
                ),
            ],
            $organizer->getNatures()
        );
    }

    public function testItOrganiseRemainingCrossReferencesButRemoveThoseIfUserCannotAccessToProject(): void
    {
        $a_ref       = CrossReferencePresenterBuilder::get(1)->withType('git')->build();
        $another_ref = CrossReferencePresenterBuilder::get(2)->withType('wiki')->build();

        $nature_collection = new NatureCollection();
        $nature_collection->addNature('git', new Nature('git', 'fas fa-tlp-versioning-git', 'Git', true));
        $nature_collection->addNature('wiki', new Nature('wiki', 'fas fa-wiki', 'Wiki', true));
        $nature_collection->addNature('other', new Nature('other', '', 'Other', true));

        $project_access_checker = Mockery::mock(ProjectAccessChecker::class)
            ->shouldReceive('checkUserCanAccessProject')
            ->andThrow(Mockery::mock(Project_AccessException::class))
            ->getMock();

        $project_manager = Mockery::mock(ProjectManager::class, ['getProject' => Mockery::mock(Project::class)]);

        $organizer = new CrossReferenceByNatureOrganizer(
            $project_manager,
            $project_access_checker,
            [$a_ref, $another_ref],
            $nature_collection,
            Mockery::mock(PFUser::class),
        );

        $organizer->moveCrossReferenceToSection($a_ref, 'cloudy/stable');
        $organizer->organizeRemainingCrossReferences();

        self::assertEquals([], $organizer->getCrossReferencePresenters());
        self::assertEquals([], $organizer->getNatures());
    }

    public function testRemoveUnreadableCrossReference(): void
    {
        $a_ref       = CrossReferencePresenterBuilder::get(1)->withType('git')->build();
        $another_ref = CrossReferencePresenterBuilder::get(2)->withType('wiki')->build();

        $project_access_checker = Mockery::mock(ProjectAccessChecker::class)
            ->shouldReceive('checkUserCanAccessProject')
            ->getMock();

        $project_manager = Mockery::mock(ProjectManager::class, ['getProject' => Mockery::mock(Project::class)]);

        $organizer = new CrossReferenceByNatureOrganizer(
            $project_manager,
            $project_access_checker,
            [$a_ref, $another_ref],
            new NatureCollection(),
            Mockery::mock(PFUser::class),
        );

        $organizer->removeUnreadableCrossReference($a_ref);

        self::assertEquals(
            [$another_ref],
            $organizer->getCrossReferencePresenters(),
        );
    }

    public function testRemoveUnreadableCrossReferenceEvenIfItHasBeenEnhanced(): void
    {
        $a_ref       = CrossReferencePresenterBuilder::get(1)->withType('git')->build();
        $another_ref = CrossReferencePresenterBuilder::get(2)->withType('wiki')->build();

        $project_access_checker = Mockery::mock(ProjectAccessChecker::class)
            ->shouldReceive('checkUserCanAccessProject')
            ->getMock();

        $project_manager = Mockery::mock(ProjectManager::class, ['getProject' => Mockery::mock(Project::class)]);

        $organizer = new CrossReferenceByNatureOrganizer(
            $project_manager,
            $project_access_checker,
            [$a_ref, $another_ref],
            new NatureCollection(),
            Mockery::mock(PFUser::class),
        );

        $organizer->removeUnreadableCrossReference($a_ref->withTitle("New title", null));

        self::assertEquals(
            [$another_ref],
            $organizer->getCrossReferencePresenters(),
        );
    }
}
