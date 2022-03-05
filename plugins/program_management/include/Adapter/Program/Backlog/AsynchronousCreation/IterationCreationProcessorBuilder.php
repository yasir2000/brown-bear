<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation;

use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\ProgramManagement\Adapter\ArtifactVisibleVerifier;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\SubmissionDateRetriever;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ArtifactLinkValueFormatter;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ChangesetValuesFormatter;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values\DateValueFormatter;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values\DescriptionValueFormatter;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values\FieldValuesGathererRetriever;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldsGatherer;
use Tuleap\ProgramManagement\Adapter\Program\Plan\ProgramAdapter;
use Tuleap\ProgramManagement\Adapter\Program\PlanningAdapter;
use Tuleap\ProgramManagement\Adapter\Program\ProgramDao;
use Tuleap\ProgramManagement\Adapter\ProjectReferenceRetriever;
use Tuleap\ProgramManagement\Adapter\Team\MirroredTimeboxes\MirroredTimeboxesDao;
use Tuleap\ProgramManagement\Adapter\Team\VisibleTeamSearcher;
use Tuleap\ProgramManagement\Adapter\Workspace\MessageLog;
use Tuleap\ProgramManagement\Adapter\Workspace\ProjectManagerAdapter;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\Artifact\ArtifactFactoryAdapter;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\Fields\FormElementFactoryAdapter;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\TrackerFactoryAdapter;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\TrackerOfArtifactRetriever;
use Tuleap\ProgramManagement\Adapter\Workspace\UserManagerAdapter;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\BuildIterationCreationProcessor;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\IterationCreationProcessor;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ProcessIterationCreation;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Artifact\Changeset\ArtifactChangesetSaver;
use Tuleap\Tracker\Artifact\Changeset\ChangesetFromXmlDao;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupPermissionDao;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupPermissionInserter;
use Tuleap\Tracker\Artifact\Changeset\FieldsToBeSavedInSpecificOrderRetriever;
use Tuleap\Tracker\Artifact\Creation\TrackerArtifactCreator;
use Tuleap\Tracker\FormElement\ArtifactLinkValidator;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkFieldValueDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\LinksRetriever;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ParentLinkAction;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeDao;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldDetector;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsRetriever;
use Tuleap\Tracker\Workflow\SimpleMode\SimpleWorkflowDao;
use Tuleap\Tracker\Workflow\SimpleMode\State\StateFactory;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionExtractor;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionRetriever;
use Tuleap\Tracker\Workflow\WorkflowUpdateChecker;

final class IterationCreationProcessorBuilder implements BuildIterationCreationProcessor
{
    public function getProcessor(): ProcessIterationCreation
    {
        $logger                   = \BackendLogger::getDefaultLogger('program_management_syslog');
        $artifact_factory         = \Tracker_ArtifactFactory::instance();
        $tracker_factory          = \TrackerFactory::instance();
        $form_element_factory     = \Tracker_FormElementFactory::instance();
        $program_DAO              = new ProgramDao();
        $project_manager          = \ProjectManager::instance();
        $event_manager            = \EventManager::instance();
        $user_retriever           = new UserManagerAdapter(\UserManager::instance());
        $transaction_executor     = new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection());
        $message_logger           = MessageLog::buildFromLogger($logger);
        $visibility_verifier      = new ArtifactVisibleVerifier($artifact_factory, $user_retriever);
        $artifact_links_usage_dao = new ArtifactLinksUsageDao();
        $artifact_changeset_dao   = new \Tracker_Artifact_ChangesetDao();
        $tracker_retriever        = new TrackerFactoryAdapter($tracker_factory);
        $artifact_retriever       = new ArtifactFactoryAdapter($artifact_factory);
        $field_retriever          = new FormElementFactoryAdapter($tracker_retriever, $form_element_factory);
        $project_manager_adapter  = new ProjectManagerAdapter($project_manager, $user_retriever);

        $synchronized_fields_gatherer = new SynchronizedFieldsGatherer(
            $tracker_retriever,
            new \Tracker_Semantic_TitleFactory(),
            new \Tracker_Semantic_DescriptionFactory(),
            new \Tracker_Semantic_StatusFactory(),
            new SemanticTimeframeBuilder(
                new SemanticTimeframeDao(),
                $form_element_factory,
                $tracker_factory,
                new LinksRetriever(
                    new ArtifactLinkFieldValueDao(),
                    $artifact_factory
                )
            ),
            $field_retriever
        );

        $changeset_values_formatter = new ChangesetValuesFormatter(
            new ArtifactLinkValueFormatter(),
            new DescriptionValueFormatter(),
            new DateValueFormatter()
        );

        $artifact_creator = new ArtifactCreatorAdapter(
            TrackerArtifactCreator::build(
                \Tracker_Artifact_Changeset_InitialChangesetCreator::build($logger),
                \Tracker_Artifact_Changeset_InitialChangesetFieldsValidator::build(),
                $logger
            ),
            $tracker_retriever,
            $user_retriever,
            $changeset_values_formatter
        );

        $changeset_creator = new \Tracker_Artifact_Changeset_NewChangesetCreator(
            new \Tracker_Artifact_Changeset_NewChangesetFieldsValidator(
                $form_element_factory,
                new ArtifactLinkValidator(
                    $artifact_factory,
                    new TypePresenterFactory(
                        new TypeDao(),
                        $artifact_links_usage_dao
                    ),
                    $artifact_links_usage_dao
                ),
                new WorkflowUpdateChecker(
                    new FrozenFieldDetector(
                        new TransitionRetriever(
                            new StateFactory(
                                \TransitionFactory::instance(),
                                new SimpleWorkflowDao()
                            ),
                            new TransitionExtractor()
                        ),
                        FrozenFieldsRetriever::instance()
                    ),
                ),
            ),
            new FieldsToBeSavedInSpecificOrderRetriever($form_element_factory),
            $artifact_changeset_dao,
            new \Tracker_Artifact_Changeset_CommentDao(),
            $artifact_factory,
            \EventManager::instance(),
            \ReferenceManager::instance(),
            new \Tracker_Artifact_Changeset_ChangesetDataInitializator($form_element_factory),
            $transaction_executor,
            new ArtifactChangesetSaver(
                $artifact_changeset_dao,
                $transaction_executor,
                new \Tracker_ArtifactDao(),
                new ChangesetFromXmlDao()
            ),
            new ParentLinkAction($artifact_factory),
            new TrackerPrivateCommentUGroupPermissionInserter(new TrackerPrivateCommentUGroupPermissionDao())
        );

        $changeset_adder = new ChangesetAdder(
            $artifact_retriever,
            $user_retriever,
            $changeset_values_formatter,
            $changeset_creator
        );

        $mirrors_creator = new IterationsCreator(
            $transaction_executor,
            new PlanningAdapter(\PlanningFactory::build(), $user_retriever),
            new StatusValueMapper($form_element_factory),
            $synchronized_fields_gatherer,
            $artifact_creator,
            new MirroredTimeboxesDao(),
            $visibility_verifier,
            new TrackerOfArtifactRetriever($artifact_retriever),
            $changeset_adder,
            new ProjectReferenceRetriever($project_manager_adapter)
        );

        $project_access_checker = new ProjectAccessChecker(
            new RestrictedUserCanAccessProjectVerifier(),
            $event_manager
        );

        return new IterationCreationProcessor(
            $message_logger,
            $synchronized_fields_gatherer,
            new FieldValuesGathererRetriever($artifact_retriever, $form_element_factory),
            new SubmissionDateRetriever($artifact_retriever),
            $program_DAO,
            ProgramAdapter::instance(),
            new VisibleTeamSearcher($program_DAO, $user_retriever, $project_manager_adapter, $project_access_checker),
            $mirrors_creator
        );
    }
}
