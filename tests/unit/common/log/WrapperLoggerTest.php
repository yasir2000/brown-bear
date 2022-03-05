<?php
/**
 * Copyright (c) Enalean, 2014-present. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Psr\Log\LogLevel;

class WrapperLoggerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    private $logger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = \Mockery::mock(\Psr\Log\LoggerInterface::class);
    }

    public function testItAppendAPrefix(): void
    {
        $wrapper = new WrapperLogger($this->logger, 'stuff');

        $this->logger->shouldReceive('log')->with(LogLevel::INFO, '[stuff] bla', [])->once();

        $wrapper->info('bla');
    }

    public function testItWrapAWrapper(): void
    {
        $wrapper1 = new WrapperLogger($this->logger, 'tracker');

        $wrapper2 = new WrapperLogger($wrapper1, 'artifact');

        $this->logger->shouldReceive('log')->with(LogLevel::INFO, '[tracker] [artifact] bla', [])->once();

        $wrapper2->info('bla');
    }

    public function testItAddAPrefixDynamically(): void
    {
        $wrapper = new WrapperLogger($this->logger, 'tracker');

        $this->logger->shouldReceive('log')->with(LogLevel::INFO, '[tracker][53] bla', [])->once();

        $wrapper->push('53');
        $wrapper->info('bla');
    }

    public function testItAddAPrefixDynamicallyAndItsKept(): void
    {
        $wrapper = new WrapperLogger($this->logger, 'tracker');

        $this->logger->shouldReceive('log')->with(LogLevel::INFO, '[tracker][53] bla', [])->once();
        $this->logger->shouldReceive('log')->with(LogLevel::INFO, '[tracker][53] coin', [])->once();

        $wrapper->push('53');
        $wrapper->info('bla');
        $wrapper->info('coin');
    }

    public function testAddedPrefixAreStacked(): void
    {
        $wrapper = new WrapperLogger($this->logger, 'tracker');

        $this->logger->shouldReceive('log')->with(LogLevel::INFO, '[tracker][53] bla', [])->once();
        $this->logger->shouldReceive('log')->with(LogLevel::INFO, '[tracker][53][field] coin', [])->once();

        $wrapper->push('53');
        $wrapper->info('bla');
        $wrapper->push('field');
        $wrapper->info('coin');
    }

    public function testItPopPrefixes(): void
    {
        $wrapper = new WrapperLogger($this->logger, 'tracker');

        $this->logger->shouldReceive('log')->with(LogLevel::INFO, '[tracker][53] bla', [])->once();
        $this->logger->shouldReceive('log')->with(LogLevel::INFO, '[tracker] coin', [])->once();

        $wrapper->push('53');
        $wrapper->info('bla');
        $wrapper->pop();
        $wrapper->info('coin');
    }

    public function testItPopPrefixes2(): void
    {
        $wrapper = new WrapperLogger($this->logger, 'tracker');

        $this->logger->shouldReceive('log')->with(LogLevel::INFO, '[tracker] stuff', [])->once();
        $this->logger->shouldReceive('log')->with(LogLevel::INFO, '[tracker][53] bla', [])->once();
        $this->logger->shouldReceive('log')->with(LogLevel::INFO, '[tracker][54] coin', [])->once();

        $wrapper->info('stuff');
        $wrapper->push('53');
        $wrapper->info('bla');
        $wrapper->pop();
        $wrapper->push('54');
        $wrapper->info('coin');
    }
}
