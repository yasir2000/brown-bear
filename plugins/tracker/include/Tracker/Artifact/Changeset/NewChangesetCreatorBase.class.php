<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\ArtifactInstrumentation;
use Tuleap\Tracker\Artifact\Changeset\ArtifactChangesetSaver;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupPermissionInserter;
use Tuleap\Tracker\Artifact\Changeset\FieldsToBeSavedInSpecificOrderRetriever;
use Tuleap\Tracker\Artifact\Event\ArtifactUpdated;
use Tuleap\Tracker\Artifact\Exception\FieldValidationException;
use Tuleap\Tracker\Artifact\XMLImport\TrackerImportConfig;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\LinkToParentWithoutCurrentArtifactChangeException;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ParentLinkAction;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;

/**
 * I am a Template Method to create a new changeset (update of an artifact)
 */
abstract class Tracker_Artifact_Changeset_NewChangesetCreatorBase extends Tracker_Artifact_Changeset_ChangesetCreatorBase //phpcs:ignore
{
    /** @var Tracker_Artifact_ChangesetDao */
    protected $changeset_dao;

    /** @var Tracker_Artifact_Changeset_CommentDao */
    protected $changeset_comment_dao;
    /**
     * @var ReferenceManager
     */
    private $reference_manager;
    /**
     * @var DBTransactionExecutor
     */
    private $transaction_executor;
    /**
     * @var \Tuleap\Tracker\Artifact\Changeset\ArtifactChangesetSaver
     */
    private $artifact_changeset_saver;

    /**
     * @var ParentLinkAction
     */
    private $parent_link_action;
    /**
     * @var TrackerPrivateCommentUGroupPermissionInserter
     */
    private $comment_ugroup_permission_inserter;

    public function __construct(
        Tracker_Artifact_Changeset_FieldsValidator $fields_validator,
        FieldsToBeSavedInSpecificOrderRetriever $fields_retriever,
        Tracker_Artifact_ChangesetDao $changeset_dao,
        Tracker_Artifact_Changeset_CommentDao $changeset_comment_dao,
        Tracker_ArtifactFactory $artifact_factory,
        EventManager $event_manager,
        ReferenceManager $reference_manager,
        Tracker_Artifact_Changeset_ChangesetDataInitializator $field_initializator,
        DBTransactionExecutor $transaction_executor,
        ArtifactChangesetSaver $artifact_changeset_saver,
        ParentLinkAction $parent_link_action,
        TrackerPrivateCommentUGroupPermissionInserter $comment_ugroup_permission_inserter,
    ) {
        parent::__construct(
            $fields_validator,
            $fields_retriever,
            $artifact_factory,
            $event_manager,
            $field_initializator
        );

        $this->changeset_dao                      = $changeset_dao;
        $this->changeset_comment_dao              = $changeset_comment_dao;
        $this->reference_manager                  = $reference_manager;
        $this->transaction_executor               = $transaction_executor;
        $this->artifact_changeset_saver           = $artifact_changeset_saver;
        $this->parent_link_action                 = $parent_link_action;
        $this->comment_ugroup_permission_inserter = $comment_ugroup_permission_inserter;
    }

    /**
     * Update an artifact (means create a new changeset)
     * @param ProjectUGroup[] $ugroups
     * @throws Tracker_NoChangeException In the validation
     * @throws FieldValidationException
     *
     * @throws Tracker_Exception In the validation
     */
    public function create(
        Artifact $artifact,
        array $fields_data,
        string $comment,
        PFUser $submitter,
        int $submitted_on,
        bool $send_notification,
        string $comment_format,
        CreatedFileURLMapping $url_mapping,
        TrackerImportConfig $tracker_import_config,
        array $ugroups,
    ): ?Tracker_Artifact_Changeset {
        $comment = trim($comment);

        $email = null;
        if ($submitter->isAnonymous()) {
            $email = $submitter->getEmail();
        }

        try {
            $new_changeset = $this->transaction_executor->execute(function () use (
                $artifact,
                $fields_data,
                $comment,
                $comment_format,
                $submitter,
                $submitted_on,
                $email,
                $url_mapping,
                $tracker_import_config,
                $ugroups
            ) {
                try {
                    $this->validateNewChangeset($artifact, $fields_data, $comment, $submitter, $email);

                    $previous_changeset = $artifact->getLastChangeset();

                    /*
                     * Post actions were run by validateNewChangeset but they modified a
                     * different set of $fields_data in the case of massChange;
                     * we run them again for the current $fields_data
                     */
                    $artifact->getWorkflow()->before($fields_data, $submitter, $artifact);

                    try {
                        $changeset_id = $this->artifact_changeset_saver->saveChangeset(
                            $artifact,
                            $submitter,
                            $submitted_on,
                            $tracker_import_config
                        );
                    } catch (Tracker_Artifact_Exception_CannotCreateNewChangeset $exception) {
                        $GLOBALS['Response']->addFeedback(
                            'error',
                            dgettext('tuleap-tracker', 'Unable to update the artifact')
                        );
                        throw new Tracker_ChangesetNotCreatedException();
                    }

                    $this->storeFieldsValues(
                        $artifact,
                        $previous_changeset,
                        $fields_data,
                        $submitter,
                        $changeset_id,
                        $url_mapping
                    );

                    if (
                        ! $this->storeComment(
                            $artifact,
                            $comment,
                            $submitter,
                            $submitted_on,
                            $comment_format,
                            $changeset_id,
                            $url_mapping,
                            $ugroups
                        )
                    ) {
                        throw new Tracker_CommentNotStoredException();
                    }

                    $new_changeset = new Tracker_Artifact_Changeset(
                        $changeset_id,
                        $artifact,
                        $submitter->getId(),
                        $submitted_on,
                        $email
                    );
                    $artifact->addChangeset($new_changeset);

                    $save_after_ok = $this->saveArtifactAfterNewChangeset(
                        $artifact,
                        $fields_data,
                        $submitter,
                        $new_changeset,
                        $previous_changeset
                    );

                    if (! $save_after_ok) {
                        throw new Tracker_AfterSaveException();
                    }
                    ArtifactInstrumentation::increment(ArtifactInstrumentation::TYPE_UPDATED);
                    return $new_changeset;
                } catch (Tracker_NoChangeException $exception) {
                    throw $exception;
                } catch (LinkToParentWithoutCurrentArtifactChangeException $exception) {
                    return null;
                } catch (Tracker_Exception $exception) {
                    throw $exception;
                }
            });


            if (! $new_changeset) {
                return null;
            }
            if (! $tracker_import_config->isFromXml()) {
                $artifact->getChangeset((int) $new_changeset->getId())->executePostCreationActions($send_notification);
            }

            $this->event_manager->processEvent(new ArtifactUpdated($artifact, $submitter, $new_changeset));

            return $new_changeset;
        } catch (PDOException $exception) {
            throw new Tracker_ChangesetCommitException($exception);
        }
    }

    abstract protected function saveNewChangesetForField(
        Tracker_FormElement_Field $field,
        Artifact $artifact,
        $previous_changeset,
        array $fields_data,
        PFUser $submitter,
        $changeset_id,
        CreatedFileURLMapping $url_mapping,
    ): bool;

    /**
     * @throws Tracker_FieldValueNotStoredException
     */
    private function storeFieldsValues(
        Artifact $artifact,
        $previous_changeset,
        array $fields_data,
        PFUser $submitter,
        $changeset_id,
        CreatedFileURLMapping $url_mapping,
    ): bool {
        foreach ($this->fields_retriever->getFields($artifact) as $field) {
            if (
                ! $this->saveNewChangesetForField(
                    $field,
                    $artifact,
                    $previous_changeset,
                    $fields_data,
                    $submitter,
                    $changeset_id,
                    $url_mapping
                )
            ) {
                $purifier = Codendi_HTMLPurifier::instance();
                throw new Tracker_FieldValueNotStoredException(
                    sprintf(dgettext('tuleap-tracker', 'The field "%1$s" cannot be stored.'), $purifier->purify($field->getLabel()))
                );
            }
        }

        return true;
    }

    /**
     * @param ProjectUGroup[] $ugroups
     */
    private function storeComment(
        Artifact $artifact,
        $comment,
        PFUser $submitter,
        $submitted_on,
        $comment_format,
        $changeset_id,
        CreatedFileURLMapping $url_mapping,
        array $ugroups,
    ): bool {
        $comment_format = Tracker_Artifact_Changeset_Comment::checkCommentFormat($comment_format);

        if ($comment_format === Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT) {
            $substitutor = new \Tuleap\Tracker\FormElement\Field\File\FileURLSubstitutor();
            $comment     = $substitutor->substituteURLsInHTML($comment, $url_mapping);
        }

        $comment_added = $this->changeset_comment_dao->createNewVersion(
            $changeset_id,
            $comment,
            $submitter->getId(),
            $submitted_on,
            0,
            $comment_format
        );
        if (! $comment_added) {
            return false;
        }

        if (is_int($comment_added)) {
            $this->comment_ugroup_permission_inserter->insertUGroupsOnPrivateComment($comment_added, $ugroups);
        }

        $this->reference_manager->extractCrossRef(
            $comment,
            $artifact->getId(),
            Artifact::REFERENCE_NATURE,
            $artifact->getTracker()->getGroupID(),
            $submitter->getId(),
            $artifact->getTracker()->getItemName()
        );

        return true;
    }

    private function validateNewChangeset(
        Artifact $artifact,
        array $fields_data,
        $comment,
        PFUser $submitter,
        $email,
    ): void {
        if ($submitter->isAnonymous() && ($email == null || $email == '')) {
            $message = dgettext('tuleap-tracker', 'You are not logged in.');
            throw new Tracker_Exception($message);
        }

        $are_fields_valid = $this->fields_validator->validate(
            $artifact,
            $submitter,
            $fields_data,
            new \Tuleap\Tracker\Changeset\Validation\NullChangesetValidationContext()
        );
        if (! $are_fields_valid) {
            $errors_from_feedback = $GLOBALS['Response']->getFeedbackErrors();
            $GLOBALS['Response']->clearFeedbackErrors();

            throw new FieldValidationException($errors_from_feedback);
        }

        $last_changeset = $artifact->getLastChangeset();

        if ($last_changeset && ! $comment && ! $last_changeset->hasChanges($fields_data)) {
            if ($this->parent_link_action->linkParent($artifact, $submitter, $fields_data)) {
                throw new LinkToParentWithoutCurrentArtifactChangeException();
            }
            throw new Tracker_NoChangeException($artifact->getId(), $artifact->getXRef());
        }

        $workflow    = $artifact->getWorkflow();
        $fields_data = $this->field_initializator->process($artifact, $fields_data);

        $workflow->validate($fields_data, $artifact, $comment, $submitter);
        /*
         * We need to run the post actions to validate the data
         */
        $workflow->before($fields_data, $submitter, $artifact);
        $workflow->checkGlobalRules($fields_data);
    }
}
