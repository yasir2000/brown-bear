<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Taskboard\REST\v1\Cell;

use Cardwall_Column;
use Cardwall_OnTop_ColumnDao;
use Cardwall_OnTop_Config_ColumnFactory;
use Luracast\Restler\RestException;
use PFUser;
use Tracker;
use Tracker_Exception;
use Tracker_FormElement_Field_Selectbox;
use Tracker_FormElement_InvalidFieldException;
use Tracker_FormElement_InvalidFieldValueException;
use Tracker_FormElementFactory;
use Tracker_NoChangeException;
use Tracker_REST_Artifact_ArtifactUpdater;
use TrackerFactory;
use Tuleap\REST\I18NRestException;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\MappedFieldRetriever;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\MappedValuesRetriever;
use Tuleap\Taskboard\Column\InvalidColumnException;
use Tuleap\Taskboard\Column\MilestoneTrackerRetriever;
use Tuleap\Taskboard\Tracker\TaskboardTracker;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation;
use Tuleap\Tracker\Rule\FirstValidValueAccordingToDependenciesRetriever;
use Tuleap\Tracker\Workflow\FirstPossibleValueInListRetriever;
use Tuleap\Tracker\Workflow\NoPossibleValueException;

class CardMappedFieldUpdater
{
    public function __construct(
        private Cardwall_OnTop_Config_ColumnFactory $column_factory,
        private MilestoneTrackerRetriever $milestone_tracker_retriever,
        private AddValidator $add_validator,
        private Tracker_REST_Artifact_ArtifactUpdater $artifact_updater,
        private MappedFieldRetriever $mapped_field_retriever,
        private MappedValuesRetriever $mapped_values_retriever,
        private FirstPossibleValueInListRetriever $first_possible_value_retriever,
    ) {
    }

    public static function build(): self
    {
        $column_dao = new Cardwall_OnTop_ColumnDao();
        return new self(
            new Cardwall_OnTop_Config_ColumnFactory($column_dao),
            new MilestoneTrackerRetriever($column_dao, TrackerFactory::instance()),
            new AddValidator(),
            Tracker_REST_Artifact_ArtifactUpdater::build(),
            MappedFieldRetriever::build(),
            MappedValuesRetriever::build(),
            new FirstPossibleValueInListRetriever(
                new FirstValidValueAccordingToDependenciesRetriever(
                    Tracker_FormElementFactory::instance()
                )
            )
        );
    }

    /**
     * @throws I18NRestException
     * @throws RestException
     */
    public function updateCardMappedField(
        Artifact $swimlane_artifact,
        int $column_id,
        Artifact $artifact_to_add,
        PFUser $current_user,
    ) {
        $column            = $this->getColumn($column_id);
        $milestone_tracker = $this->getMilestoneTracker($column);
        $this->add_validator->validateArtifacts($swimlane_artifact, $artifact_to_add, $current_user);

        $values = $this->buildUpdateValues(
            $artifact_to_add,
            new TaskboardTracker($milestone_tracker, $artifact_to_add->getTracker()),
            $column,
            $current_user
        );
        try {
            $this->artifact_updater->update($current_user, $artifact_to_add, $values);
        } catch (Tracker_FormElement_InvalidFieldException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_FormElement_InvalidFieldValueException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_NoChangeException $exception) {
            // Do nothing
        } catch (Tracker_Exception $exception) {
            if ($GLOBALS['Response']->feedbackHasErrors()) {
                throw new RestException(500, $GLOBALS['Response']->getRawFeedback());
            }
            throw new RestException(500, $exception->getMessage());
        }
    }

    /**
     * @throws RestException
     */
    private function getColumn(int $id): Cardwall_Column
    {
        $column = $this->column_factory->getColumnById($id);
        if ($column === null) {
            throw new RestException(404);
        }
        return $column;
    }

    /**
     * @return ArtifactValuesRepresentation[]
     * @throws I18NRestException
     */
    private function buildUpdateValues(
        Artifact $artifact_to_add,
        TaskboardTracker $taskboard_tracker,
        Cardwall_Column $column,
        PFUser $current_user,
    ): array {
        $representation                 = new ArtifactValuesRepresentation();
        $mapped_field                   = $this->getMappedField($taskboard_tracker, $column, $current_user);
        $representation->field_id       = (int) $mapped_field->getId();
        $first_mapped_value             = $this->getFirstMappedValue(
            $mapped_field,
            $artifact_to_add,
            $taskboard_tracker,
            $column
        );
        $representation->bind_value_ids = [$first_mapped_value];

        return [$representation];
    }

    /**
     * @throws I18NRestException
     */
    private function getMappedField(
        TaskboardTracker $taskboard_tracker,
        Cardwall_Column $column,
        PFUser $current_user,
    ): Tracker_FormElement_Field_Selectbox {
        $mapped_field = $this->mapped_field_retriever->getField($taskboard_tracker);
        if ($mapped_field === null) {
            throw new I18NRestException(
                400,
                sprintf(
                    dgettext(
                        'tuleap-taskboard',
                        "Tracker %s has no list field mapped to column %s, please check its configuration."
                    ),
                    $taskboard_tracker->getTracker()->getName(),
                    $column->getLabel()
                )
            );
        }
        if (! $mapped_field->userCanUpdate($current_user)) {
            throw new I18NRestException(
                403,
                sprintf(
                    dgettext('tuleap-taskboard', "You don't have permission to update the %s field."),
                    $mapped_field->getLabel()
                )
            );
        }

        return $mapped_field;
    }

    /**
     * @throws I18NRestException
     */
    private function getFirstMappedValue(
        Tracker_FormElement_Field_Selectbox $mapped_field,
        Artifact $artifact_to_add,
        TaskboardTracker $taskboard_tracker,
        Cardwall_Column $column,
    ): int {
        $mapped_values = $this->mapped_values_retriever->getValuesMappedToColumn(
            $taskboard_tracker,
            $column
        );

        if ($mapped_values->isEmpty()) {
            throw new I18NRestException(
                400,
                sprintf(
                    dgettext(
                        'tuleap-taskboard',
                        "Tracker %s has no value mapped to column %s, please check its configuration."
                    ),
                    $taskboard_tracker->getTracker()->getName(),
                    $column->getLabel()
                )
            );
        }

        try {
            return $this->first_possible_value_retriever->getFirstPossibleValue(
                $artifact_to_add,
                $mapped_field,
                $mapped_values
            );
        } catch (NoPossibleValueException $exception) {
            throw new I18NRestException(
                400,
                $exception->getMessage()
            );
        }
    }

    /**
     * @throws RestException
     */
    private function getMilestoneTracker(Cardwall_Column $column): Tracker
    {
        try {
            $milestone_tracker = $this->milestone_tracker_retriever->getMilestoneTrackerOfColumn($column);
        } catch (InvalidColumnException $e) {
            throw new RestException(404);
        }
        return $milestone_tracker;
    }
}
