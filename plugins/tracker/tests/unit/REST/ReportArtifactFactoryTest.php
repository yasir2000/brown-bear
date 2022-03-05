<?php
/**
 * Copyright (c) Enalean, 2017 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\v1;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\Tracker\Report\Query\FromWhere;

class ReportArtifactFactoryTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var ReportArtifactFactory */
    private $report_artifact_factory;
    /** @var \Tracker_ArtifactFactory */
    private $tracker_artifact_factory;

    protected function setUp(): void
    {
        $this->tracker_artifact_factory = \Mockery::spy(\Tracker_ArtifactFactory::class);

        $this->report_artifact_factory = new ReportArtifactFactory(
            $this->tracker_artifact_factory
        );
    }

    public function testItReturnsAnEmptyCollectionWhenTheReportDoesNotMatchArtifacts(): void
    {
        $empty_report = \Mockery::spy(\Tracker_Report::class);
        $from_where   = new FromWhere('', '');

        $collection = $this->report_artifact_factory->getArtifactsMatchingReportWithAdditionalFromWhere(
            $empty_report,
            $from_where,
            10,
            0
        );

        $this->assertEquals([], $collection->getArtifacts());
        $this->assertEquals(0, $collection->getTotalSize());
    }

    public function testItReturnsACollectionOfMatchingArtifactsCorrespondingToLimitAndOffset(): void
    {
        $report     = \Mockery::spy(\Tracker_Report::class);
        $from_where = new FromWhere('', '');

        $report->shouldReceive('getMatchingIdsWithAdditionalFromWhere')->andReturns(['id' => '12,85,217,98']);
        $artifact_one = Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact_one->shouldReceive('getId')->andReturn(85);
        $artifact_two = Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact_two->shouldReceive('getId')->andReturn(217);
        $this->tracker_artifact_factory->shouldReceive('getArtifactsByArtifactIdList')->andReturns([$artifact_one, $artifact_two]);

        $collection = $this->report_artifact_factory->getArtifactsMatchingReportWithAdditionalFromWhere(
            $report,
            $from_where,
            2,
            1
        );

        $this->assertEquals([$artifact_one, $artifact_two], $collection->getArtifacts());
        $this->assertEquals(4, $collection->getTotalSize());
    }
}
