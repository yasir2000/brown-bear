<?php
/**
 * Copyright Enalean (c) 2016 - present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
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

namespace Tuleap\SVN\Hooks;

use ForgeConfig;
use Mockery;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ReferenceManager;
use SVN_CommitToTagDeniedException;
use Tuleap\SVN\Admin\ImmutableTag;
use Tuleap\SVN\Admin\ImmutableTagDao;
use Tuleap\SVN\Commit\CommitMessageValidator;
use Tuleap\SVN\Commit\CommitMessageWithoutReferenceException;
use Tuleap\SVN\Commit\EmptyCommitMessageException;
use Tuleap\SVN\Commit\ImmutableTagCommitValidator;
use Tuleap\SVN\Commit\Svnlook;
use Tuleap\SVN\Repository\HookConfig;
use Tuleap\SVN\Repository\Repository;

class PreCommitTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Repository
     */
    private $repository;
    /**
     * @var string
     */
    private $system_path;
    /**
     * @var string
     */
    private $repository_name;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Tuleap\SVN\Repository\RepositoryManager
     */
    private $repository_manager;


    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Tuleap\SVN\Admin\ImmutableTagFactory
     */
    private $immutable_tag_factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->immutable_tag_factory = Mockery::spy(\Tuleap\SVN\Admin\ImmutableTagFactory::class);
        $this->repository_manager    = Mockery::spy(\Tuleap\SVN\Repository\RepositoryManager::class);

        $this->repository_name = 'repositoryname';
        $project_id            = 1;
        $this->system_path     = $project_id . "/" . $this->repository_name;

        $this->repository = Mockery::spy(\Tuleap\SVN\Repository\Repository::class);
        $this->repository->shouldReceive('getId')->andReturn(1);
        $this->repository->shouldReceive('getName')->andReturn($this->repository_name);
        $this->repository_manager->shouldReceive('getRepositoryFromSystemPath')
            ->withArgs([$this->system_path])
            ->andReturn($this->repository);
    }

    private function assertCommitIsAllowed(): void
    {
        $paths = func_get_args();
        try {
            $this->preCommitToTags($paths);
            $this->addToAssertionCount(1);
        } catch (SVN_CommitToTagDeniedException $ex) {
            $this->fail('Commit of "' . implode(', ', $paths) . '" should be allowed');
        }
    }

    private function assertCommitIsDenied(): void
    {
        $paths = func_get_args();
        try {
            $this->preCommitToTags($paths);
            $this->fail('Commit of "' . implode(', ', $paths) . '" should be denied');
        } catch (SVN_CommitToTagDeniedException $ex) {
            $this->addToAssertionCount(1);
        }
    }

    /**
     * @throws SVN_CommitToTagDeniedException
     */
    private function preCommitToTags(array $paths): void
    {
        $svn_look = Mockery::mock(Svnlook::class);
        $svn_look->shouldReceive('getMessageFromTransaction')->andReturn(["COMMIT MSG"]);
        $svn_look->shouldReceive('getTransactionPath')->andReturn($paths);
        $svn_look->shouldReceive('getContent');
        $svn_look->shouldReceive('closeContentResource');

        $pre_commit = new PreCommit(
            $svn_look,
            new NullLogger(),
            Mockery::spy(CommitMessageValidator::class),
            new ImmutableTagCommitValidator(
                new NullLogger(),
                $this->immutable_tag_factory,
            )
        );
        $pre_commit->assertCommitIsValid($this->repository, '1');
    }

    public function testCommitToWhiteListedTagIsAllowed()
    {
        $immutable_tags = Mockery::mock(ImmutableTag::class);

        $immutable_tags->shouldReceive('getPaths')->andReturn(['/*/tags/']);
        $immutable_tags->shouldReceive('getWhitelist')->andReturn(["trunk/tags/v1/to to/"]);

        $this->immutable_tag_factory->shouldReceive('getByRepositoryId')->andReturn($immutable_tags);

        $this->assertCommitIsAllowed('A   trunk/tags/v1/to to/banana');
        $this->assertCommitIsDenied('A   trunk/tags/v2/to to/banana');
    }

    public function testCommitToTagIsAllowed(): void
    {
        $immutable_tags = Mockery::mock(ImmutableTag::class);
        $immutable_tags->shouldReceive('getPaths')->andReturn([]);

        $this->immutable_tag_factory->shouldReceive('getByRepositoryId')->andReturn($immutable_tags);

        $this->assertEquals($immutable_tags->getPaths(), []);

        $this->assertCommitIsAllowed('A   file');
        $this->assertCommitIsAllowed('U   file');
        $this->assertCommitIsAllowed('D   file');

        $this->assertCommitIsAllowed('A   moduleA/trunk/toto');
        $this->assertCommitIsAllowed('U   moduleA/trunk/toto');
        $this->assertCommitIsAllowed('D   moduleA/trunk/toto');

        $this->assertCommitIsAllowed('A   moduleA/tags/v1/');
        $this->assertCommitIsAllowed('U   moduleA/tags/v1/');
        $this->assertCommitIsAllowed('D   moduleA/tags/v1/');

        $this->assertCommitIsAllowed('A   moduleA/tags/v1/toto');
        $this->assertCommitIsAllowed('U   moduleA/tags/v1/toto');
        $this->assertCommitIsAllowed('D   moduleA/tags/v1/toto');

        $this->assertCommitIsAllowed('A   moduleA/branch', 'A   moduleA/tags/v1/toto');

        $this->assertCommitIsAllowed('A   trunk/toto');
        $this->assertCommitIsAllowed('U   trunk/toto');
        $this->assertCommitIsAllowed('D   trunk/toto');

        $this->assertCommitIsAllowed('A   tags/v1/');
        $this->assertCommitIsAllowed('U   tags/v1/');
        $this->assertCommitIsAllowed('D   tags/v1/');

        $this->assertCommitIsAllowed('A   tags/v1/toto');
        $this->assertCommitIsAllowed('U   tags/v1/toto');
        $this->assertCommitIsAllowed('D   tags/v1/toto');

        $this->assertCommitIsAllowed('A   tags/moduleA/');
        $this->assertCommitIsAllowed('U   tags/moduleA/');
        $this->assertCommitIsAllowed('D   tags/moduleA/');

        $this->assertCommitIsAllowed('A   tags/moduleA/v1/');
        $this->assertCommitIsAllowed('U   tags/moduleA/v1/');
        $this->assertCommitIsAllowed('D   tags/moduleA/v1/');

        $this->assertCommitIsAllowed('A   tags/moduleA/toto');
        $this->assertCommitIsAllowed('U   tags/moduleA/toto');
        $this->assertCommitIsAllowed('D   tags/moduleA/toto');

        $this->assertCommitIsAllowed('A   tags/moduleA/v1/toto');
        $this->assertCommitIsAllowed('U   tags/moduleA/v1/toto');
        $this->assertCommitIsAllowed('D   tags/moduleA/v1/toto');

        $this->assertCommitIsAllowed('A   trunk/toto', 'A   tags/moduleA/v1/toto');
    }

    public function testCommitToTagIsDeniedInModule(): void
    {
        $immutable_tags = Mockery::mock(ImmutableTag::class);
        $immutable_tags->shouldReceive('getPaths')->andReturn(['/*/tags/']);
        $immutable_tags->shouldReceive('getWhitelist')->andReturn([]);

        $immutable_tag_dao = Mockery::mock(ImmutableTagDao::class);
        $immutable_tag_dao->shouldReceive('searchByRepositoryId')->andReturn([$this->repository]);

        $this->immutable_tag_factory->shouldReceive('getByRepositoryId')->andReturn($immutable_tags);

        $this->assertCommitIsDenied('A   moduleA/branch', 'A   moduleA/tags/v1/toto');

        $this->assertCommitIsAllowed('A   file');
        $this->assertCommitIsAllowed('U   file');
        $this->assertCommitIsAllowed('D   file');

        $this->assertCommitIsAllowed('A   moduleA/trunk/toto');
        $this->assertCommitIsAllowed('U   moduleA/trunk/toto');
        $this->assertCommitIsAllowed('D   moduleA/trunk/toto');

        $this->assertCommitIsAllowed('A   moduleA/tags/v1/');
        $this->assertCommitIsDenied('U   moduleA/tags/v1/');
        $this->assertCommitIsDenied('D   moduleA/tags/v1/');

        $this->assertCommitIsDenied('A   moduleA/tags/v1/toto');
        $this->assertCommitIsDenied('U   moduleA/tags/v1/toto');
        $this->assertCommitIsDenied('D   moduleA/tags/v1/toto');

        $this->assertCommitIsAllowed('A   trunk/toto');
        $this->assertCommitIsAllowed('U   trunk/toto');
        $this->assertCommitIsAllowed('D   trunk/toto');

        $this->assertCommitIsAllowed('A   tags/v1/');
        $this->assertCommitIsAllowed('U   tags/v1/');
        $this->assertCommitIsAllowed('D   tags/v1/');

        $this->assertCommitIsAllowed('A   tags/v1/toto');
        $this->assertCommitIsAllowed('U   tags/v1/toto');
        $this->assertCommitIsAllowed('D   tags/v1/toto');

        $this->assertCommitIsAllowed('A   tags/moduleA/');
        $this->assertCommitIsAllowed('U   tags/moduleA/');
        $this->assertCommitIsAllowed('D   tags/moduleA/');

        $this->assertCommitIsAllowed('A   tags/moduleA/v1/');
        $this->assertCommitIsAllowed('U   tags/moduleA/v1/');
        $this->assertCommitIsAllowed('D   tags/moduleA/v1/');

        $this->assertCommitIsAllowed('A   tags/moduleA/toto');
        $this->assertCommitIsAllowed('U   tags/moduleA/toto');
        $this->assertCommitIsAllowed('D   tags/moduleA/toto');

        $this->assertCommitIsAllowed('A   tags/moduleA/v1/toto');
        $this->assertCommitIsAllowed('U   tags/moduleA/v1/toto');
        $this->assertCommitIsAllowed('D   tags/moduleA/v1/toto');

        $this->assertCommitIsAllowed('A   trunk/toto', 'A   tags/moduleA/v1/toto');
    }

    public function testCommitToTagIsDeniedAtRootAndInModules(): void
    {
        $immutable_tags = Mockery::mock(ImmutableTag::class);
        $immutable_tags->shouldReceive('getPaths')->andReturn(['tags', '/*/tags']);
        $immutable_tags->shouldReceive('getWhitelist')->andReturn([]);

        $immutable_tag_dao = Mockery::mock(ImmutableTagDao::class);
        $immutable_tag_dao->shouldReceive('searchByRepositoryId')->andReturn([$this->repository]);

        $this->immutable_tag_factory->shouldReceive('getByRepositoryId')->andReturn($immutable_tags);

        $this->assertCommitIsAllowed('A   file');
        $this->assertCommitIsAllowed('U   file');
        $this->assertCommitIsAllowed('D   file');

        $this->assertCommitIsAllowed('A   moduleA/trunk/toto');
        $this->assertCommitIsAllowed('U   moduleA/trunk/toto');
        $this->assertCommitIsAllowed('D   moduleA/trunk/toto');

        $this->assertCommitIsAllowed('A   moduleA/tags/v1/');
        $this->assertCommitIsDenied('U   moduleA/tags/v1/');
        $this->assertCommitIsDenied('D   moduleA/tags/v1/');

        $this->assertCommitIsDenied('A   moduleA/tags/v1/toto');
        $this->assertCommitIsDenied('U   moduleA/tags/v1/toto');
        $this->assertCommitIsDenied('D   moduleA/tags/v1/toto');

        $this->assertCommitIsDenied('A   moduleA/branch', 'A   moduleA/tags/v1/toto');

        $this->assertCommitIsAllowed('A   trunk/toto');
        $this->assertCommitIsAllowed('U   trunk/toto');
        $this->assertCommitIsAllowed('D   trunk/toto');

        $this->assertCommitIsAllowed('A   tags/v1/');
        $this->assertCommitIsDenied('U   tags/v1/');
        $this->assertCommitIsDenied('D   tags/v1/');

        $this->assertCommitIsDenied('A   tags/v1/toto');
        $this->assertCommitIsDenied('U   tags/v1/toto');
        $this->assertCommitIsDenied('D   tags/v1/toto');

        $this->assertCommitIsAllowed('A   tags/moduleA/');
        $this->assertCommitIsDenied('U   tags/moduleA/');
        $this->assertCommitIsDenied('D   tags/moduleA/');

        $this->assertCommitIsDenied('A   tags/moduleA/v1/');
        $this->assertCommitIsDenied('U   tags/moduleA/v1/');
        $this->assertCommitIsDenied('D   tags/moduleA/v1/');

        $this->assertCommitIsDenied('A   tags/moduleA/toto');
        $this->assertCommitIsDenied('U   tags/moduleA/toto');
        $this->assertCommitIsDenied('D   tags/moduleA/toto');

        $this->assertCommitIsDenied('A   tags/moduleA/v1/toto');
        $this->assertCommitIsDenied('U   tags/moduleA/v1/toto');
        $this->assertCommitIsDenied('D   tags/moduleA/v1/toto');

        $this->assertCommitIsDenied('A   trunk/toto', 'A   tags/moduleA/v1/toto');
    }

    public function testItRejectsCommitIfCommitMessageIsEmptyAndForgeRequiresACommitMessage(): void
    {
        ForgeConfig::set('sys_allow_empty_svn_commit_message', false);

        $svn_look = Mockery::mock(Svnlook::class);
        $svn_look->shouldReceive('getMessageFromTransaction')->andReturn([""]);

        $hook_config = Mockery::spy(\Tuleap\SVN\Repository\HookConfigRetriever::class);
        $hook_config->shouldReceive('getHookConfig')->withArgs([HookConfig::MANDATORY_REFERENCE])->andReturn(false);

        $hook = new PreCommit(
            $svn_look,
            Mockery::mock(LoggerInterface::class),
            new CommitMessageValidator(
                $hook_config,
                Mockery::mock(ReferenceManager::class),
            ),
        );

        $this->expectException(EmptyCommitMessageException::class);
        $hook->assertCommitIsValid($this->repository, '1');
    }

    public function testIDoesNotRejectCommitIfCommitMessageIsEmptyAndForgeDoesNotRequireACommitMessage(): void
    {
        ForgeConfig::set('sys_allow_empty_svn_commit_message', true);

        $svn_look = Mockery::mock(Svnlook::class);
        $svn_look->shouldReceive('getMessageFromTransaction')->andReturn([""]);
        $svn_look->shouldReceive('getTransactionPath')->andReturn([]);

        $hook_config_retriever = Mockery::spy(\Tuleap\SVN\Repository\HookConfigRetriever::class);

        $hook_config = Mockery::mock(HookConfig::class);
        $hook_config_retriever->shouldReceive('getHookConfig')
            ->withArgs([$this->repository])
            ->andReturn($hook_config);

        $hook_config->shouldReceive('getHookConfig')->withArgs([HookConfig::MANDATORY_REFERENCE])->andReturnFalse();

        $hook = new PreCommit(
            $svn_look,
            new NullLogger(),
            new CommitMessageValidator(
                $hook_config_retriever,
                Mockery::mock(ReferenceManager::class),
            ),
        );

        $hook->assertCommitIsValid($this->repository, '1');
    }

    public function testIRejectsCommitMessagesWithoutArtifactReference(): void
    {
        $project = Mockery::mock('Project');

        $svn_look = Mockery::mock(Svnlook::class);
        $svn_look->shouldReceive('getMessageFromTransaction')->andReturn(["Commit message without reference"]);

        $hook_config_retriever = Mockery::spy(\Tuleap\SVN\Repository\HookConfigRetriever::class);

        $hook_config = Mockery::mock(HookConfig::class);
        $hook_config_retriever->shouldReceive('getHookConfig')
            ->withArgs([$this->repository])
            ->andReturn($hook_config);

        $hook_config->shouldReceive('getHookConfig')->withArgs([HookConfig::MANDATORY_REFERENCE])->andReturnTrue();

        $reference_manager = Mockery::mock(ReferenceManager::class);

        $this->repository->shouldReceive('getProject')->once()->andReturn($project);
        $project->shouldReceive('getId')->andReturn(123);
        $reference_manager->shouldReceive('stringContainsReferences')
            ->withArgs(["Commit message without reference", Mockery::any()])
            ->once()
            ->andReturn(false);

        $hook = new PreCommit(
            $svn_look,
            new NullLogger(),
            new CommitMessageValidator(
                $hook_config_retriever,
                $reference_manager,
            ),
        );

        $this->expectException(CommitMessageWithoutReferenceException::class);
        $hook->assertCommitIsValid($this->repository, '1');
    }
}
