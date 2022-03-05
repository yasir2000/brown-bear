<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Timeframe;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\GlobalResponseMock;

class SemanticTimeframeUpdatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalResponseMock;

    /**
     * @var SemanticTimeframeDao
     */
    private $semantic_timeframe_dao;

    /**
     * @var SemanticTimeframeUpdator
     */
    private $updator;

    /**
     * @var \Tracker
     */
    private $tracker;

    /**
     * @var \Tracker_FormElementFactory
     */
    private $formelement_factory;

    /**
     * @var int
     */
    private $tracker_id;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|SemanticTimeframeSuitableTrackersOtherSemanticsCanBeImpliedFromRetriever
     */
    private $suitable_trackers_retriever;

    protected function setUp(): void
    {
        $this->semantic_timeframe_dao      = Mockery::mock(SemanticTimeframeDao::class);
        $this->tracker                     = Mockery::mock(\Tracker::class);
        $this->formelement_factory         = Mockery::mock(\Tracker_FormElementFactory::class);
        $this->suitable_trackers_retriever = Mockery::mock(SemanticTimeframeSuitableTrackersOtherSemanticsCanBeImpliedFromRetriever::class);
        $this->updator                     = new SemanticTimeframeUpdator(
            $this->semantic_timeframe_dao,
            $this->formelement_factory,
            $this->suitable_trackers_retriever
        );

        $this->tracker_id = 123;
        $this->tracker->shouldReceive('getId')->andReturn($this->tracker_id);
    }

    public function testItDoesNotUpdateIfAFieldIdIsNotNumeric(): void
    {
        $request = new \Codendi_Request([
            'start-date-field-id' => 'start',
            'duration-field-id'   => '1234',
        ]);

        $this->semantic_timeframe_dao->shouldReceive("save")->never();

        $GLOBALS['Response']->expects(self::once())->method('addFeedback')->with(
            \Feedback::ERROR,
            'An error occurred while updating the timeframe semantic'
        );

        $this->updator->update($this->tracker, $request);
    }

    public function testItDoesNotUpdateIfStartDateFieldIdIsMissing(): void
    {
        $request = new \Codendi_Request([
            'start-date-field-id' => '',
            'duration-field-id'   => '5678',
        ]);

        $this->semantic_timeframe_dao->shouldReceive("save")->never();

        $GLOBALS['Response']->expects(self::once())->method('addFeedback')->with(
            \Feedback::ERROR,
            'An error occurred while updating the timeframe semantic'
        );

        $this->updator->update($this->tracker, $request);
    }

    public function testItDoesNotUpdateIfDurationFieldIdIsMissing(): void
    {
        $request = new \Codendi_Request([
            'start-date-field-id' => '1234',
            'duration-field-id'   => '',
        ]);

        $this->semantic_timeframe_dao->shouldReceive("save")->never();

        $GLOBALS['Response']->expects(self::once())->method('addFeedback')->with(
            \Feedback::ERROR,
            'An error occurred while updating the timeframe semantic'
        );

        $this->updator->update($this->tracker, $request);
    }

    public function testItDoesNotUpdateIfAFieldCannotBeFoundInTheTracker(): void
    {
        $request = new \Codendi_Request([
            'start-date-field-id' => '1234',
            'duration-field-id'   => '5678',
        ]);

        $this->formelement_factory->shouldReceive("getUsedDateFieldById")->with($this->tracker, 1234)->andReturn(null);
        $this->semantic_timeframe_dao->shouldReceive("save")->never();

        $GLOBALS['Response']->expects(self::once())->method('addFeedback')->with(
            \Feedback::ERROR,
            'An error occurred while updating the timeframe semantic'
        );

        $this->updator->update($this->tracker, $request);
    }

    public function testItUpdatesTheSemanticWithDuration(): void
    {
        $start_date_field_id = 1234;
        $duration_field_id   = 5678;
        $request             = new \Codendi_Request([
            'start-date-field-id' => $start_date_field_id,
            'duration-field-id'   => $duration_field_id,
        ]);

        $start_date_field = Mockery::mock(\Tracker_FormElement_Field_Date::class);
        $duration_field   = Mockery::mock(\Tracker_FormElement_Field_Integer::class);

        $start_date_field->shouldReceive('getTrackerId')->andReturn($this->tracker_id);
        $duration_field->shouldReceive('getTrackerId')->andReturn($this->tracker_id);

        $this->formelement_factory->shouldReceive("getUsedDateFieldById")->with($this->tracker, $start_date_field_id)->andReturn($start_date_field);
        $this->formelement_factory->shouldReceive("getUsedFieldByIdAndType")->with(
            $this->tracker,
            $duration_field_id,
            ['int', 'float', 'computed']
        )->andReturn($duration_field);

        $this->semantic_timeframe_dao->shouldReceive("save")->with(
            $this->tracker_id,
            $start_date_field_id,
            $duration_field_id,
            null,
            null
        )->andReturn(true)->once();

        $GLOBALS['Response']->expects(self::once())->method('addFeedback')->with(
            \Feedback::INFO,
            'Semantic timeframe updated successfully'
        );

        $this->updator->update($this->tracker, $request);
    }

    public function testItUpdatesTheSemanticWithEndDate(): void
    {
        $start_date_field_id = 1234;
        $end_date_field_id   = 5678;
        $request             = new \Codendi_Request([
            'start-date-field-id' => $start_date_field_id,
            'end-date-field-id'   => $end_date_field_id,
        ]);

        $start_date_field = Mockery::mock(\Tracker_FormElement_Field_Date::class);
        $end_date_field   = Mockery::mock(\Tracker_FormElement_Field_Date::class);

        $start_date_field->shouldReceive('getTrackerId')->andReturn($this->tracker_id);
        $end_date_field->shouldReceive('getTrackerId')->andReturn($this->tracker_id);

        $this->formelement_factory->shouldReceive("getUsedDateFieldById")->with($this->tracker, $start_date_field_id)->andReturn($start_date_field);
        $this->formelement_factory->shouldReceive("getUsedDateFieldById")->with($this->tracker, $end_date_field_id)->andReturn($end_date_field);

        $this->semantic_timeframe_dao->shouldReceive("save")->with(
            $this->tracker_id,
            $start_date_field_id,
            null,
            $end_date_field_id,
            null
        )->andReturn(true)->once();

        $GLOBALS['Response']->expects(self::once())->method('addFeedback')->with(
            \Feedback::INFO,
            'Semantic timeframe updated successfully'
        );

        $this->updator->update($this->tracker, $request);
    }


    public function testItUpdatesTheSemanticWhenItIsImpliedFromAnotherTracker(): void
    {
        $sprints_tracker_id = 150;
        $request            = new \Codendi_Request(
            [
                'implied-from-tracker-id' => $sprints_tracker_id,
            ]
        );

        $this->suitable_trackers_retriever->shouldReceive('getTrackersWeCanUseToImplyTheSemanticOfTheCurrentTrackerFrom')
            ->with($this->tracker)
            ->andReturn([
                '150' => Mockery::mock(\Tracker::class),
            ]);

        $this->semantic_timeframe_dao->shouldReceive("save")->with(
            $this->tracker_id,
            null,
            null,
            null,
            $sprints_tracker_id
        )->andReturn(true)->once();

        $GLOBALS['Response']->expects(self::once())->method('addFeedback')->with(
            \Feedback::INFO,
            'Semantic timeframe updated successfully'
        );

        $this->updator->update($this->tracker, $request);
    }

    public function testItRejectsIfBothDurationAndEndDateAreSent(): void
    {
        $start_date_field_id = 1234;
        $end_date_field_id   = 5678;
        $duration_field_id   = 345;

        $request = new \Codendi_Request([
            'start-date-field-id' => $start_date_field_id,
            'duration-field-id'   => $duration_field_id,
            'end-date-field-id'   => $end_date_field_id,
        ]);

        $start_date_field = Mockery::mock(\Tracker_FormElement_Field_Date::class);
        $end_date_field   = Mockery::mock(\Tracker_FormElement_Field_Date::class);
        $duration_field   = Mockery::mock(\Tracker_FormElement_Field_Numeric::class);

        $start_date_field->shouldReceive('getTrackerId')->andReturn($this->tracker_id);
        $end_date_field->shouldReceive('getTrackerId')->andReturn($this->tracker_id);
        $duration_field->shouldReceive('getTrackerId')->andReturn($this->tracker_id);

        $this->formelement_factory->shouldReceive("getUsedDateFieldById")->with($this->tracker, $start_date_field_id)->andReturn($start_date_field);
        $this->formelement_factory->shouldReceive("getUsedDateFieldById")->with($this->tracker, $end_date_field_id)->andReturn($end_date_field);
        $this->formelement_factory->shouldReceive("getUsedFieldByIdAndType")->with(
            $this->tracker,
            $duration_field_id,
            ['int', 'float', 'computed']
        )->andReturn($duration_field);

        $this->semantic_timeframe_dao->shouldReceive("save")->never();

        $GLOBALS['Response']->expects(self::once())->method('addFeedback')->with(
            \Feedback::ERROR,
            dgettext('tuleap-tracker', 'An error occurred while updating the timeframe semantic')
        );

        $this->updator->update($this->tracker, $request);
    }

    public function testItDoesNotResetWhenSomeTimeframeConfigurationsRelyOnTheCurrentOne(): void
    {
        $sprint_tracker_id = 106;
        $sprint_tracker    = Mockery::mock(\Tracker::class, ['getId' => $sprint_tracker_id]);

        $this->semantic_timeframe_dao->shouldReceive('getSemanticsImpliedFromGivenTracker')
            ->with($sprint_tracker_id)
            ->andReturn([
                [
                    'tracker_id' => 104,
                    'implied_from_tracker_id' => $sprint_tracker_id,
                ],
            ])
            ->once();

        $this->semantic_timeframe_dao->shouldReceive('deleteTimeframeSemantic')->never();

        $GLOBALS['Response']->expects(self::once())->method('addFeedback')->with(
            \Feedback::ERROR,
            'You cannot reset this semantic because some trackers inherit their own semantic timeframe from this one.'
        );

        $this->updator->reset($sprint_tracker);
    }

    /**
     * @testWith [null]
     *           [[]]
     */
    public function testItResetsTheTimeframeSemanticOfAGivenTracker(?array $implied_semantics): void
    {
        $sprint_tracker_id = 106;
        $sprint_tracker    = Mockery::mock(\Tracker::class, ['getId' => $sprint_tracker_id]);

        $this->semantic_timeframe_dao->shouldReceive('getSemanticsImpliedFromGivenTracker')
            ->with($sprint_tracker_id)
            ->andReturn($implied_semantics)
            ->once();

        $this->semantic_timeframe_dao->shouldReceive('deleteTimeframeSemantic')
            ->with($sprint_tracker_id)
            ->once();

        $GLOBALS['Response']->expects(self::once())->method('addFeedback')->with(
            \Feedback::INFO,
            'Semantic timeframe reset successfully'
        );

        $this->updator->reset($sprint_tracker);
    }
}
