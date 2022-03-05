<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

namespace Tuleap\Hudson;

use Http\Mock\Client;
use HudsonJobURLMalformedException;
use HudsonTestResult;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\GlobalLanguageMock;
use Tuleap\Http\HTTPFactoryBuilder;

final class HudsonTestResultTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    public function testMalformedURL()
    {
        $this->expectException(HudsonJobURLMalformedException::class);

        new HudsonTestResult("toto", new Client(), HTTPFactoryBuilder::requestFactory());
    }

    public function testMissingSchemeURL()
    {
        $this->expectException(HudsonJobURLMalformedException::class);

        new HudsonTestResult("code4:8080/hudson/jobs/tuleap", new Client(), HTTPFactoryBuilder::requestFactory());
    }

    public function testMissingHostURL()
    {
        $this->expectException(HudsonJobURLMalformedException::class);

        new HudsonTestResult("http://", new Client(), HTTPFactoryBuilder::requestFactory());
    }

    public function testSimpleJobTestResult()
    {
        $test_result_file = __DIR__ . '/resources/testReport.xml';
        $xmldom           = simplexml_load_string(file_get_contents($test_result_file), \SimpleXMLElement::class, LIBXML_NONET);

        $test_result = Mockery::spy(HudsonTestResult::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $test_result->shouldReceive('_getXMLObject')->andReturn($xmldom);
        $test_result->__construct("http://myCIserver/jobs/myCIjob/lastBuild/testReport/", new Client(), HTTPFactoryBuilder::requestFactory());

        $this->assertEquals($test_result->getFailCount(), 5);
        $this->assertEquals($test_result->getPassCount(), 416);
        $this->assertEquals($test_result->getSkipCount(), 3);
        $this->assertEquals($test_result->getTotalCount(), 424);
    }
}
