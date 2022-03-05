/*
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

import "./tuleap-artifact-modal.tpl.html";
import TuleapArtifactModalController from "./tuleap-artifact-modal-controller.js";

import _ from "lodash";
import { isInCreationMode, setCreationMode } from "./modal-creation-mode-state.js";
import {
    getArtifactWithCompleteTrackerStructure,
    getTracker,
    getUserPreference,
} from "./rest/rest-service";
import { updateFileUploadRulesWhenNeeded } from "./fields/file-field/file-upload-rules-state.js";
import { getArtifactFieldValues } from "./artifact-edition-initializer.js";
import { buildFormTree } from "./model/form-tree-builder.js";
import { enforceWorkflowTransitions } from "./model/workflow-field-values-filter.js";
import { isValidTextFormat, TEXT_FORMAT_COMMONMARK } from "../../../constants/fields-constants.js";
import { setTextFieldDefaultFormat } from "./model/UserPreferencesStore";
import {
    getTargetFieldPossibleValues,
    setUpFieldDependenciesActions,
} from "./field-dependencies-helper.js";
import { getSelectedValues } from "./model/field-values-formatter.js";
import { addFieldValuesToTracker, transform } from "./model/tracker-transformer.js";
import { setTrackerFields } from "./model/FirstFileFieldStore";

export default ArtifactModalService;

ArtifactModalService.$inject = ["$q", "TlpModalService", "TuleapArtifactModalLoading"];

function ArtifactModalService($q, TlpModalService, TuleapArtifactModalLoading) {
    const self = this;
    Object.assign(self, {
        initCreationModalModel,
        initEditionModalModel,
        showCreation,
        showEdition,
        loading: TuleapArtifactModalLoading,
    });

    /**
     * Opens a new modal pop-in which will display a form with all the fields defined in the
     * given tracker.
     * displayItemCallback will be called after the last HTTP response is received.
     *
     * @param {int} tracker_id               The tracker to which the item we want to add/edit belongs
     * @param {int} parent_artifact_id       The artifact's parent's id
     * @param {function} displayItemCallback The function to call after receiving the last HTTP response. It will be called with the new artifact's id.
     * @param {array} prefill_values         The prefill values for creation, using field name as identifier
     * @param {boolean} is_list_picker_enabled  Enable the new list picker or not. Currently it is behind a feature flag. (To be removed when the feature flag will be removed)
     * @param {boolean} has_current_project_parents  Does the current project have parents? (see SAFe).
     * @param {boolean} is_links_field_v2_enabled  Enable the new artifact link field.
     */
    function showCreation(
        tracker_id,
        parent_artifact_id,
        displayItemCallback,
        is_list_picker_enabled = false,
        has_current_project_parents = false,
        is_links_field_v2_enabled = false,
        prefill_values
    ) {
        TuleapArtifactModalLoading.loading = true;

        return TlpModalService.open({
            templateUrl: "tuleap-artifact-modal.tpl.html",
            controller: TuleapArtifactModalController,
            controllerAs: "modal",
            tlpModalOptions: { keyboard: false, destroy_on_hide: true },
            resolve: {
                modal_model: self.initCreationModalModel(
                    tracker_id,
                    parent_artifact_id,
                    is_list_picker_enabled,
                    has_current_project_parents,
                    is_links_field_v2_enabled,
                    prefill_values
                ),
                displayItemCallback: displayItemCallback ? displayItemCallback : _.noop,
            },
        });
    }

    /**
     * Opens a new modal pop-in in edition mode, which will display a form with
     * all fields defined in the given tracker filled with the artifact's
     * existing values.
     * displayItemCallback will be called after the last HTTP response is received.
     *
     * @param {int} user_id                  The idea of current user
     * @param {int} tracker_id               The tracker to which the item we want to add/edit belongs
     * @param {int} artifact_id              The id of the artifact we want to edit
     * @param {function} displayItemCallback The function to call after receiving the last HTTP response. It will be called with the edited artifact's id.
     * @param {boolean} is_list_picker_enabled  Enable the new list picker or not. Currently it is behind a feature flag. (To be removed when the feature flag will be removed)
     * @param {boolean} has_current_project_parents  Does the current project have parents? (see SAFe).
     * @param {boolean} is_links_field_v2_enabled Enable the new artifact link field.
     */
    function showEdition(
        user_id,
        tracker_id,
        artifact_id,
        displayItemCallback,
        is_list_picker_enabled = false,
        has_current_project_parents = false,
        is_links_field_v2_enabled = false
    ) {
        TuleapArtifactModalLoading.loading = true;

        return TlpModalService.open({
            templateUrl: "tuleap-artifact-modal.tpl.html",
            controller: TuleapArtifactModalController,
            controllerAs: "modal",
            tlpModalOptions: { keyboard: false, destroy_on_hide: true },
            resolve: {
                modal_model: self.initEditionModalModel(
                    user_id,
                    tracker_id,
                    artifact_id,
                    is_list_picker_enabled,
                    has_current_project_parents,
                    is_links_field_v2_enabled
                ),
                displayItemCallback: displayItemCallback ? displayItemCallback : _.noop,
            },
        });
    }

    function initCreationModalModel(
        tracker_id,
        parent_artifact_id,
        is_list_picker_enabled,
        has_current_project_parents,
        is_links_field_v2_enabled,
        prefill_values
    ) {
        var modal_model = {};

        const creation_mode = true;
        setCreationMode(creation_mode);
        modal_model.tracker_id = tracker_id;
        modal_model.parent_artifact_id = parent_artifact_id;
        modal_model.is_list_picker_enabled = is_list_picker_enabled;
        modal_model.has_current_project_parents = has_current_project_parents;
        modal_model.is_links_field_v2_enabled = is_links_field_v2_enabled;

        var promise = $q
            .when(getTracker(tracker_id))
            .then(function (tracker) {
                const transformed_tracker = transform(tracker, creation_mode);
                modal_model.tracker = transformed_tracker;
                modal_model.color = transformed_tracker.color_name;
                modal_model.title = transformed_tracker.item_name;

                const initial_values = mapPrefillsToFieldValues(
                    prefill_values || [],
                    modal_model.tracker.fields
                );
                applyWorkflowTransitions(transformed_tracker, {});
                modal_model.values = getSelectedValues(initial_values, transformed_tracker);
                applyFieldDependencies(transformed_tracker, modal_model.values);
                modal_model.ordered_fields = buildFormTree(transformed_tracker);

                const file_upload_rules_promise = $q.when(
                    updateFileUploadRulesWhenNeeded(transformed_tracker.fields)
                );
                return file_upload_rules_promise;
            })
            .then(function () {
                initializeFileFieldStore(modal_model);
                return modal_model;
            });

        return promise;
    }

    function initEditionModalModel(
        user_id,
        tracker_id,
        artifact_id,
        is_list_picker_enabled,
        has_current_project_parents,
        is_links_field_v2_enabled
    ) {
        var modal_model = {};

        const creation_mode = false;

        modal_model.is_list_picker_enabled = is_list_picker_enabled;
        modal_model.has_current_project_parents = has_current_project_parents;
        modal_model.is_links_field_v2_enabled = is_links_field_v2_enabled;
        setCreationMode(creation_mode);
        modal_model.user_id = user_id;
        modal_model.tracker_id = tracker_id;
        modal_model.artifact_id = artifact_id;
        var transformed_tracker;

        var promise = $q
            .all([
                getArtifactWithCompleteTrackerStructure(artifact_id),
                getFollowupsCommentsOrderUserPreference(user_id, tracker_id, modal_model),
                getTextFieldsFormatUserPreference(user_id, modal_model),
                getRelativeDatesDisplayUserPreference(user_id, modal_model),
            ])
            .then(function (promises) {
                const tracker = promises[0].tracker;
                transformed_tracker = transform(tracker, creation_mode);

                modal_model.ordered_fields = transformed_tracker.ordered_fields;
                modal_model.color = transformed_tracker.color_name;

                const artifact_values = getArtifactFieldValues(promises[0]);
                let tracker_with_field_values = addFieldValuesToTracker(
                    artifact_values,
                    transformed_tracker
                );

                applyWorkflowTransitions(tracker_with_field_values, artifact_values);
                modal_model.values = getSelectedValues(artifact_values, transformed_tracker);
                modal_model.title = artifact_values.title;
                modal_model.etag = promises[0].Etag;
                modal_model.last_modified = promises[0]["Last-Modified"];

                applyFieldDependencies(tracker_with_field_values, modal_model.values);

                modal_model.tracker = tracker_with_field_values;
                modal_model.ordered_fields = buildFormTree(tracker_with_field_values);

                const file_upload_rules_promise = $q.when(
                    updateFileUploadRulesWhenNeeded(transformed_tracker.fields)
                );
                return file_upload_rules_promise;
            })
            .then(function () {
                initializeFileFieldStore(modal_model);
                return modal_model;
            });

        return promise;
    }

    function initializeFileFieldStore(modal_model) {
        setTrackerFields(modal_model.tracker.fields);
    }

    function getFollowupsCommentsOrderUserPreference(user_id, tracker_id, modal_model) {
        var preference_key = "tracker_comment_invertorder_" + tracker_id;

        return $q.when(getUserPreference(user_id, preference_key)).then(function (data) {
            modal_model.invert_followups_comments_order = Boolean(data.value);
        });
    }

    function getTextFieldsFormatUserPreference(user_id, modal_model) {
        return $q
            .when(getUserPreference(user_id, "user_edition_default_format"))
            .then(function (data) {
                const format = isValidTextFormat(data.value) ? data.value : TEXT_FORMAT_COMMONMARK;
                modal_model.text_fields_format = format;
                setTextFieldDefaultFormat(format);
            });
    }

    function getRelativeDatesDisplayUserPreference(user_id, modal_model) {
        return $q.when(getUserPreference(user_id, "relative_dates_display")).then(function (data) {
            modal_model.relative_dates_display =
                data.value !== false ? data.value : "relative_first-absolute_tooltip";
        });
    }

    function applyWorkflowTransitions(tracker, field_values) {
        if (!hasWorkflowTransitions(tracker)) {
            return;
        }
        var workflow = getWorkflow(tracker);

        const workflow_field = tracker.fields.find((field) => field.field_id === workflow.field_id);
        if (!workflow_field) {
            return;
        }

        var source_value_id = null;
        if (!isInCreationMode() && typeof field_values[workflow.field_id] !== "undefined") {
            source_value_id = field_values[workflow.field_id].bind_value_ids[0];
        }
        enforceWorkflowTransitions(source_value_id, workflow_field, workflow);
    }

    function hasWorkflowTransitions(tracker) {
        return (
            _.has(tracker, "workflow") &&
            _.has(tracker.workflow, "transitions") &&
            tracker.workflow.is_used === "1" &&
            tracker.workflow.field_id
        );
    }

    function getWorkflow(tracker) {
        return tracker.workflow;
    }

    function applyFieldDependencies(tracker, field_values) {
        var filterTargetFieldValues = function (
            source_field_id,
            target_field,
            field_dependencies_rules
        ) {
            var source_value_ids = [].concat(field_values[source_field_id].bind_value_ids);

            target_field.filtered_values = getTargetFieldPossibleValues(
                source_value_ids,
                target_field,
                field_dependencies_rules
            );
        };

        setUpFieldDependenciesActions(tracker, filterTargetFieldValues);
    }

    function mapPrefillsToFieldValues(prefill_values, tracker_fields) {
        var field_values = {};

        prefill_values.forEach(function (prefill) {
            const field = tracker_fields.find((field) => field.name === prefill.name);
            if (field) {
                field_values[field.field_id] = Object.assign({}, prefill, {
                    field_id: field.field_id,
                });
            }
        });

        return field_values;
    }
}
