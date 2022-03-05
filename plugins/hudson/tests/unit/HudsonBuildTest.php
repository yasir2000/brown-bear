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
use HudsonBuild;
use HudsonJobURLMalformedException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\GlobalLanguageMock;
use Tuleap\Http\HTTPFactoryBuilder;

final class HudsonBuildTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    public function testMalformedURL(): void
    {
        $this->expectException(HudsonJobURLMalformedException::class);

        new HudsonBuild("toto", new Client(), HTTPFactoryBuilder::requestFactory());
    }

    public function testMissingSchemeURL(): void
    {
        $this->expectException(HudsonJobURLMalformedException::class);

        new HudsonBuild("code4:8080/hudson/jobs/tuleap", new Client(), HTTPFactoryBuilder::requestFactory());
    }

    public function testMissingHostURL(): void
    {
        $this->expectException(HudsonJobURLMalformedException::class);

        new HudsonBuild("http://", new Client(), HTTPFactoryBuilder::requestFactory());
    }

    public function testSimpleJobBuild(): void
    {
        $build_file = __DIR__ . '/resources/jobbuild.xml';
        $xmldom     = simplexml_load_string(file_get_contents($build_file), \SimpleXMLElement::class, LIBXML_NONET);

        $build = Mockery::spy(HudsonBuild::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $build->shouldReceive('_getXMLObject')->andReturn($xmldom);
        $build->__construct("http://myCIserver/jobs/myCIjob/lastBuild/", new Client(), HTTPFactoryBuilder::requestFactory());

        $this->assertEquals($build->getBuildStyle(), "freeStyleBuild");
        $this->assertFalse($build->isBuilding());
        $this->assertEquals($build->getUrl(), "http://code4.grenoble.xrce.xerox.com:8080/hudson/job/Codendi/87/");
        $this->assertEquals($build->getResult(), "UNSTABLE");
        $this->assertEquals($build->getNumber(), 87);
        $this->assertEquals($build->getDuration(), 359231);
        $this->assertEquals($build->getTimestamp(), 1230051671000);
    }
}
