<?php
/**
 *  Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\CrossTracker\REST\v1;

use RestBase;

class CrossTrackerTestNonRegressionTrackerTest extends RestBase
{
    public function testItThrowsAnExceptionWhenReportIsNotFound()
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'cross_tracker_reports/100'));

        $this->assertEquals($response->getStatusCode(), 404);
    }

    public function testItThrowsAnExceptionWhenMoreThan10Trackers()
    {
        $params   = [
            "trackers_id" => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
        ];
        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/1')->withBody($this->stream_factory->createStream(json_encode($params))));

        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testItThrowsAnExceptionWhenATrackerIsNotFoundOnePlatform()
    {
        $params   = [
            "trackers_id" => [1001],
        ];
        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/1')->withBody($this->stream_factory->createStream(json_encode($params))));

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testItThrowsAnExceptionWhenTrackerIsDuplicateInList()
    {
        $params   = [
            "trackers_id" => [$this->epic_tracker_id, $this->epic_tracker_id],
        ];
        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'cross_tracker_reports/1')->withBody($this->stream_factory->createStream(json_encode($params))));

        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testItDoesNotAddTrackersUserCantView()
    {
        $params   = [
            "trackers_id" => [$this->epic_tracker_id, $this->kanban_tracker_id],
        ];
        $response = $this->getResponseForNonProjectMember($this->request_factory->createRequest('PUT', 'cross_tracker_reports/1')->withBody($this->stream_factory->createStream(json_encode($params))));

        $this->assertEquals($response->getStatusCode(), 403);
    }

    public function itThrowsAnExceptionWhenAQueryIsDefinedAndTrackersIdAreNotAnArray()
    {
        $query = json_encode(
            [
                "trackers_id" => "toto",
            ]
        );

        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'cross_tracker_reports/1/content?limit=50&offset=0&query=' . urlencode($query))
        );

        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function itThrowsAnExceptionWhenAQueryIsDefinedAndTrackersIdAreNotAnArrayOfInt()
    {
        $query = json_encode(
            [
                "trackers_id" => ["toto"],
            ]
        );

        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'cross_tracker_reports/1/content?limit=50&offset=0&query=' . urlencode($query))
        );

        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function itThrowsAnExceptionWhenAQueryIsDefinedAndTrackersIdAreNotSent()
    {
        $query = json_encode(
            ["toto"]
        );

        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'cross_tracker_reports/1/content?limit=50&offset=0&query=' . urlencode($query))
        );

        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testYouCantAccessPersonalReportOfAnOtherUser()
    {
        $response = $this->getResponseForNonProjectMember($this->request_factory->createRequest('GET', 'cross_tracker_reports/2'));

        $this->assertEquals($response->getStatusCode(), 403);
    }

    public function testYouCantAccessProjectReportOfProjectYouCantSee()
    {
        $response = $this->getResponseForNonProjectMember($this->request_factory->createRequest('GET', 'cross_tracker_reports/3'));

        $this->assertEquals($response->getStatusCode(), 403);
    }

    private function getResponseForNonProjectMember($request)
    {
        return $this->getResponse($request, \REST_TestDataBuilder::TEST_USER_4_NAME);
    }
}
