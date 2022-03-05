<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\TestManagement\REST\v1;

use PFUser;
use Tracker_REST_Artifact_ArtifactUpdater;
use Tuleap\TestManagement\Campaign\Campaign;
use Tuleap\TestManagement\Campaign\CampaignSaver;
use Tuleap\TestManagement\LabelFieldNotFoundException;
use Tuleap\Tracker\Semantic\Status\SemanticStatusNotDefinedException;
use Tuleap\Tracker\Semantic\Status\SemanticStatusClosedValueNotFoundException;

/**
 * @psalm-import-type StatusAcceptableValue from CampaignArtifactUpdateFieldValuesBuilder
 */
class CampaignUpdater
{
    /** @var Tracker_REST_Artifact_ArtifactUpdater */
    private $artifact_updater;
    /** @var CampaignSaver */
    private $campaign_saver;
    /**
     * @var CampaignArtifactUpdateFieldValuesBuilder
     */
    private $field_values_builder;

    public function __construct(
        Tracker_REST_Artifact_ArtifactUpdater $artifact_updater,
        CampaignSaver $campaign_saver,
        CampaignArtifactUpdateFieldValuesBuilder $field_values_builder,
    ) {
        $this->artifact_updater     = $artifact_updater;
        $this->campaign_saver       = $campaign_saver;
        $this->field_values_builder = $field_values_builder;
    }

    /**
     * @psalm-param StatusAcceptableValue $change_status
     * @throws LabelFieldNotFoundException
     * @throws SemanticStatusNotDefinedException
     * @throws SemanticStatusClosedValueNotFoundException
     * @throws \Luracast\Restler\RestException
     * @throws \Tracker_AfterSaveException
     * @throws \Tracker_ChangesetCommitException
     * @throws \Tracker_ChangesetNotCreatedException
     * @throws \Tracker_CommentNotStoredException
     * @throws \Tracker_Exception
     * @throws \Tracker_FormElement_InvalidFieldException
     * @throws \Tracker_FormElement_InvalidFieldValueException
     */
    public function updateCampaign(
        PFUser $user,
        Campaign $campaign,
        ?string $change_status,
    ): void {
        $this->campaign_saver->save($campaign);

        $artifact = $campaign->getArtifact();
        $tracker  = $artifact->getTracker();
        $values   = $this->field_values_builder->getFieldValuesForCampaignArtifactUpdate(
            $tracker,
            $user,
            $campaign->getLabel(),
            $change_status
        );

        $this->artifact_updater->update($user, $artifact, $values);
    }
}
