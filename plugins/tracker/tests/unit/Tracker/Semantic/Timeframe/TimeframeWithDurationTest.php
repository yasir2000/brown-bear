<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Timeframe;

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\SemanticTimeframeWithDurationRepresentation;

class TimeframeWithDurationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var TimeframeWithEndDate
     */
    private $timeframe;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Tracker_FormElement_Field_Date
     */
    private $start_date_field;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Tracker_FormElement_Field_Integer
     */
    private $duration_field;
    /**
     * @var MockObject|Artifact
     */
    private $artifact;
    /**
     * @var \PFUser|MockObject
     */
    private $user;

    protected function setUp(): void
    {
        $this->start_date_field = $this->getMockedDateField(1001);
        $this->duration_field   = $this->getMockedDurationField(1002);
        $this->artifact         = $this->getMockBuilder(Artifact::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->user = $this->getMockBuilder(\PFUser::class)->disableOriginalConstructor()->getMock();

        $this->timeframe = new TimeframeWithDuration(
            $this->start_date_field,
            $this->duration_field
        );
    }

    /**
     * @testWith [1001, true]
     *           [1002, true]
     *           [1003, false]
     */
    public function testItReturnsTrueWhenFieldIsUsed(int $field_id, bool $is_used): void
    {
        $field = $this->getMockedDateField($field_id);

        $this->assertEquals(
            $is_used,
            $this->timeframe->isFieldUsed($field)
        );
    }

    public function testItReturnsItsConfigDescription(): void
    {
        $this->start_date_field->expects(self::any())->method('getLabel')->will(self::returnValue('Start date'));
        $this->duration_field->expects(self::any())->method('getLabel')->will(self::returnValue('Duration'));

        $this->assertEquals(
            'Timeframe is based on start date field "Start date" and duration field "Duration".',
            $this->timeframe->getConfigDescription()
        );
    }

    public function testItIsDefined(): void
    {
        $this->assertTrue($this->timeframe->isDefined());
    }

    public function testItDoesNotExportToXMLIfStartDateIsNotInFieldMapping(): void
    {
        $root = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $this->timeframe->exportToXml($root, []);

        $this->assertCount(0, $root->children());
    }

    public function testItDoesNotExportToXMLIfDurationIsNotInFieldMapping(): void
    {
        $root = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $this->timeframe->exportToXml($root, [
            'F101' => 1001,
        ]);

        $this->assertCount(0, $root->children());
    }

    public function testItExportsToXML(): void
    {
        $root = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $this->timeframe->exportToXml($root, [
            'F101' => 1001,
            'F102' => 1002,
        ]);

        $this->assertCount(1, $root->children());
        $this->assertEquals('timeframe', (string) $root->semantic['type']);
        $this->assertEquals('F101', (string) $root->semantic->start_date_field['REF']);
        $this->assertEquals('F102', (string) $root->semantic->duration_field['REF']);
    }

    /**
     * @testWith [false, false]
     *           [true, false]
     */
    public function testItDoesNotExportToRESTWhenUserCanReadFields(bool $can_read_start_date, bool $can_read_duration): void
    {
        $this->start_date_field->expects(self::any())->method('userCanRead')->will(self::returnValue($can_read_start_date));
        $this->duration_field->expects(self::any())->method('userCanRead')->will(self::returnValue($can_read_duration));

        $this->assertNull($this->timeframe->exportToREST($this->user));
    }

    public function testItExportsToREST(): void
    {
        $this->start_date_field->expects(self::any())->method('userCanRead')->will(self::returnValue(true));
        $this->duration_field->expects(self::any())->method('userCanRead')->will(self::returnValue(true));

        $this->assertEquals(
            new SemanticTimeframeWithDurationRepresentation(
                1001,
                1002
            ),
            $this->timeframe->exportToREST($this->user)
        );
    }

    public function testItSaves(): void
    {
        $dao     = $this->getMockBuilder(SemanticTimeframeDao::class)->disableOriginalConstructor()->getMock();
        $tracker = $this->getMockBuilder(\Tracker::class)->disableOriginalConstructor()->getMock();

        $dao->expects(self::once())->method('save')->with(113, 1001, 1002, null, null)->will(self::returnValue(true));
        $tracker->expects(self::once())->method('getId')->will(self::returnValue(113));

        self::assertTrue(
            $this->timeframe->save($tracker, $dao)
        );
    }

    public function testItBuildATimePeriodWithoutWeekObjectForArtifactForREST(): void
    {
        // Sprint 10 days, from `Monday, Jul 1, 2013` to `Monday, Jul 15, 2013`
        $duration          = 10;
        $start_date        = '07/01/2013';
        $expected_end_date = '07/15/2013';

        $this->start_date_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));
        $this->duration_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));

        $this->mockStartDateFieldWithValue($start_date);
        $this->mockDurationFieldWithValue($duration);

        $time_period = $this->timeframe->buildTimePeriodWithoutWeekendForArtifactForREST(
            $this->artifact,
            $this->user,
            new NullLogger()
        );

        $this->assertSame(strtotime($start_date), $time_period->getStartDate());
        $this->assertSame(strtotime($expected_end_date), $time_period->getEndDate());
        $this->assertSame(10, $time_period->getDuration());
    }

    public function testItBuildsATimePeriodWithoutWeekObjectForRESTWithStartDateAsNullForArtifactIfNoLastChangesetValueForStartDate(): void
    {
        $duration = 10;

        $this->start_date_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));
        $this->duration_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));

        $this->start_date_field->expects(self::once())->method('getLastChangesetValue')
            ->with($this->artifact)
            ->will(self::returnValue(null));

        $this->mockDurationFieldWithValue($duration);

        $time_period = $this->timeframe->buildTimePeriodWithoutWeekendForArtifactForREST(
            $this->artifact,
            $this->user,
            new NullLogger()
        );

        $this->assertNull($time_period->getStartDate());
        $this->assertSame(1209600, $time_period->getEndDate());
        $this->assertSame(10, $time_period->getDuration());
    }

    public function testItBuildsATimePeriodForRESTWithNullDurationWhenDurationFieldHasNoLastChangeset(): void
    {
        $start_date = '07/01/2013';

        $this->start_date_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));
        $this->duration_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));

        $this->mockStartDateFieldWithValue($start_date);
        $this->duration_field->expects(self::once())->method('getLastChangesetValue')
            ->with($this->artifact)
            ->will(self::returnValue(null));

        $time_period = $this->timeframe->buildTimePeriodWithoutWeekendForArtifactForREST(
            $this->artifact,
            $this->user,
            new NullLogger()
        );

        $this->assertSame(strtotime($start_date), $time_period->getStartDate());
        self::assertNull($time_period->getEndDate());
        self::assertNull($time_period->getDuration());
    }

    public function testItBuildsTimePeriodWithoutWeekendsForArtifacts(): void
    {
        // Sprint 10 days, from `Monday, Jul 1, 2013` to `Monday, Jul 15, 2013`
        $duration          = 10;
        $start_date        = '07/01/2013';
        $expected_end_date = '07/15/2013';

        $this->start_date_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));
        $this->duration_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));

        $this->mockStartDateFieldWithValue($start_date);
        $this->mockDurationFieldWithValue($duration);

        $time_period = $this->timeframe->buildTimePeriodWithoutWeekendForArtifact(
            $this->artifact,
            $this->user,
            new NullLogger()
        );

        $this->assertSame(strtotime($start_date), $time_period->getStartDate());
        $this->assertSame(strtotime($expected_end_date), $time_period->getEndDate());
        $this->assertSame(10, $time_period->getDuration());
    }

    public function testItBuildsATimePeriodWithoutWeekObjectWithStartDateAsZeroForArtifactIfNoLastChangesetValueForStartDate(): void
    {
        $duration = 10;

        $this->start_date_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));
        $this->duration_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));

        $this->start_date_field->expects(self::once())->method('getLastChangesetValue')
            ->with($this->artifact)
            ->will(self::returnValue(null));
        $this->mockDurationFieldWithValue($duration);

        $time_period = $this->timeframe->buildTimePeriodWithoutWeekendForArtifact(
            $this->artifact,
            $this->user,
            new NullLogger()
        );

        $this->assertSame(0, $time_period->getStartDate());
        $this->assertSame(1209600, $time_period->getEndDate());
        $this->assertSame(10, $time_period->getDuration());
    }

    public function testItBuildsATimePeriodWithZeroDurationWhenDurationFieldHasNoLastChangeset(): void
    {
        $start_date = '07/01/2013';

        $this->start_date_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));
        $this->duration_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));

        $this->mockStartDateFieldWithValue($start_date);
        $this->duration_field->expects(self::once())->method('getLastChangesetValue')
            ->with($this->artifact)
            ->will(self::returnValue(null));

        $time_period = $this->timeframe->buildTimePeriodWithoutWeekendForArtifact(
            $this->artifact,
            $this->user,
            new NullLogger()
        );

        $this->assertSame(strtotime($start_date), $time_period->getStartDate());
        $this->assertSame(strtotime($start_date), $time_period->getEndDate());
        $this->assertSame(0, $time_period->getDuration());
    }

    public function testItBuildsATimePeriodForChartWhenStartDateAndDurationAreSet(): void
    {
        // Sprint 10 days, from `Monday, Jul 1, 2013` to `Monday, Jul 15, 2013`
        $duration          = 10;
        $start_date        = '07/01/2013';
        $expected_end_date = '07/15/2013';

        $this->start_date_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));
        $this->duration_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));

        $this->mockStartDateFieldWithValue($start_date);
        $this->mockDurationFieldWithValue($duration);

        $time_period = $this->timeframe->buildTimePeriodWithoutWeekendForArtifactChartRendering(
            $this->artifact,
            $this->user,
            new NullLogger()
        );

        $this->assertSame(strtotime($start_date), $time_period->getStartDate());
        $this->assertSame(strtotime($expected_end_date), $time_period->getEndDate());
        $this->assertSame(10, $time_period->getDuration());
    }

    public function testItThrowsAnExceptionWhenStartDateIsEmptyOrHasNoValueInChartContext(): void
    {
        $this->start_date_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));

        $start_date_changeset = self::getMockBuilder(\Tracker_Artifact_ChangesetValue_Date::class)
            ->disableOriginalConstructor()
            ->getMock();

        $start_date_changeset->expects(self::once())->method('getTimestamp')->will(self::returnValue(null));

        $this->start_date_field->expects(self::once())->method('getLastChangesetValue')
            ->with($this->artifact)
            ->will(self::returnValue($start_date_changeset));

        self::expectException(\Tracker_FormElement_Chart_Field_Exception::class);

        $this->timeframe->buildTimePeriodWithoutWeekendForArtifactChartRendering(
            $this->artifact,
            $this->user,
            new NullLogger()
        );
    }

    /**
     * @testWith [-1]
     *           [0]
     *           [1]
     *           [null]
     */
    public function testItThrowsAnExceptionWhenDurationHasParticularValuesInChartContext(?int $duration): void
    {
        $start_date = '07/01/2013';

        $this->start_date_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));
        $this->duration_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));

        $this->mockStartDateFieldWithValue($start_date);
        $this->mockDurationFieldWithValue($duration);

        $this->expectException(\Tracker_FormElement_Chart_Field_Exception::class);

        $this->timeframe->buildTimePeriodWithoutWeekendForArtifactChartRendering(
            $this->artifact,
            $this->user,
            new NullLogger()
        );
    }

    public function testItThrowsAnExceptionWhenDurationHasNoLastChangesetValueInChartContext(): void
    {
        $start_date = '07/01/2013';

        $this->start_date_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));
        $this->duration_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));

        $this->mockStartDateFieldWithValue($start_date);
        $this->duration_field->expects(self::once())->method('getLastChangesetValue')
            ->with($this->artifact)
            ->will(self::returnValue(null));

        $this->expectException(\Tracker_FormElement_Chart_Field_Exception::class);

        $this->timeframe->buildTimePeriodWithoutWeekendForArtifactChartRendering(
            $this->artifact,
            $this->user,
            new NullLogger()
        );
    }

    private function getMockedDateField(int $field_id): \Tracker_FormElement_Field_Date
    {
        $mock = $this->getMockBuilder(\Tracker_FormElement_Field_Date::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mock->expects(self::any())->method('getId')->will(self::returnValue($field_id));

        return $mock;
    }

    private function getMockedDurationField(int $field_id)
    {
        $mock = $this->getMockBuilder(\Tracker_FormElement_Field_Integer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mock->expects(self::any())->method('getId')->will(self::returnValue($field_id));

        return $mock;
    }

    private function mockStartDateFieldWithValue(?string $string_date): void
    {
        $timestamp            = $string_date !== null ? strtotime($string_date) : null;
        $date_changeset_value = self::getMockBuilder(\Tracker_Artifact_ChangesetValue_Date::class)
            ->disableOriginalConstructor()
            ->getMock();

        $date_changeset_value->expects(self::any())->method('getTimestamp')->will(self::returnValue($timestamp));

        $this->start_date_field->expects(self::any())->method('getLastChangesetValue')
            ->with($this->artifact)
            ->will(self::returnValue($date_changeset_value));
    }

    private function mockDurationFieldWithValue(?int $duration): void
    {
        $duration_changeset_value = self::getMockBuilder(\Tracker_Artifact_ChangesetValue_Integer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $duration_changeset_value->expects(self::any())->method('getNumeric')->will(self::returnValue($duration));

        $this->duration_field->expects(self::any())->method('getLastChangesetValue')
            ->with($this->artifact)
            ->will(self::returnValue($duration_changeset_value));
    }
}
