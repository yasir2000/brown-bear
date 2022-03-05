<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\Artifact\Changeset\Comment\NewChangesetCommentRepresentation;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_REST_Artifact_ArtifactUpdater
{
    /** @var Tracker_REST_Artifact_ArtifactValidator */
    private $artifact_validator;

    public function __construct(Tracker_REST_Artifact_ArtifactValidator $artifact_validator)
    {
        $this->artifact_validator = $artifact_validator;
    }

    public static function build(): self
    {
        return new self(new Tracker_REST_Artifact_ArtifactValidator(Tracker_FormElementFactory::instance()));
    }

    /**
     * @throws Tracker_Exception
     * @throws Tracker_NoChangeException
     * @throws \Luracast\Restler\RestException
     */
    public function update(
        PFUser $user,
        Artifact $artifact,
        array $values,
        ?NewChangesetCommentRepresentation $comment = null,
    ): void {
        $this->checkArtifact($user, $artifact);
        $fields_data = $this->artifact_validator->getFieldsDataOnUpdate($values, $artifact);

        $comment_body   = '';
        $comment_format = Tracker_Artifact_Changeset_Comment::COMMONMARK_COMMENT;
        if ($comment) {
            $comment_body   = $comment->body;
            $comment_format = $comment->format;
        }

        $artifact->createNewChangeset($fields_data, $comment_body, $user, true, $comment_format);
    }

    private function checkArtifact(PFUser $user, Artifact $artifact)
    {
        if (! $artifact->userCanUpdate($user)) {
            throw new \Luracast\Restler\RestException(403, 'You have not the permission to update this card');
        }

        if ($this->clientWantsToUpdateLatestVersion() && ! $this->isUpdatingLatestVersion($artifact)) {
            throw new \Luracast\Restler\RestException(412, 'Artifact has been modified since you last requested it. Please edit the latest version');
        }
    }

    private function clientWantsToUpdateLatestVersion()
    {
        return (isset($_SERVER['HTTP_IF_UNMODIFIED_SINCE']) || isset($_SERVER['HTTP_IF_MATCH']));
    }

    private function isUpdatingLatestVersion(Artifact $artifact)
    {
        $valid_unmodified = true;
        $valid_match      = true;

        if (isset($_SERVER['HTTP_IF_UNMODIFIED_SINCE'])) {
            $client_version = strtotime($_SERVER['HTTP_IF_UNMODIFIED_SINCE']);
            $last_version   = $artifact->getLastUpdateDate();

            $valid_unmodified = ($last_version == $client_version);
        }

        if (isset($_SERVER['HTTP_IF_MATCH'])) {
            $client_version = $_SERVER['HTTP_IF_MATCH'];
            $last_version   = $artifact->getVersionIdentifier();

            $valid_match = ($last_version == $client_version);
        }

        return ($valid_unmodified && $valid_match);
    }
}
