<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\REST\v1;

use BackendLogger;
use EventManager;
use Git_Command_Exception;
use Git_GitRepositoryUrlManager;
use GitDao;
use GitPlugin;
use GitRepoNotFoundException;
use GitRepository;
use GitRepositoryFactory;
use Luracast\Restler\RestException;
use PFUser;
use PluginFactory;
use Project_AccessException;
use Project_AccessProjectNotFoundException;
use ProjectHistoryDao;
use ProjectManager;
use Psr\Log\LoggerInterface;
use ReferenceManager;
use Tuleap\Git\CommitMetadata\CommitMetadataRetriever;
use Tuleap\Git\CommitStatus\CommitStatusDAO;
use Tuleap\Git\CommitStatus\CommitStatusRetriever;
use Tuleap\Git\Gitolite\GitoliteAccessURLGenerator;
use Tuleap\Git\GitPHP\Pack;
use Tuleap\Git\GitPHP\ProjectProvider;
use Tuleap\Git\Permissions\AccessControlVerifier;
use Tuleap\Git\Permissions\FineGrainedDao;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\Git\REST\v1\GitCommitRepresentationBuilder;
use Tuleap\Label\Label;
use Tuleap\Label\PaginatedCollectionsOfLabelsBuilder;
use Tuleap\Label\REST\LabelRepresentation;
use Tuleap\Label\REST\LabelsPATCHRepresentation;
use Tuleap\Label\REST\LabelsUpdater;
use Tuleap\Label\REST\UnableToAddAndRemoveSameLabelException;
use Tuleap\Label\REST\UnableToAddEmptyLabelException;
use Tuleap\Label\UnknownLabelException;
use Tuleap\Project\Label\LabelDao;
use Tuleap\Project\REST\UserRESTReferenceRetriever;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use Tuleap\PullRequest\Authorization\PullRequestPermissionChecker;
use Tuleap\PullRequest\Authorization\UserCannotMergePullRequestException;
use Tuleap\PullRequest\Comment\Comment;
use Tuleap\PullRequest\Comment\Dao as CommentDao;
use Tuleap\PullRequest\Comment\Factory as CommentFactory;
use Tuleap\PullRequest\Dao as PullRequestDao;
use Tuleap\PullRequest\Events\PullRequestDiffRepresentationBuild;
use Tuleap\PullRequest\Exception\PullRequestAlreadyExistsException;
use Tuleap\PullRequest\Exception\PullRequestAnonymousUserException;
use Tuleap\PullRequest\Exception\PullRequestCannotBeCreatedException;
use Tuleap\PullRequest\Exception\PullRequestNotFoundException;
use Tuleap\PullRequest\Exception\PullRequestRepositoryMigratedOnGerritException;
use Tuleap\PullRequest\Exception\PullRequestTargetException;
use Tuleap\PullRequest\Exception\UnknownBranchNameException;
use Tuleap\PullRequest\Exception\UnknownReferenceException;
use Tuleap\PullRequest\Exception\UserCannotReadGitRepositoryException;
use Tuleap\PullRequest\Factory as PullRequestFactory;
use Tuleap\PullRequest\FileUniDiff;
use Tuleap\PullRequest\FileUniDiffBuilder;
use Tuleap\PullRequest\GitExec;
use Tuleap\PullRequest\GitReference\GitPullRequestReference;
use Tuleap\PullRequest\GitReference\GitPullRequestReferenceCreator;
use Tuleap\PullRequest\GitReference\GitPullRequestReferenceDAO;
use Tuleap\PullRequest\GitReference\GitPullRequestReferenceNamespaceAvailabilityChecker;
use Tuleap\PullRequest\GitReference\GitPullRequestReferenceNotFoundException;
use Tuleap\PullRequest\GitReference\GitPullRequestReferenceRetriever;
use Tuleap\PullRequest\GitReference\GitPullRequestReferenceUpdater;
use Tuleap\PullRequest\InlineComment\Dao as InlineCommentDao;
use Tuleap\PullRequest\InlineComment\InlineCommentCreator;
use Tuleap\PullRequest\Label\LabelsCurlyCoatedRetriever;
use Tuleap\PullRequest\Label\PullRequestLabelDao;
use Tuleap\PullRequest\MergeSetting\MergeSettingDAO;
use Tuleap\PullRequest\MergeSetting\MergeSettingRetriever;
use Tuleap\PullRequest\Notification\PullRequestNotificationSupport;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\PullRequestCloser;
use Tuleap\PullRequest\PullRequestCreator;
use Tuleap\PullRequest\PullRequestMerger;
use Tuleap\PullRequest\PullRequestWithGitReference;
use Tuleap\PullRequest\REST\v1\Reviewer\ReviewerRepresentationInformationExtractor;
use Tuleap\PullRequest\REST\v1\Reviewer\ReviewersPUTRepresentation;
use Tuleap\PullRequest\REST\v1\Reviewer\ReviewersRepresentation;
use Tuleap\PullRequest\Reviewer\Change\ReviewerChangeDAO;
use Tuleap\PullRequest\Reviewer\Change\ReviewerChangeRetriever;
use Tuleap\PullRequest\Reviewer\ReviewerDAO;
use Tuleap\PullRequest\Reviewer\ReviewerRetriever;
use Tuleap\PullRequest\Reviewer\ReviewersCannotBeUpdatedOnClosedPullRequestException;
use Tuleap\PullRequest\Reviewer\ReviewerUpdater;
use Tuleap\PullRequest\Reviewer\UserCannotBeAddedAsReviewerException;
use Tuleap\PullRequest\Timeline\Dao as TimelineDao;
use Tuleap\PullRequest\Timeline\Factory as TimelineFactory;
use Tuleap\PullRequest\Timeline\TimelineEventCreator;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\ProjectAuthorization;
use Tuleap\REST\ProjectStatusVerificator;
use Tuleap\User\REST\MinimalUserRepresentation;
use URLVerification;
use UserManager;

class PullRequestsResource extends AuthenticatedResource
{
    public const MAX_LIMIT = 50;

    /** @var PullRequestPermissionChecker */
    private $permission_checker;

    /** @var LabelsUpdater */
    private $labels_updater;

    /** @var LabelsCurlyCoatedRetriever */
    private $labels_retriever;

    /** @var GitRepositoryFactory */
    private $git_repository_factory;

    /** @var PullRequestFactory */
    private $pull_request_factory;

    /** @var Tuleap\PullRequest\Timeline\Factory */
    private $timeline_factory;

    /** @var Tuleap\PullRequest\Comment\Factory */
    private $comment_factory;

    /** @var PaginatedCommentsRepresentationsBuilder */
    private $paginated_timeline_representation_builder;

    /** @var PaginatedCommentsRepresentationsBuilder */
    private $paginated_comments_representations_builder;

    /** @var UserManager */
    private $user_manager;

    /** @var PullRequestMerger */
    private $pull_request_merger;

    /** @var PullRequestCloser */
    private $pull_request_closer;

    /** @var PullRequestCreator */
    private $pull_request_creator;

    /** @var EventManager */
    private $event_manager;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @var InlineCommentCreator
     */
    private $inline_comment_creator;

    /**
     * @var AccessControlVerifier
     */
    private $access_control_verifier;
    /**
     * @var GitPullRequestReferenceRetriever
     */
    private $git_pull_request_reference_retriever;
    /**
     * @var GitPullRequestReferenceUpdater
     */
    private $git_pull_request_reference_updater;
    /**
     * @var GitPlugin
     */
    private $git_plugin;
    /**
     * @var GitCommitRepresentationBuilder
     */
    private $commit_representation_builder;
    /**
     * @var CommitStatusRetriever
     */
    private $status_retriever;

    public function __construct()
    {
        $this->git_repository_factory = new GitRepositoryFactory(
            new GitDao(),
            ProjectManager::instance()
        );

        $pull_request_dao           = new PullRequestDao();
        $reference_manager          = ReferenceManager::instance();
        $this->pull_request_factory = new PullRequestFactory($pull_request_dao, $reference_manager);

        $this->logger = BackendLogger::getDefaultLogger();

        $event_dispatcher = PullRequestNotificationSupport::buildDispatcher($this->logger);

        $comment_dao           = new CommentDao();
        $this->comment_factory = new CommentFactory($comment_dao, $reference_manager, $event_dispatcher);

        $this->user_manager = UserManager::instance();

        $inline_comment_dao     = new InlineCommentDao();
        $timeline_dao           = new TimelineDao();
        $this->timeline_factory = new TimelineFactory(
            $comment_dao,
            $inline_comment_dao,
            $timeline_dao,
            new ReviewerChangeRetriever(
                new ReviewerChangeDAO(),
                $this->pull_request_factory,
                $this->user_manager,
            )
        );

        $this->paginated_timeline_representation_builder = new PaginatedTimelineRepresentationBuilder(
            $this->timeline_factory
        );

        $this->paginated_comments_representations_builder = new PaginatedCommentsRepresentationsBuilder(
            $this->comment_factory
        );

        $this->event_manager        = EventManager::instance();
        $this->pull_request_merger  = new PullRequestMerger(
            new MergeSettingRetriever(new MergeSettingDAO())
        );
        $this->pull_request_creator = new PullRequestCreator(
            $this->pull_request_factory,
            $pull_request_dao,
            $this->pull_request_merger,
            $this->event_manager,
            new GitPullRequestReferenceCreator(
                new GitPullRequestReferenceDAO(),
                new GitPullRequestReferenceNamespaceAvailabilityChecker()
            )
        );
        $this->pull_request_closer  = new PullRequestCloser(
            $pull_request_dao,
            $this->pull_request_merger,
            new TimelineEventCreator(new TimelineDao()),
            $event_dispatcher
        );

        $dao                          = new \Tuleap\PullRequest\InlineComment\Dao();
        $this->inline_comment_creator = new InlineCommentCreator($dao, $reference_manager, $event_dispatcher);

        $this->access_control_verifier = new AccessControlVerifier(
            new FineGrainedRetriever(new FineGrainedDao()),
            new \System_Command()
        );

        $this->labels_retriever = new LabelsCurlyCoatedRetriever(
            new PaginatedCollectionsOfLabelsBuilder(),
            new PullRequestLabelDao()
        );
        $this->labels_updater   = new LabelsUpdater(new LabelDao(), new PullRequestLabelDao(), new ProjectHistoryDao());

        $this->permission_checker = new PullRequestPermissionChecker(
            $this->git_repository_factory,
            new \Tuleap\Project\ProjectAccessChecker(
                new RestrictedUserCanAccessProjectVerifier(),
                $this->event_manager
            ),
            $this->access_control_verifier
        );

        $git_pull_request_reference_dao             = new GitPullRequestReferenceDAO();
        $this->git_pull_request_reference_retriever = new GitPullRequestReferenceRetriever($git_pull_request_reference_dao);
        $this->git_pull_request_reference_updater   = new GitPullRequestReferenceUpdater(
            $git_pull_request_reference_dao,
            new GitPullRequestReferenceNamespaceAvailabilityChecker()
        );

        $this->git_plugin = PluginFactory::instance()->getPluginByName('git');
        $url_manager      = new Git_GitRepositoryUrlManager($this->git_plugin);

        $this->status_retriever              = new CommitStatusRetriever(new CommitStatusDAO());
        $metadata_retriever                  = new CommitMetadataRetriever($this->status_retriever, $this->user_manager);
        $this->commit_representation_builder = new GitCommitRepresentationBuilder(
            $metadata_retriever,
            $url_manager
        );
    }

    /**
     * @url OPTIONS
     */
    public function options()
    {
        return $this->sendAllowHeadersForPullRequests();
    }

    /**
     * Get pull request
     *
     * Retrieve a given pull request. <br/>
     * User is not able to see a pull request in a git repository where he is not able to READ
     *
     * <pre>
     * /!\ PullRequest REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @url GET {id}
     *
     * @access protected
     *
     * @param int $id pull request ID
     *
     * @return array {@type Tuleap\PullRequest\REST\v1\PullRequestRepresentation}
     *
     * @throws RestException 403
     * @throws RestException 404 x Pull request does not exist
     */
    protected function get($id)
    {
        $this->checkAccess();
        $this->sendAllowHeadersForPullRequests();

        $pull_request_with_git_reference = $this->getAccessiblePullRequestWithGitReferenceForCurrentUser($id);
        $pull_request                    = $pull_request_with_git_reference->getPullRequest();
        $user                            = $this->user_manager->getCurrentUser();
        $repository_src                  = $this->getRepository($pull_request->getRepositoryId());
        $repository_dest                 = $this->getRepository($pull_request->getRepoDestId());

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $user,
            $repository_src->getProject()
        );

        $pr_representation_factory = new PullRequestRepresentationFactory(
            $this->access_control_verifier,
            $this->status_retriever,
            $this->getGitoliteAccessURLGenerator()
        );

        return $pr_representation_factory->getPullRequestRepresentation(
            $pull_request_with_git_reference,
            $repository_src,
            $repository_dest,
            GitExec::buildFromRepository($repository_dest),
            $user
        );
    }

    /**
     * Get pull request commits
     *
     * Retrieve all commits of a given pull request. <br/>
     * User is not able to see a pull request in a git repository where he is not able to READ
     *
     * <pre>
     * /!\ PullRequest REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @url    GET {id}/commits
     *
     * @access hybrid
     *
     * @param int $id     pull request ID
     * @param int $limit  Number of fetched comments {@from path} {@min 0}{@max 50}
     * @param int $offset Position of the first comment to fetch {@from path} {@min 0}
     *
     * @return array {@type \Tuleap\Git\REST\v1\GitCommitRepresentation}
     *
     * @throws RestException 403
     * @throws RestException 404 x Pull request does not exist
     * @throws RestException 410
     * @throws RestException 500
     */
    public function getCommits($id, $limit = 50, $offset = 0)
    {
        $this->checkAccess();
        $this->sendAllowHeadersForCommits();

        $pull_requests_with_git_reference = $this->getAccessiblePullRequestWithGitReferenceForCurrentUser($id);

        $pull_request   = $pull_requests_with_git_reference->getPullRequest();
        $git_repository = $this->getRepository($pull_request->getRepoDestId());

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $this->user_manager->getCurrentUser(),
            $git_repository->getProject()
        );

        $provider = new ProjectProvider($git_repository);

        $commit_factory = new PullRequestsCommitRepresentationFactory(
            $this->getExecutor($git_repository),
            $provider->GetProject(),
            $this->git_repository_factory,
            $this->commit_representation_builder
        );

        try {
            $commit_representation = $commit_factory->getPullRequestCommits(
                $pull_requests_with_git_reference->getPullRequest(),
                $limit,
                $offset
            );

            Header::sendPaginationHeaders($limit, $offset, $commit_representation->getSize(), self::MAX_LIMIT);

            return $commit_representation->getCommitsCollection();
        } catch (Git_Command_Exception $exception) {
            throw new RestException(500, $exception->getMessage());
        }
    }

    /**
     * @url OPTIONS {id}/commits
     */
    public function optionsCommits($id)
    {
        $this->sendAllowHeadersForCommits();
    }


    /**
     * @url OPTIONS {id}/labels
     */
    public function optionsLabels($id)
    {
        $this->sendAllowHeadersForLabels();
    }

    /**
     * Get labels
     *
     * Get the labels that are defined for this pull request
     *
     * <pre>
     * /!\ PullRequest REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @url GET {id}/labels
     *
     * @access protected
     *
     * @param int $id pull request ID
     * @param int $limit
     * @param int $offset
     *
     * @return array
     *
     * @throws RestException 403
     * @throws RestException 404 x Pull request does not exist
     */
    protected function getLabels($id, $limit = self::MAX_LIMIT, $offset = 0)
    {
        $this->checkAccess();
        $this->sendAllowHeadersForLabels();

        $pull_request_with_git_reference = $this->getAccessiblePullRequestWithGitReferenceForCurrentUser($id);
        $pull_request                    = $pull_request_with_git_reference->getPullRequest();
        $dest_repository                 = $this->getRepository($pull_request->getRepoDestId());

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $this->user_manager->getCurrentUser(),
            $dest_repository->getProject()
        );

        $collection            = $this->labels_retriever->getPaginatedLabelsForPullRequest($pull_request, $limit, $offset);
        $labels_representation = array_map(
            function (Label $label) {
                $representation = new LabelRepresentation();
                $representation->build($label);

                return $representation;
            },
            $collection->getLabels()
        );

        $this->sendAllowHeadersForLabels();
        Header::sendPaginationHeaders($limit, $offset, $collection->getTotalSize(), self::MAX_LIMIT);

        return [
            'labels' => $labels_representation,
        ];
    }

    /**
     * Update labels
     *
     * <p>Update the labels of the pull request. You can add or remove labels.</p>
     *
     * <p>Example of payload:</p>
     *
     * <pre>
     * {<br>
     * &nbsp;&nbsp;"add": [<br>
     * &nbsp;&nbsp;&nbsp;&nbsp;{ "id": 1 },<br>
     * &nbsp;&nbsp;&nbsp;&nbsp;{ "id": 2 },<br>
     * &nbsp;&nbsp;&nbsp;&nbsp;{ "id": 3 }<br>
     * &nbsp;&nbsp;],<br>
     * &nbsp;&nbsp;"remove": [<br>
     * &nbsp;&nbsp;&nbsp;&nbsp;{ "id": 4 },<br>
     * &nbsp;&nbsp;&nbsp;&nbsp;{ "id": 5 },<br>
     * &nbsp;&nbsp;&nbsp;&nbsp;{ "id": 6 }<br>
     * &nbsp;&nbsp;]<br>
     * }<br>
     * </pre>
     *
     * <p>This will add labels with ids 1, 2, and 3; and will remove labels with ids 4, 5, and 6.</p>
     *
     * <p>You can also create labels, they will be added to the list of project labels. Example:</p>
     *
     * <pre>
     * {<br>
     * &nbsp;&nbsp;"add": [<br>
     * &nbsp;&nbsp;&nbsp;&nbsp;{ "id": 1 },<br>
     * &nbsp;&nbsp;&nbsp;&nbsp;{ "id": 2 },<br>
     * &nbsp;&nbsp;&nbsp;&nbsp;{ "label": "Emergency Fix" }<br>
     * &nbsp;&nbsp;]<br>
     * }<br>
     * </pre>
     *
     * <p>This will create "Emergency Fix" label (if it does not already exist, else it uses the existing one),
     * and add it to the current pull request. Please note that you must use the id to remove labels from the
     * pull request.</p>
     *
     * <pre>
     * /!\ PullRequest REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @url PATCH {id}/labels
     *
     * @access protected
     *
     * @param int $id pull request ID
     *
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404 x Pull request does not exist
     */
    protected function patchLabels($id, LabelsPATCHRepresentation $body)
    {
        $this->checkAccess();
        $this->sendAllowHeadersForLabels();

        $pull_request_with_git_reference = $this->getWritablePullRequestWithGitReference($id);
        $pull_request                    = $pull_request_with_git_reference->getPullRequest();
        $repository_dest                 = $this->getRepository($pull_request->getRepoDestId());

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            $repository_dest->getProject()
        );

        try {
            $this->labels_updater->update($repository_dest->getProjectId(), $pull_request, $body);
        } catch (UnknownLabelException $exception) {
            throw new RestException(400, "Label is unknown in the project");
        } catch (UnableToAddAndRemoveSameLabelException $exception) {
            throw new RestException(400, "Unable to add and remove the same label");
        } catch (UnableToAddEmptyLabelException $exception) {
            throw new RestException(400, "Unable to add an empty label");
        } catch (\Exception $exception) {
            throw new RestException(500, $exception->getMessage());
        }
    }

    /**
     * Get pull request's impacted files
     *
     * Get the impacted files for a pull request.<br/>
     * User is not able to see a pull request in a git repository where he is not able to READ
     *
     * <pre>
     * /!\ PullRequest REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @url GET {id}/files
     *
     * @access protected
     *
     * @param int $id pull request ID
     *
     * @return array {@type PullRequest\REST\v1\PullRequestFileRepresentation}
     *
     * @throws RestException 403
     * @throws RestException 404 x Pull request does not exist
     */
    protected function getFiles($id)
    {
        $this->checkAccess();
        $this->sendAllowHeadersForPullRequests();

        $pull_request_with_git_reference = $this->getAccessiblePullRequestWithGitReferenceForCurrentUser($id);
        $pull_request                    = $pull_request_with_git_reference->getPullRequest();
        $git_repository_destination      = $this->getRepository($pull_request->getRepoDestId());
        $executor                        = $this->getExecutor($git_repository_destination);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $this->user_manager->getCurrentUser(),
            $git_repository_destination->getProject()
        );

        $file_representation_factory = new PullRequestFileRepresentationFactory($executor);

        try {
            $modified_files = $file_representation_factory->getModifiedFilesRepresentations($pull_request);
        } catch (UnknownReferenceException $exception) {
            throw new RestException(404, $exception->getMessage());
        }

        return $modified_files;
    }

    /**
     * Get the diff of a given file in a pull request
     *
     * Get the diff of a given file between the source branch and the dest branch for a pull request.<br/>
     * User is not able to see a pull request in a git repository where he is not able to READ
     *
     * <pre>
     * /!\ PullRequest REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @url GET {id}/file_diff
     *
     * @access protected
     *
     * @param  int $id pull request ID
     * @param  string $path File path {@from query}
     *
     * @return PullRequestFileUniDiffRepresentation {@type Tuleap\PullRequest\REST\v1\PullRequestFileUniDiffRepresentation}
     *
     * @throws RestException 403
     * @throws RestException 404 x Pull request does not exist
     * @throws RestException 404 x The file does not exist
     */
    protected function getFileDiff($id, $path)
    {
        $this->checkAccess();
        $this->sendAllowHeadersForPullRequests();

        $pull_request_with_git_reference = $this->getAccessiblePullRequestWithGitReferenceForCurrentUser($id);
        $pull_request                    = $pull_request_with_git_reference->getPullRequest();
        $git_repository_destination      = $this->getRepository($pull_request->getRepoDestId());

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $this->user_manager->getCurrentUser(),
            $git_repository_destination->getProject()
        );

        $git_project = (new ProjectProvider($git_repository_destination))->GetProject();
        $commit_src  = $git_project->GetCommit($pull_request->getSha1Src());
        $commit_dest = $git_project->GetCommit($pull_request->getSha1Dest());

        $object_reference_src  = $commit_src->PathToHash($path);
        $object_reference_dest = $commit_dest->PathToHash($path);

        if ($object_reference_src === '' && $object_reference_dest === '') {
            throw new RestException(404, 'The file does not exist');
        }

        $object_src  = $git_project->GetObject($object_reference_src, $object_type_src) ?: "";
        $object_dest = $git_project->GetObject($object_reference_dest, $object_type_dest) ?: "";

        $mime_type = 'text/plain';
        $charset   = 'utf-8';
        if ($object_type_src === Pack::OBJ_BLOB || $object_type_dest === Pack::OBJ_BLOB) {
            list($mime_type, $charset) = MimeDetector::getMimeInfo($path, $object_dest, $object_src);
        }

        $event = new PullRequestDiffRepresentationBuild($object_dest, $object_src);
        $this->event_manager->processEvent($event);

        $special_format = $event->getSpecialFormat();

        if ($charset === "binary" || $special_format !== '') {
            $diff            = new FileUniDiff();
            $inline_comments = [];
        } else {
            $executor_repo_destination = $this->getExecutor($git_repository_destination);
            $unidiff_builder           = new FileUniDiffBuilder();
            $diff                      = $unidiff_builder->buildFileUniDiffFromCommonAncestor(
                $executor_repo_destination,
                $path,
                $pull_request->getSha1Dest(),
                $pull_request->getSha1Src()
            );

            $inline_comment_builder = new PullRequestInlineCommentRepresentationBuilder(
                new \Tuleap\PullRequest\InlineComment\Dao(),
                $this->user_manager
            );
            $git_repository_source  = $this->getRepository($pull_request->getRepositoryId());
            $inline_comments        = $inline_comment_builder->getForFile($pull_request, $path, $git_repository_source->getProjectId());
        }

        return PullRequestFileUniDiffRepresentation::build($diff, $inline_comments, $mime_type, $charset, $special_format);
    }

    /**
     * Post a new inline comment
     *
     * Post a new inline comment for a given pull request file and a position (left | right)<br>
     * Format: { "content": "My new comment" , "unidiff_offset": 1, "file_path": "dir/myfile.txt" , position: "left" }
     *
     * <pre>
     * /!\ PullRequest REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @url POST {id}/inline-comments
     *
     * @access protected
     *
     * @param int $id Pull request id
     * @param PullRequestInlineCommentPOSTRepresentation $comment_data Comment {@from body} {@type Tuleap\PullRequest\REST\v1\PullRequestInlineCommentPOSTRepresentation}
     *
     * @status 201
     * @throws RestException 403
     */
    protected function postInline($id, PullRequestInlineCommentPOSTRepresentation $comment_data)
    {
        $this->checkAccess();
        $this->sendAllowHeadersForInlineComments();

        $pull_request_with_git_reference = $this->getAccessiblePullRequestWithGitReferenceForCurrentUser($id);
        $pull_request                    = $pull_request_with_git_reference->getPullRequest();
        $git_repository_source           = $this->getRepository($pull_request->getRepositoryId());
        $git_repository_destination      = $this->getRepository($pull_request->getRepoDestId());
        $user                            = $this->user_manager->getCurrentUser();

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            $git_repository_destination->getProject()
        );

        $git_project = (new ProjectProvider($git_repository_destination))->GetProject();
        $commit_src  = $git_project->GetCommit($pull_request->getSha1Src());
        $commit_dest = $git_project->GetCommit($pull_request->getSha1Dest());

        $object_reference_src  = $commit_src->PathToHash($comment_data->file_path);
        $object_reference_dest = $commit_dest->PathToHash($comment_data->file_path);

        if ($object_reference_src === '' && $object_reference_dest === '') {
            throw new RestException(404, 'The file does not exist');
        }

        if (! in_array($comment_data->position, ['left', 'right'])) {
            throw new RestException(400, 'Please provide a valid position, either left or right');
        }

        $post_date = time();

        $this->inline_comment_creator->insert(
            $pull_request,
            $user,
            $comment_data,
            $post_date,
            $git_repository_source->getProjectId()
        );

        $user_representation = MinimalUserRepresentation::build($this->user_manager->getUserById($user->getId()));

        return new PullRequestInlineCommentRepresentation(
            $comment_data->unidiff_offset,
            $user_representation,
            $post_date,
            $comment_data->content,
            $git_repository_source->getProjectId(),
            $comment_data->position
        );
    }

    /**
     * Create PullRequest
     *
     * Create a new pullrequest.<br/>
     *
     * <pre>
     * /!\ PullRequest REST routes are under construction and subject to changes /!\
     * </pre>
     * <br/>
     * Here is an example of a valid POST content:
     * <pre>
     * {<br/>
     * &nbsp;&nbsp;"repository_id": 3,<br/>
     * &nbsp;&nbsp;"branch_src": "dev",<br/>
     * &nbsp;&nbsp;"repository_dest_id": 3,<br/>
     * &nbsp;&nbsp;"branch_dest": "master"<br/>
     * }<br/>
     * </pre>
     *
     * @url POST
     *
     * @access protected
     *
     * @param  PullRequestPOSTRepresentation $content Id of the Git repository, name of the source branch and name of the destination branch
     * @return PullRequestReference
     *
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404
     * @status 201
     */
    protected function post(PullRequestPOSTRepresentation $content)
    {
        $this->checkAccess();
        $this->sendAllowHeadersForPullRequests();

        $user = $this->user_manager->getCurrentUser();

        $repository_id  = $content->repository_id;
        $repository_src = $this->getRepository($repository_id);
        $branch_src     = $content->branch_src;

        $repository_dest_id = $content->repository_dest_id;
        $repository_dest    = $this->getRepository($repository_dest_id);
        $branch_dest        = $content->branch_dest;

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            $repository_dest->getProject()
        );

        $this->checkUserCanReadRepository($user, $repository_src);

        try {
            $generated_pull_request = $this->pull_request_creator->generatePullRequest(
                $repository_src,
                $branch_src,
                $repository_dest,
                $branch_dest,
                $user
            );
        } catch (UnknownBranchNameException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (PullRequestCannotBeCreatedException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (PullRequestAlreadyExistsException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (PullRequestRepositoryMigratedOnGerritException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (PullRequestAnonymousUserException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (PullRequestTargetException $exception) {
            throw new RestException(400, $exception->getMessage());
        }

        if (! $generated_pull_request) {
            throw new RestException(500);
        }

        $pull_request_reference = PullRequestReference::fromPullRequest($generated_pull_request);

        $this->sendLocationHeader($pull_request_reference->uri);

        return $pull_request_reference;
    }

    /**
     * Partial update of a pull request
     *
     * Merge or abandon a pull request.
     * <br/>
     * -- OR --
     * <br/>
     * Update title and description of pull request.
     *
     * <pre>
     * /!\ PullRequest REST routes are under construction and subject to changes /!\
     * </pre>
     * <br/>
     *
     * Here is an example of a valid PATCH content to merge a pull request:
     * <pre>
     * {<br/>
     * &nbsp;&nbsp;"status": "merge"<br/>
     * }<br/>
     * </pre>
     * <br/>
     *
     * For now, only fast-forward merges are taken into account.
     * <br/>
     *
     * A pull request that has been abandoned cannot be merged later.<br/>
     * Here is an example of a valid PATCH content to abandon a pull request:
     * <pre>
     * {<br/>
     * &nbsp;&nbsp;"status": "abandon"<br/>
     * }<br/>
     * </pre>
     * <br/>
     *
     * Here is an example of a valid PATCH content to update a pull request:
     * <pre>
     * {<br/>
     * &nbsp;&nbsp;"title": "new title",<br/>
     * &nbsp;&nbsp;"description": "new description"<br/>
     * }<br/>
     * </pre>
     * <br/>
     *
     * @url PATCH {id}
     *
     * @access protected
     *
     * @param  int $id pull request ID
     * @param  PullRequestPATCHRepresentation $body new pull request status {@from body}
     * @return array {@type Tuleap\PullRequest\REST\v1\PullRequestRepresentation}
     *
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404 x Pull request does not exist
     * @throws RestException 500 x Error while abandoning the pull request
     * @throws RestException 500 x Error while merging the pull request
     */
    protected function patch($id, PullRequestPATCHRepresentation $body)
    {
        $this->checkAccess();
        $this->sendAllowHeadersForPullRequests();

        $user                            = $this->user_manager->getCurrentUser();
        $pull_request_with_git_reference = $this->getAccessiblePullRequestWithGitReferenceForCurrentUser($id);
        $pull_request                    = $pull_request_with_git_reference->getPullRequest();
        $repository_src                  = $this->getRepository($pull_request->getRepositoryId());
        $repository_dest                 = $this->getRepository($pull_request->getRepoDestId());

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            $repository_dest->getProject()
        );

        $status = $body->status;
        if ($status !== null) {
            $this->patchStatus($user, $pull_request, $status);
        } else {
            $this->patchInfo(
                $user,
                $pull_request,
                $repository_src->getProjectId(),
                $body
            );
        }
        $updated_pull_request = $this->pull_request_factory->getPullRequestById($id);

        $pr_representation_factory = new PullRequestRepresentationFactory(
            $this->access_control_verifier,
            new CommitStatusRetriever(new CommitStatusDAO()),
            $this->getGitoliteAccessURLGenerator()
        );

        return $pr_representation_factory->getPullRequestRepresentation(
            new PullRequestWithGitReference($updated_pull_request, $pull_request_with_git_reference->getGitReference()),
            $repository_src,
            $repository_dest,
            GitExec::buildFromRepository($repository_dest),
            $user
        );
    }

    /**
     * @throws RestException
     */
    private function patchStatus(PFUser $user, PullRequest $pull_request, $status)
    {
        $status_patcher = new StatusPatcher(
            $this->git_repository_factory,
            $this->access_control_verifier,
            $this->permission_checker,
            $this->pull_request_closer,
            new URLVerification(),
            $this->logger
        );

        $status_patcher->patchStatus($user, $pull_request, $status);
    }

    /**
     * @throws RestException 400
     * @throws RestException 403
     */
    private function patchInfo(
        PFUser $user,
        PullRequest $pull_request,
        $project_id,
        $body,
    ) {
        $this->checkUserCanMerge($pull_request, $user);

        $trimmed_title = trim($body->title);
        if (empty($trimmed_title)) {
            throw new RestException(400, 'Title cannot be empty');
        }

        $this->pull_request_factory->updateTitleAndDescription(
            $user,
            $pull_request,
            $project_id,
            $body->title,
            $body->description
        );
    }

    /**
     * @url OPTIONS {id}/timeline
     */
    public function optionsTimeline($id)
    {
        return $this->sendAllowHeadersForTimeline();
    }

    /**
     * Get pull request's timeline
     *
     * <pre>
     * /!\ PullRequest REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @url GET {id}/timeline
     *
     * @access protected
     *
     * @param int    $id     Pull request id
     * @param int    $limit  Number of fetched comments {@from path} {@min 0} {@max 50}
     * @param int    $offset Position of the first comment to fetch {@from path} {@min 0}
     *
     * @return array {@type Tuleap\PullRequest\REST\v1\TimelineRepresentation}
     *
     * @throws RestException 403
     * @throws RestException 404
     */
    protected function getTimeline($id, $limit = 10, $offset = 0)
    {
        $this->checkAccess();
        $this->sendAllowHeadersForTimeline();

        $pull_request_with_git_reference = $this->getAccessiblePullRequestWithGitReferenceForCurrentUser($id);
        $pull_request                    = $pull_request_with_git_reference->getPullRequest();
        $git_repository                  = $this->getRepository($pull_request->getRepositoryId());
        $project_id                      = $git_repository->getProjectId();

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $this->user_manager->getCurrentUser(),
            $git_repository->getProject()
        );

        $paginated_timeline_representation = $this->paginated_timeline_representation_builder->getPaginatedTimelineRepresentation(
            $pull_request,
            $project_id,
            $limit,
            $offset
        );

        Header::sendPaginationHeaders($limit, $offset, $paginated_timeline_representation->total_size, self::MAX_LIMIT);

        return $paginated_timeline_representation;
    }

    /**
     * @url OPTIONS {id}/comments
     */
    public function optionsComments($id)
    {
        return $this->sendAllowHeadersForComments();
    }

    /**
     * Get pull request's comments
     *
     * <pre>
     * /!\ PullRequest REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @url GET {id}/comments
     *
     * @access protected
     *
     * @param int    $id     Pull request id
     * @param int    $limit  Number of fetched comments {@from path} {@min 0} {@max 50}
     * @param int    $offset Position of the first comment to fetch {@from path} {@min 0}
     * @param string $order  In which order comments are fetched. Default is asc. {@from path}{@choice asc,desc}
     *
     * @return array {@type Tuleap\PullRequest\REST\v1\CommentRepresentation}
     *
     * @throws RestException 403
     * @throws RestException 404
     */
    protected function getComments($id, $limit = 10, $offset = 0, $order = 'asc')
    {
        $this->checkAccess();
        $this->sendAllowHeadersForComments();

        $pull_request_with_git_reference = $this->getAccessiblePullRequestWithGitReferenceForCurrentUser($id);
        $pull_request                    = $pull_request_with_git_reference->getPullRequest();
        $git_repository                  = $this->getRepository($pull_request->getRepositoryId());
        $project_id                      = $git_repository->getProjectId();

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $this->user_manager->getCurrentUser(),
            $git_repository->getProject()
        );

        $paginated_comments_representations = $this->paginated_comments_representations_builder->getPaginatedCommentsRepresentations(
            $id,
            $project_id,
            $limit,
            $offset,
            $order
        );

        Header::sendPaginationHeaders($limit, $offset, $paginated_comments_representations->getTotalSize(), self::MAX_LIMIT);

        return $paginated_comments_representations->getCommentsRepresentations();
    }

    /**
     * Post a new comment
     *
     * Post a new comment for a given pull request <br>
     * Format: { "content": "My new comment" }
     *
     * <pre>
     * /!\ PullRequest REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @url POST {id}/comments
     *
     * @access protected
     *
     * @param int $id Pull request id
     * @param CommentPOSTRepresentation $comment_data Comment {@from body} {@type Tuleap\PullRequest\REST\v1\CommentPOSTRepresentation}
     *
     * @status 201
     * @throws RestException 401
     * @throws RestException 403
     */
    protected function postComments($id, CommentPOSTRepresentation $comment_data)
    {
        $this->checkAccess();
        $this->sendAllowHeadersForComments();

        $user                            = $this->user_manager->getCurrentUser();
        $pull_request_with_git_reference = $this->getAccessiblePullRequestWithGitReferenceForCurrentUser($id);
        $pull_request                    = $pull_request_with_git_reference->getPullRequest();
        $git_repository                  = $this->getRepository($pull_request->getRepositoryId());
        $project_id                      = $git_repository->getProjectId();

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            $git_repository->getProject()
        );

        $current_time   = time();
        $comment        = new Comment(0, $id, $user->getId(), $current_time, $comment_data->content);
        $new_comment_id = $this->comment_factory->save($comment, $user, $project_id);

        $user_representation = MinimalUserRepresentation::build($user);

        return new CommentRepresentation($new_comment_id, $project_id, $user_representation, $comment->getPostDate(), $comment->getContent());
    }

    /**
     * Get pull request's reviewers
     *
     * @url OPTIONS {id}/reviewers
     *
     * @param int $id Pull request ID
     */
    public function optionsReviewers(int $id): void
    {
        Header::allowOptionsGetPut();
    }

    /**
     * Get pull request's reviewers
     *
     * @url GET {id}/reviewers
     *
     * @access hybrid
     *
     * @param int $id Pull request ID
     *
     *
     * @throws RestException 403
     * @throws RestException 404
     */
    public function getReviewers(int $id): ReviewersRepresentation
    {
        $this->checkAccess();
        $this->optionsReviewers($id);

        $pull_request = $this->getAccessiblePullRequest($id);

        $reviewer_retrievers = new ReviewerRetriever($this->user_manager, new ReviewerDAO(), $this->permission_checker);

        return ReviewersRepresentation::fromUsers(...$reviewer_retrievers->getReviewers($pull_request));
    }

    /**
     * Set pull request's reviewers
     *
     * @url PUT {id}/reviewers
     *
     * @status 204
     *
     * @param int $id Pull request ID
     *
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404
     */
    protected function putReviewers(int $id, ReviewersPUTRepresentation $representation): void
    {
        $this->checkAccess();
        $this->optionsReviewers($id);

        $pull_request = $this->getWritablePullRequestWithGitReference($id);

        $information_extractor = new ReviewerRepresentationInformationExtractor(
            new UserRESTReferenceRetriever($this->user_manager),
        );
        $users                 = $information_extractor->getUsers($representation);

        $reviewer_updater = new ReviewerUpdater(
            new ReviewerDAO(),
            $this->permission_checker,
            PullRequestNotificationSupport::buildDispatcher($this->logger)
        );
        try {
            $reviewer_updater->updatePullRequestReviewers(
                $pull_request->getPullRequest(),
                $this->user_manager->getCurrentUser(),
                new \DateTimeImmutable(),
                ...$users
            );
        } catch (UserCannotBeAddedAsReviewerException $exception) {
            throw new RestException(
                400,
                'User #' . $exception->getUser()->getId() . ' cannot access this pull request'
            );
        } catch (ReviewersCannotBeUpdatedOnClosedPullRequestException $exception) {
            throw new RestException(
                403,
                'This pull request is already closed, the reviewers can not be updated'
            );
        }
    }

    /**
     * @param $id
     * @return PullRequestWithGitReference
     */
    private function getWritablePullRequestWithGitReference($id)
    {
        $pull_request_with_git_reference = $this->getAccessiblePullRequestWithGitReferenceForCurrentUser($id);
        $pull_request                    = $pull_request_with_git_reference->getPullRequest();

        $current_user = $this->user_manager->getCurrentUser();

        $this->checkUserCanMerge($pull_request, $current_user);

        return $pull_request_with_git_reference;
    }

    /**
     * @throws RestException
     */
    private function getAccessiblePullRequest(int $pull_request_id): PullRequest
    {
        try {
            $pull_request = $this->pull_request_factory->getPullRequestById($pull_request_id);
            $current_user = $this->user_manager->getCurrentUser();
            $this->permission_checker->checkPullRequestIsReadableByUser($pull_request, $current_user);

            return $pull_request;
        } catch (PullRequestNotFoundException $exception) {
            throw new RestException(404);
        } catch (\GitRepoNotFoundException $exception) {
            throw new RestException(404);
        } catch (Project_AccessProjectNotFoundException $exception) {
            throw new RestException(404);
        } catch (Project_AccessException $exception) {
            throw new RestException(403, $exception->getMessage());
        } catch (UserCannotReadGitRepositoryException $exception) {
            throw new RestException(403, 'User is not able to READ the git repository');
        } catch (GitPullRequestReferenceNotFoundException $exception) {
            throw new RestException(404);
        }
    }

    /**
     * @throws RestException
     */
    private function getAccessiblePullRequestWithGitReferenceForCurrentUser(int $id): PullRequestWithGitReference
    {
        try {
            $pull_request = $this->getAccessiblePullRequest($id);

            $git_reference = $this->git_pull_request_reference_retriever->getGitReferenceFromPullRequest($pull_request);
        } catch (GitPullRequestReferenceNotFoundException $exception) {
            throw new RestException(404);
        }

        if ($git_reference->isGitReferenceBroken()) {
            throw new RestException(
                410,
                dgettext('tuleap-pullrequest', 'The pull request is not accessible anymore')
            );
        }

        $this->updateGitReferenceIfNeeded($pull_request, $git_reference);

        return new PullRequestWithGitReference($pull_request, $git_reference);
    }

    private function updateGitReferenceIfNeeded(PullRequest $pull_request, GitPullRequestReference $git_reference)
    {
        if (! $git_reference->isGitReferenceNeedToBeCreatedInRepository()) {
            return;
        }
        $repository_source      = $this->getRepository($pull_request->getRepositoryId());
        $repository_destination = $this->getRepository($pull_request->getRepoDestId());
        $this->git_pull_request_reference_updater->updatePullRequestReference(
            $pull_request,
            GitExec::buildFromRepository($repository_source),
            GitExec::buildFromRepository($repository_destination),
            $repository_destination
        );
    }

    private function getRepository($repository_id)
    {
        $repository = $this->git_repository_factory->getRepositoryById($repository_id);

        if (! $repository) {
            throw new RestException(404, "Git repository not found");
        }

        return $repository;
    }

    private function checkUserCanReadRepository(PFUser $user, GitRepository $repository)
    {
        ProjectAuthorization::userCanAccessProject($user, $repository->getProject(), new URLVerification());

        if (! $repository->userCanRead($user)) {
            throw new RestException(403, 'User is not able to READ the git repository');
        }
    }

    /**
     * @throws RestException 403
     * @throws RestException 404
     */
    private function checkUserCanMerge(PullRequest $pull_request, PFUser $user): void
    {
        try {
            $this->permission_checker->checkPullRequestIsMergeableByUser($pull_request, $user);
        } catch (UserCannotMergePullRequestException $e) {
            throw new RestException(403, 'User is not able to WRITE the git repository');
        } catch (GitRepoNotFoundException $e) {
            throw new RestException(404, 'Git repository not found');
        }
    }

    private function sendLocationHeader($uri)
    {
        $uri_with_api_version = '/api/v1/' . $uri;

        Header::Location($uri_with_api_version);
    }

    private function sendAllowHeadersForPullRequests()
    {
        Header::allowOptionsGetPostPatch();
    }

    private function sendAllowHeadersForTimeline()
    {
        Header::allowOptionsGet();
    }

    private function sendAllowHeadersForCommits()
    {
        Header::allowOptionsGet();
    }


    private function sendAllowHeadersForLabels()
    {
        Header::allowOptionsGetPatch();
    }

    private function sendAllowHeadersForComments()
    {
        Header::allowOptionsGetPost();
    }

    private function sendAllowHeadersForInlineComments()
    {
        Header::allowOptionsGetPost();
    }

    /**
     * @return GitExec
     */
    private function getExecutor(GitRepository $git_repository)
    {
        return new GitExec($git_repository->getFullPath(), $git_repository->getFullPath());
    }

    /**
     * @return GitoliteAccessURLGenerator
     */
    private function getGitoliteAccessURLGenerator()
    {
        return new GitoliteAccessURLGenerator($this->git_plugin->getPluginInfo());
    }
}
