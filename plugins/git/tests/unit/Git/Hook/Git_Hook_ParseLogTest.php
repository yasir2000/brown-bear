<?php
/**
 * Copyright BrownBear (c) 2013 - Present. All rights reserved.
 *
 * Tuleap and BrownBear names and logos are registrated trademarks owned by
 * BrownBear SAS. All other trademarks or names are properties of their respective
 * owners.
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

use Tuleap\Git\Hook\CrossReferencesExtractor;

require_once __DIR__ . '/../../bootstrap.php';

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Git_Hook_ParseLogTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private $extract_cross_ref;
    private $log_pushes;
    private $parse_log;
    private $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extract_cross_ref = \Mockery::spy(CrossReferencesExtractor::class);
        $this->log_pushes        = \Mockery::spy(\Git_Hook_LogPushes::class);
        $this->logger            = \Mockery::spy(\Psr\Log\LoggerInterface::class);
        $this->parse_log         = new Git_Hook_ParseLog($this->log_pushes, $this->extract_cross_ref, $this->logger);
    }

    public function testItExecutesExtractOnEachCommit(): void
    {
        $push_details = \Mockery::spy(\Git_Hook_PushDetails::class)->shouldReceive('getRevisionList')->andReturns(['469eaa9'])->getMock();

        $this->extract_cross_ref->shouldReceive('extractCommitReference')->with($push_details, '469eaa9')->once();

        $this->parse_log->execute($push_details);
    }

    public function testItExecutesExtractOnTag(): void
    {
        $push_details = \Mockery::spy(\Git_Hook_PushDetails::class)->shouldReceive('getRevisionList')->andReturns(['469eaa9'])->getMock();

        $this->extract_cross_ref->shouldReceive('extractCommitReference')->with($push_details, '469eaa9')->once();

        $this->parse_log->execute($push_details);
    }

    public function testItDoesntAttemptToExtractWhenBranchIsDeleted(): void
    {
        $push_details = new Git_Hook_PushDetails(
            \Mockery::mock(\GitRepository::class),
            \Tuleap\Test\Builders\UserTestBuilder::anActiveUser()->build(),
            'refs/tags/v1',
            'create',
            'tag',
            []
        );

        $this->log_pushes->shouldReceive('executeForRepository')
            ->once()
            ->with($push_details);

        $this->extract_cross_ref->shouldReceive('extractTagReference')->once();

        $this->parse_log->execute($push_details);
    }

    public function testItExecutesExtractEvenWhenThereAreErrors(): void
    {
        $push_details = \Mockery::spy(\Git_Hook_PushDetails::class);
        $push_details->shouldReceive('getRevisionList')->andReturns(['0fb0737', '469eaa9']);
        $push_details->shouldReceive('getRepository')->andReturns(\Mockery::spy(\GitRepository::class));

        $this->extract_cross_ref->shouldReceive('extractCommitReference')->with($push_details, '0fb0737');
        $this->logger->shouldReceive('error')->once();
        $this->extract_cross_ref->shouldReceive('extractCommitReference')
            ->with($push_details, '469eaa9')
            ->andThrows(new Git_Command_Exception('whatever', ['whatever'], '234'));

        $this->parse_log->execute($push_details);
    }
}
