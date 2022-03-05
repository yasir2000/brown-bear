<?php
/**
 * Copyright (c) Enalean, 2015 - present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
 */

declare(strict_types=1);

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

class Tracker_FormElement_Field_ArtifactLinkTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use \Tuleap\GlobalResponseMock;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact_Changeset
     */
    private $changeset;

    protected function setUp(): void
    {
        $this->changeset = Mockery::spy(Tracker_Artifact_Changeset::class);
        $this->changeset->shouldReceive('getArtifact')->andReturn(Mockery::spy(Artifact::class));
    }

    protected function tearDown(): void
    {
        UserManager::clearInstance();
        Tracker_ArtifactFactory::clearInstance();
    }

    public function testNoDefaultValue(): void
    {
        $field = Mockery::mock(\Tracker_FormElement_Field_ArtifactLink::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $field->shouldReceive('getProperty')->andReturn(null);
        $this->assertFalse($field->hasDefaultValue());
    }

    public function testGetChangesetValue(): void
    {
        $value_dao = Mockery::mock(
            \Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkFieldValueDao::class
        );
        $value_dao->shouldReceive('searchById')->andReturn(TestHelper::arrayToDar([
            'id' => 123,
            'field_id' => 1,
            'artifact_id' => '999',
            'keyword' => 'bug',
            'group_id' => '102',
            'tracker_id' => '456',
            'nature' => '',
            'last_changeset_id' => '789',
        ]));
        $value_dao->shouldReceive('searchReverseLinksById')->andReturn(TestHelper::emptyDar());

        $field = Mockery::mock(\Tracker_FormElement_Field_ArtifactLink::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $field->shouldReceive('getValueDao')->andReturns($value_dao);

        $this->assertInstanceOf(Tracker_Artifact_ChangesetValue_ArtifactLink::class, $field->getChangesetValue($this->changeset, 123, false));
    }

    public function testGetChangesetValueDoesntExist(): void
    {
        $value_dao = Mockery::mock(
            \Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkFieldValueDao::class
        );
        $value_dao->shouldReceive('searchById')->andReturn(TestHelper::emptyDar());
        $value_dao->shouldReceive('searchReverseLinksById')->andReturn(TestHelper::emptyDar());

        $field = Mockery::mock(\Tracker_FormElement_Field_ArtifactLink::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $field->shouldReceive('getValueDao')->andReturns($value_dao);

        $this->assertNotNull($field->getChangesetValue($this->changeset, 123, false));
    }

    public function testFetchRawValue(): void
    {
        $f       = Mockery::mock(\Tracker_FormElement_Field_ArtifactLink::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $art_ids = ['123, 132, 999'];
        $value   = Mockery::mock(Tracker_Artifact_ChangesetValue_ArtifactLink::class);
        $value->shouldReceive('getArtifactIds')->andReturns($art_ids);
        $this->assertEquals('123, 132, 999', $f->fetchRawValue($value));
    }

    public function testIsValidRequiredFieldWithExistingValues(): void
    {
        $field = Mockery::mock(\Tracker_FormElement_Field_ArtifactLink::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $field->shouldReceive('isRequired')->andReturns(true);

        $ids = [123];
        $cv  = Mockery::mock(Tracker_Artifact_ChangesetValue_ArtifactLink::class);
        $cv->shouldReceive('getArtifactIds')->andReturns($ids);
        $c = Mockery::mock(\Tracker_Artifact_Changeset::class);
        $c->shouldReceive('getValue')->andReturns($cv);

        $artifact = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $tracker  = Mockery::mock(\Tracker::class);
        $artifact->shouldReceive('getLastChangeset')->andReturn($c);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);

        $field->shouldReceive('getLastChangesetValue')->andReturns($cv);

        $this->assertTrue($field->isValidRegardingRequiredProperty($artifact, null));  // existing values
        $this->assertFalse($field->isValidRegardingRequiredProperty($artifact, ['new_values' => '', 'removed_values' => ['123']]));
    }

    public function testIsValidRequiredFieldWithoutExistingValues(): void
    {
        $field = Mockery::mock(\Tracker_FormElement_Field_ArtifactLink::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $field->shouldReceive('isRequired')->andReturns(true);

        $ids = [];
        $cv  = Mockery::mock(Tracker_Artifact_ChangesetValue_ArtifactLink::class);
        $cv->shouldReceive('getArtifactIds')->andReturns($ids);
        $c = Mockery::mock(\Tracker_Artifact_Changeset::class);
        $c->shouldReceive('getValue')->andReturns($cv);
        $a = Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $a->shouldReceive('getLastChangeset')->andReturns($c);

        $this->assertFalse($field->isValidRegardingRequiredProperty($a, ['new_values' => '']));
        $this->assertFalse($field->isValidRegardingRequiredProperty($a, null));
    }

    public function testIsValidAddsErrorIfARequiredFieldIsAnArrayWithoutNewValues(): void
    {
        $f = Mockery::mock(\Tracker_FormElement_Field_ArtifactLink::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $f->shouldReceive('isRequired')->andReturns(true);

        $a = Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $a->shouldReceive('getLastChangeset')->andReturns(false);
        $this->assertFalse($f->isValidRegardingRequiredProperty($a, ['new_values' => '']));
        $this->assertTrue($f->hasErrors());
    }

    public function testIsValidAddsErrorIfARequiredFieldValueIsAnEmptyString(): void
    {
        $f = Mockery::mock(\Tracker_FormElement_Field_ArtifactLink::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $f->shouldReceive('isRequired')->andReturns(true);

        $a = Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $a->shouldReceive('getLastChangeset')->andReturns(false);
        $this->assertFalse($f->isValidRegardingRequiredProperty($a, ''));
        $this->assertTrue($f->hasErrors());
    }

    public function testReturnsAnEmptyListWhenThereAreNoValuesInTheChangeset(): void
    {
        $field     = $this->buildField();
        $changeset = Mockery::spy(\Tracker_Artifact_Changeset::class);
        $user      = Mockery::mock(PFUser::class);

        $artifacts = $field->getLinkedArtifacts($changeset, $user);
        $this->assertEmpty($artifacts);
    }

    public function testReturnsAnEmptyPaginatedListWhenThereAreNoValuesInTheChangeset(): void
    {
        $field     = $this->buildField();
        $changeset = Mockery::spy(\Tracker_Artifact_Changeset::class);
        $user      = Mockery::mock(PFUser::class);

        $sliced = $field->getSlicedLinkedArtifacts($changeset, $user, 10, 0);
        $this->assertEmpty($sliced->getArtifacts());
        $this->assertEquals(0, $sliced->getTotalSize());
    }

    public function testCreatesAListOfArtifactsBasedOnTheIdsInTheChangesetField(): void
    {
        $user = Mockery::mock(PFUser::class);

        $artifact_1 = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact_1->shouldReceive('getId')->andReturn(123);
        $artifact_1->shouldReceive('userCanView')->andReturn(true);
        $artifact_2 = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact_2->shouldReceive('getId')->andReturn(345);
        $artifact_2->shouldReceive('userCanView')->andReturn(true);

        $field = $this->buildField();
        $field->setArtifactFactory($this->givenAnArtifactFactory([$artifact_1, $artifact_2]));

        $changeset = $this->givenAChangesetValueWithArtifactIds($field, [123, 345]);

        $artifacts          = $field->getLinkedArtifacts($changeset, $user);
        $expected_artifacts = [
            $artifact_1,
            $artifact_2,
        ];
        $this->assertEquals($expected_artifacts, $artifacts);
    }

    public function testCreatesAPaginatedListOfArtifactsBasedOnTheIdsInTheChangesetField(): void
    {
        $user = Mockery::mock(PFUser::class);

        $artifact_1 = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact_1->shouldReceive('getId')->andReturn(123);
        $artifact_1->shouldReceive('userCanView')->andReturn(true);
        $artifact_2 = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact_2->shouldReceive('getId')->andReturn(345);
        $artifact_2->shouldReceive('userCanView')->andReturn(true);

        $field = $this->buildField();
        $field->setArtifactFactory($this->givenAnArtifactFactory([$artifact_1, $artifact_2]));

        $changeset = $this->givenAChangesetValueWithArtifactIds($field, [123, 345]);

        $sliced             = $field->getSlicedLinkedArtifacts($changeset, $user, 10, 0);
        $expected_artifacts = [
            $artifact_1,
            $artifact_2,
        ];
        $this->assertEquals($expected_artifacts, $sliced->getArtifacts());
        $this->assertEquals(2, $sliced->getTotalSize());
    }

    public function testCreatesAFirstPageOfPaginatedListOfArtifactsBasedOnTheIdsInTheChangesetField(): void
    {
        $user = Mockery::mock(PFUser::class);

        $artifact_1 = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact_1->shouldReceive('getId')->andReturn(123);
        $artifact_1->shouldReceive('userCanView')->andReturn(true);
        $artifact_2 = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact_2->shouldReceive('getId')->andReturn(345);
        $artifact_2->shouldReceive('userCanView')->andReturn(true);

        $field = $this->buildField();
        $field->setArtifactFactory($this->givenAnArtifactFactory([$artifact_1, $artifact_2]));

        $changeset = $this->givenAChangesetValueWithArtifactIds($field, [123, 345]);

        $sliced             = $field->getSlicedLinkedArtifacts($changeset, $user, 1, 0);
        $expected_artifacts = [
            $artifact_1,
        ];
        $this->assertEquals($expected_artifacts, $sliced->getArtifacts());
        $this->assertEquals(2, $sliced->getTotalSize());
    }

    public function testCreatesASecondPageOfPaginatedListOfArtifactsBasedOnTheIdsInTheChangesetField(): void
    {
        $user = Mockery::mock(PFUser::class);

        $artifact_1 = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact_1->shouldReceive('getId')->andReturn(123);
        $artifact_1->shouldReceive('userCanView')->andReturn(true);
        $artifact_2 = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact_2->shouldReceive('getId')->andReturn(345);
        $artifact_2->shouldReceive('userCanView')->andReturn(true);

        $field = $this->buildField();
        $field->setArtifactFactory($this->givenAnArtifactFactory([$artifact_1, $artifact_2]));

        $changeset = $this->GivenAChangesetValueWithArtifactIds($field, [123, 345]);

        $sliced             = $field->getSlicedLinkedArtifacts($changeset, $user, 1, 1);
        $expected_artifacts = [
            $artifact_2,
        ];
        $this->assertEquals($expected_artifacts, $sliced->getArtifacts());
        $this->assertEquals(2, $sliced->getTotalSize());
    }

    public function testIgnoresIdsThatDontExist(): void
    {
        $user     = Mockery::mock(PFUser::class);
        $artifact = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->shouldReceive('getId')->andReturn(123);
        $artifact->shouldReceive('userCanView')->andReturn(true);

        $field = $this->buildField();
        $field->setArtifactFactory($this->givenAnArtifactFactory([$artifact]));

        $non_existing_id = 666;
        $changeset       = $this->givenAChangesetValueWithArtifactIds($field, [123, $non_existing_id]);

        $artifacts          = $field->getLinkedArtifacts($changeset, $user);
        $expected_artifacts = [$artifact];
        $this->assertEquals($expected_artifacts, $artifacts);
    }

    public function testIgnoresInPaginatedListIdsThatDontExist(): void
    {
        $user     = Mockery::mock(PFUser::class);
        $artifact = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->shouldReceive('getId')->andReturn(123);
        $artifact->shouldReceive('userCanView')->andReturn(true);

        $field = $this->buildField();
        $field->setArtifactFactory($this->givenAnArtifactFactory([$artifact]));

        $non_existing_id = 666;
        $changeset       = $this->givenAChangesetValueWithArtifactIds($field, [123, $non_existing_id]);

        $sliced             = $field->getSlicedLinkedArtifacts($changeset, $user, 10, 0);
        $expected_artifacts = [$artifact];
        $this->assertEquals($expected_artifacts, $sliced->getArtifacts());
        $this->assertEquals(2, $sliced->getTotalSize());
    }

    public function testReturnsOnlyArtifactsAccessibleByGivenUser(): void
    {
        $user = Mockery::mock(PFUser::class);

        $artifact_1 = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact_1->shouldReceive('getId')->andReturn(123);
        $artifact_1->shouldReceive('userCanView')->with($user)->andReturn(false);
        $artifact_2 = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact_2->shouldReceive('getId')->andReturn(345);
        $artifact_2->shouldReceive('userCanView')->with($user)->andReturn(true);

        $field = $this->buildField();
        $field->setArtifactFactory($this->givenAnArtifactFactory([$artifact_1, $artifact_2]));

        $changeset = $this->givenAChangesetValueWithArtifactIds($field, [123, 345]);

        $artifacts          = $field->getLinkedArtifacts($changeset, $user);
        $expected_artifacts = [
            $artifact_2,
        ];
        $this->assertEquals($expected_artifacts, $artifacts);
    }

    public function testReturnsOnlyPaginatedArtifactsAccessibleByGivenUser(): void
    {
        $user = Mockery::mock(PFUser::class);

        $artifact_1 = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact_1->shouldReceive('getId')->andReturn(123);
        $artifact_1->shouldReceive('userCanView')->with($user)->andReturn(false);
        $artifact_2 = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact_2->shouldReceive('getId')->andReturn(345);
        $artifact_2->shouldReceive('userCanView')->with($user)->andReturn(true);

        $field = $this->buildField();
        $field->setArtifactFactory($this->givenAnArtifactFactory([$artifact_1, $artifact_2]));

        $changeset = $this->givenAChangesetValueWithArtifactIds($field, [123, 345]);

        $sliced             = $field->getSlicedLinkedArtifacts($changeset, $user, 10, 0);
        $expected_artifacts = [
            $artifact_2,
        ];
        $this->assertEquals($expected_artifacts, $sliced->getArtifacts());
        $this->assertEquals(2, $sliced->getTotalSize());
    }

    public function testReturnsAFirstPageOfOnlyPaginatedArtifactsAccessibleByGivenUser(): void
    {
        $user = Mockery::mock(PFUser::class);

        $artifact_1 = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact_1->shouldReceive('getId')->andReturn(123);
        $artifact_1->shouldReceive('userCanView')->with($user)->andReturn(false);
        $artifact_2 = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact_2->shouldReceive('getId')->andReturn(345);
        $artifact_2->shouldReceive('userCanView')->with($user)->andReturn(true);

        $field = $this->buildField();
        $field->setArtifactFactory($this->givenAnArtifactFactory([$artifact_1, $artifact_2]));

        $changeset = $this->givenAChangesetValueWithArtifactIds($field, [123, 345]);

        $sliced             = $field->getSlicedLinkedArtifacts($changeset, $user, 1, 0);
        $expected_artifacts = [];
        $this->assertEquals($expected_artifacts, $sliced->getArtifacts());
        $this->assertEquals(2, $sliced->getTotalSize());
    }

    public function testReturnsASecondPageOfOnlyPaginatedArtifactsAccessibleByGivenUser(): void
    {
        $user = Mockery::mock(PFUser::class);

        $artifact_1 = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact_1->shouldReceive('getId')->andReturn(123);
        $artifact_1->shouldReceive('userCanView')->with($user)->andReturn(false);
        $artifact_2 = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact_2->shouldReceive('getId')->andReturn(345);
        $artifact_2->shouldReceive('userCanView')->with($user)->andReturn(true);

        $field = $this->buildField();
        $field->setArtifactFactory($this->GivenAnArtifactFactory([$artifact_1, $artifact_2]));

        $changeset = $this->GivenAChangesetValueWithArtifactIds($field, [123, 345]);

        $sliced             = $field->getSlicedLinkedArtifacts($changeset, $user, 1, 1);
        $expected_artifacts = [
            $artifact_2,
        ];
        $this->assertEquals($expected_artifacts, $sliced->getArtifacts());
        $this->assertEquals(2, $sliced->getTotalSize());
    }

    public function testItThrowsAnExceptionWhenReturningValueIndexedByFieldName(): void
    {
        $field = $this->buildField();

        $this->expectException(Tracker_FormElement_RESTValueByField_NotImplementedException::class);

        $value = 'some_value';

        $field->getFieldDataFromRESTValueByField($value);
    }

    public function testGetFieldDataFromRESTValueExtractsLinksFromRESTValue(): void
    {
        $user_manager = $this->createMock(UserManager::class);
        $user_manager
            ->method('getCurrentUser')
            ->willReturn(\Tuleap\Test\Builders\UserTestBuilder::anAnonymousUser()->build());
        UserManager::setInstance($user_manager);

        $field = $this->buildField();

        self::assertEquals(
            [
                'new_values'     => '123,234',
                'removed_values' => [],
                'types'          => [
                    234 => '_is_child',
                ],
            ],
            $field->getFieldDataFromRESTValue([
                "links" => [
                    ["id" => 123],
                    ["id" => 234, "type" => "_is_child"],
                ],
            ], null),
        );
    }

    public function testGetFieldDataFromRESTValueExtractsParentFromRESTValue(): void
    {
        $user_manager = $this->createMock(UserManager::class);
        $user_manager
            ->method('getCurrentUser')
            ->willReturn(\Tuleap\Test\Builders\UserTestBuilder::anAnonymousUser()->build());
        UserManager::setInstance($user_manager);

        $field = $this->buildField();

        self::assertEquals(
            [
                "parent" => [123],
            ],
            $field->getFieldDataFromRESTValue(["parent" => ["id" => 123]], null),
        );
    }

    public function testGetFieldDataFromRESTValueExtractsBothParentAndLinksFromRESTValue(): void
    {
        $user_manager = $this->createMock(UserManager::class);
        $user_manager
            ->method('getCurrentUser')
            ->willReturn(\Tuleap\Test\Builders\UserTestBuilder::anAnonymousUser()->build());
        UserManager::setInstance($user_manager);

        $field = $this->buildField();

        self::assertEquals(
            [
                "parent" => [123],
                'new_values'     => '124,234',
                'removed_values' => [],
                'types'          => [
                    234 => '_is_child',
                ],
            ],
            $field->getFieldDataFromRESTValue([
                "parent" => ["id" => 123],
                "links" => [
                    ["id" => 124],
                    ["id" => 234, "type" => "_is_child"],
                ],
            ], null),
        );
    }

    public function testGetFieldDataFromRESTValueRemovesExistingLinksIfTheyAreNotProvided(): void
    {
        $user_manager = $this->createMock(UserManager::class);
        $user_manager
            ->method('getCurrentUser')
            ->willReturn(\Tuleap\Test\Builders\UserTestBuilder::anAnonymousUser()->build());
        UserManager::setInstance($user_manager);

        $field = $this->buildField();

        $changeset = $this->createMock(Tracker_Artifact_Changeset::class);
        $changeset
            ->method("getId")
            ->willReturn(1001);

        $artifact = $this->createMock(Artifact::class);
        $artifact
            ->method("getLastChangeset")
            ->willReturn($changeset);
        $artifact
            ->method('getAnArtifactLinkField')
            ->willReturn($field);

        $dao = $this->createMock(\Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ChangesetValueArtifactLinkDao::class);
        $dao
            ->method("searchChangesetValues")
            ->willReturn(
                [
                    [
                        "changeset_id"      => 1001,
                        "artifact_id"       => 666,
                        "keyword"           => "art",
                        "group_id"          => 101,
                        "tracker_id"        => 102,
                        "last_changeset_id" => 1000,
                        "nature"            => "",
                    ],
                ]
            );

        $existing_link = $this->createMock(Artifact::class);
        $existing_link
            ->method("userCanView")
            ->willReturn(true);
        $existing_link
            ->method("getId")
            ->willReturn(666);
        $existing_link
            ->method('getTracker')
            ->willReturn(TrackerTestBuilder::aTracker()->withId(101)->build());
        $existing_link
            ->method('getLastChangeset')
            ->willReturn(\Tuleap\Tracker\Test\Builders\ChangesetTestBuilder::aChangeset('100001')->build());

        Tracker_ArtifactFactory::setInstance($this->givenAnArtifactFactory([$existing_link]));

        $field->setChangesetValueArtifactLinkDao($dao);

        self::assertEquals(
            [
                "parent" => [123],
                'new_values'     => '124,234',
                'removed_values' => [666 => [666]],
                'types'          => [
                    234 => '_is_child',
                ],
            ],
            $field->getFieldDataFromRESTValue([
                "parent" => ["id" => 123],
                "links" => [
                    ["id" => 124],
                    ["id" => 234, "type" => "_is_child"],
                ],
            ], $artifact),
        );
    }

    public function testItDoesNotRaiseWarningIfItDoesNotHaveAllInformationToDisplayAnAsyncRenderer(): void
    {
        $field = $this->buildField();

        $field->process(
            $this->createMock(Tracker_IDisplayTrackerLayout::class),
            new class extends Codendi_Request {
                public function __construct()
                {
                    parent::__construct([]);
                }

                public function get($variable)
                {
                    return [
                        'func' => 'artifactlink-renderer-async',
                        'renderer_data' => json_encode(["artifact_id" => 123]),
                    ][$variable];
                }

                public function isAjax()
                {
                    return true;
                }
            },
            \Tuleap\Test\Builders\UserTestBuilder::anAnonymousUser()->build()
        );
    }

    private function buildField(): Tracker_FormElement_Field_ArtifactLink
    {
        return new Tracker_FormElement_Field_ArtifactLink(
            1,
            101,
            null,
            'field_artlink',
            'Field ArtLink',
            '',
            1,
            'P',
            true,
            '',
            1
        );
    }

    private function givenAChangesetValueWithArtifactIds(Tracker_FormElement_Field_ArtifactLink $field, array $ids): Tracker_Artifact_Changeset
    {
        $changeset_value = Mockery::spy(Tracker_Artifact_ChangesetValue_ArtifactLink::class);
        $changeset_value->shouldReceive('getArtifactIds')->andReturns($ids);
        $changeset = Mockery::spy(\Tracker_Artifact_Changeset::class);
        $changeset->shouldReceive('getValue')->with($field)->andReturns($changeset_value);
        return $changeset;
    }

    private function givenAnArtifactFactory(array $artifacts): Tracker_ArtifactFactory
    {
        $factory = Mockery::spy(\Tracker_ArtifactFactory::class);
        foreach ($artifacts as $a) {
            $factory->shouldReceive('getArtifactById')->with($a->getId())->andReturns($a);
        }
        return $factory;
    }
}
