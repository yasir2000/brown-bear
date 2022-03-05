<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\ActionButtons;

use PFUser;
use Tracker;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\ArtifactsDeletion\ArtifactDeletionLimitRetriever;
use Tuleap\Tracker\Artifact\ArtifactsDeletion\ArtifactsDeletionLimitReachedException;
use Tuleap\Tracker\Artifact\ArtifactsDeletion\DeletionOfArtifactsIsNotAllowedException;

class ArtifactMoveButtonPresenterBuilder
{
    /**
     * @var ArtifactDeletionLimitRetriever
     */
    private $deletion_limit_retriever;
    /**
     * @var \EventManager
     */
    private $event_manager;

    public function __construct(
        ArtifactDeletionLimitRetriever $deletion_limit_retriever,
        \EventManager $event_manager,
    ) {
        $this->deletion_limit_retriever = $deletion_limit_retriever;
        $this->event_manager            = $event_manager;
    }

    public function getMoveArtifactButton(PFUser $user, Artifact $artifact)
    {
        if (! $artifact->getTracker()->userIsAdmin($user)) {
            return;
        }

        $errors = [];

        $limit_error = $this->collectErrorRelatedToDeletionLimit($user);
        if ($limit_error) {
            $errors[] = $limit_error;
        }

        $event = new MoveArtifactActionAllowedByPluginRetriever($artifact);
        $this->event_manager->processEvent($event);
        $semantic_error = $this->collectErrorRelatedToSemantics($artifact->getTracker(), $event);
        if ($semantic_error) {
            $errors[] = $semantic_error;
        }

        $external_errors = $this->collectErrorsThrownByExternalPlugins($event);
        if ($external_errors) {
            $errors[] = $external_errors;
        }

        $links_error = $this->collectErrorsRelatedToArtifactLinks($artifact, $user);
        if ($links_error) {
            $errors[] = $links_error;
        }

        return new ArtifactMoveButtonPresenter(
            dgettext('tuleap-tracker', "Move this artifact"),
            $errors
        );
    }

    public function getMoveArtifactModal(Artifact $artifact)
    {
        return new ArtifactMoveModalPresenter($artifact);
    }

    /**
     *
     * @return string
     */
    private function collectErrorRelatedToDeletionLimit(PFUser $user)
    {
        try {
            $this->deletion_limit_retriever->getNumberOfArtifactsAllowedToDelete($user);
        } catch (DeletionOfArtifactsIsNotAllowedException $exception) {
            return $exception->getMessage();
        } catch (ArtifactsDeletionLimitReachedException $exception) {
            return $exception->getMessage();
        }

        return;
    }

    private function collectErrorRelatedToSemantics(
        Tracker $tracker,
        MoveArtifactActionAllowedByPluginRetriever $event,
    ) {
        if (
            $tracker->hasSemanticsTitle() ||
            $tracker->hasSemanticsDescription() ||
            $tracker->hasSemanticsStatus() ||
            $tracker->getContributorField() !== null ||
            $event->hasExternalSemanticDefined()
        ) {
            return;
        }

        return dgettext("tuleap-tracker", "No semantic defined in this tracker.");
    }

    private function collectErrorsRelatedToArtifactLinks(Artifact $artifact, PFUser $user)
    {
        if ($artifact->getLinkedAndReverseArtifacts($user)) {
            return dgettext("tuleap-tracker", "Artifacts with artifact links can not be moved.");
        }

        return;
    }

    private function collectErrorsThrownByExternalPlugins(MoveArtifactActionAllowedByPluginRetriever $event)
    {
        if ($event->doesAnExternalPluginForbiddenTheMove()) {
            return $event->getErrorMessage();
        }

        return;
    }
}
