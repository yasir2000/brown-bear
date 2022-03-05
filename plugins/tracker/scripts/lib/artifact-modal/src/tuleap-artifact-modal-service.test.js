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

import artifact_modal_module from "./tuleap-artifact-modal.js";
import angular from "angular";
import "angular-mocks";

import * as modal_creation_mode_state from "./modal-creation-mode-state.js";
import * as rest_service from "./rest/rest-service";
import * as form_tree_builder from "./model/form-tree-builder.js";
import * as workflow_field_values_filter from "./model/workflow-field-values-filter.js";
import * as file_upload_rules_state from "./fields/file-field/file-upload-rules-state.js";
import { createAngularPromiseWrapper } from "../../../../../../tests/jest/angular-promise-wrapper.js";
import * as field_values_formatter from "./model/field-values-formatter.js";
import * as tracker_transformer from "./model/tracker-transformer.js";

describe("NewTuleapArtifactModalService", () => {
    let NewTuleapArtifactModalService,
        $q,
        buildFormTree,
        enforceWorkflowTransitions,
        setCreationMode,
        isInCreationMode,
        getTracker,
        getUserPreference,
        getArtifactWithCompleteTrackerStructure,
        updateFileUploadRulesWhenNeeded,
        tracker,
        wrapPromise,
        getSelectedValues;

    beforeEach(() => {
        angular.mock.module(artifact_modal_module);

        let $rootScope;
        angular.mock.inject((_$rootScope_, _$q_, _NewTuleapArtifactModalService_) => {
            $rootScope = _$rootScope_;
            $q = _$q_;
            NewTuleapArtifactModalService = _NewTuleapArtifactModalService_;
        });

        jest.spyOn(tracker_transformer, "addFieldValuesToTracker").mockImplementation(
            (artifact_values, tracker) => {
                return tracker;
            }
        );
        jest.spyOn(tracker_transformer, "transform").mockImplementation((tracker) => {
            return tracker;
        });
        setCreationMode = jest.spyOn(modal_creation_mode_state, "setCreationMode");
        isInCreationMode = jest.spyOn(modal_creation_mode_state, "isInCreationMode");
        getTracker = jest.spyOn(rest_service, "getTracker");
        getUserPreference = jest.spyOn(rest_service, "getUserPreference");
        getArtifactWithCompleteTrackerStructure = jest.spyOn(
            rest_service,
            "getArtifactWithCompleteTrackerStructure"
        );
        updateFileUploadRulesWhenNeeded = jest.spyOn(
            file_upload_rules_state,
            "updateFileUploadRulesWhenNeeded"
        );
        buildFormTree = jest.spyOn(form_tree_builder, "buildFormTree").mockReturnValue({});
        enforceWorkflowTransitions = jest.spyOn(
            workflow_field_values_filter,
            "enforceWorkflowTransitions"
        );
        getSelectedValues = jest
            .spyOn(field_values_formatter, "getSelectedValues")
            .mockReturnValue({});

        wrapPromise = createAngularPromiseWrapper($rootScope);
    });

    describe("initCreationModalModel() -", () => {
        let tracker_id, parent_artifact_id;

        beforeEach(() => {
            tracker_id = 28;
            parent_artifact_id = 581;
        });

        it("Given a tracker id and a parent artifact id, then the tracker's structure will be retrieved and a promise will be resolved with the modal's model object", async () => {
            tracker = {
                id: tracker_id,
                color_name: "importer",
                item_name: "preinvest",
                parent: null,
                fields: [],
            };
            getTracker.mockReturnValue($q.when(tracker));
            updateFileUploadRulesWhenNeeded.mockReturnValue($q.when());

            const promise = NewTuleapArtifactModalService.initCreationModalModel(
                tracker_id,
                parent_artifact_id,
                false,
                false,
                false
            );

            await expect(wrapPromise(promise)).resolves.toBeDefined();
            expect(getTracker).toHaveBeenCalledWith(tracker_id);
            expect(updateFileUploadRulesWhenNeeded).toHaveBeenCalled();
            expect(getSelectedValues).toHaveBeenCalledWith({}, tracker);
            expect(tracker_transformer.transform).toHaveBeenCalledWith(tracker, true);
            expect(buildFormTree).toHaveBeenCalledWith(tracker);
            const model = promise.$$state.value;
            expect(setCreationMode).toHaveBeenCalledWith(true);
            expect(model.tracker_id).toEqual(tracker_id);
            expect(model.parent_artifact_id).toEqual(parent_artifact_id);
            expect(model.tracker).toEqual(tracker);
            expect(model.title).toEqual("preinvest");
            expect(model.color).toEqual("importer");
            expect(model.values).toBeDefined();
            expect(model.ordered_fields).toBeDefined();
            expect(model.parent_artifacts).toBeUndefined();
            expect(model.artifact_id).toBeUndefined();
        });

        it("Given that I could not get the tracker structure, then a promise will be rejected", async () => {
            getTracker.mockReturnValue($q.reject());

            const promise = wrapPromise(
                NewTuleapArtifactModalService.initCreationModalModel(tracker_id, parent_artifact_id)
            );

            let has_thrown = false;
            try {
                await promise;
            } catch (e) {
                has_thrown = true;
            }
            expect(has_thrown).toBe(true);
        });

        describe("apply transitions -", () => {
            beforeEach(() => {
                isInCreationMode.mockReturnValue(true);
            });

            it("Given a tracker that had workflow transitions, when I create the modal's creation model, then the transitions will be enforced", async () => {
                const workflow_field = {
                    field_id: 189,
                    values: [],
                };
                const workflow = {
                    is_used: "1",
                    field_id: 189,
                    transitions: [
                        {
                            from_id: null,
                            to_id: 511,
                        },
                    ],
                };
                tracker = {
                    id: tracker_id,
                    fields: [workflow_field],
                    workflow,
                };
                getTracker.mockReturnValue($q.when(tracker));
                updateFileUploadRulesWhenNeeded.mockReturnValue($q.when());

                const promise = wrapPromise(
                    NewTuleapArtifactModalService.initCreationModalModel(tracker_id)
                );

                await expect(promise).resolves.toBeDefined();
                expect(enforceWorkflowTransitions).toHaveBeenCalledWith(
                    null,
                    workflow_field,
                    workflow
                );
            });

            it("Given a tracker that had workflow transitions but were not used, then the transitions won't be enforced", async () => {
                const workflow_field = {
                    field_id: tracker_id,
                };
                const workflow = {
                    is_used: "0",
                    field_id: 189,
                    transitions: [
                        {
                            from_id: 326,
                            to_id: 723,
                        },
                    ],
                };
                tracker = {
                    id: tracker_id,
                    fields: [workflow_field],
                    workflow,
                };
                getTracker.mockReturnValue($q.when(tracker));
                updateFileUploadRulesWhenNeeded.mockReturnValue($q.when());

                const promise = wrapPromise(
                    NewTuleapArtifactModalService.initCreationModalModel(tracker_id)
                );

                await expect(promise).resolves.toBeDefined();
                expect(enforceWorkflowTransitions).not.toHaveBeenCalled();
            });

            it("Given a tracker that didn't have workflow transitions, when I create the modal's creation model, then the transitions won't be enforced", async () => {
                const workflow_field = {
                    field_id: tracker_id,
                };
                const workflow = {
                    is_used: "1",
                    field_id: 189,
                    transitions: [],
                };
                tracker = {
                    id: tracker_id,
                    fields: [workflow_field],
                    workflow,
                };
                getTracker.mockReturnValue($q.when(tracker));
                updateFileUploadRulesWhenNeeded.mockReturnValue($q.when());

                const promise = wrapPromise(
                    NewTuleapArtifactModalService.initCreationModalModel(tracker_id)
                );

                await expect(promise).resolves.toBeDefined();
                expect(enforceWorkflowTransitions).not.toHaveBeenCalled();
            });
        });
    });

    describe("initEditionModalModel() -", () => {
        let user_id, tracker_id, artifact_id;

        beforeEach(() => {
            getSelectedValues.mockReturnValue({
                113: {
                    value: "onomatomania",
                },
            });

            const comment_order_preference = {
                key: "tracker_comment_invertorder_93",
                value: "1",
            };

            const text_format_preference = {
                key: "user_edition_default_format",
                value: "html",
            };

            user_id = 102;
            tracker_id = 93;
            artifact_id = 250;
            getUserPreference.mockImplementation((user_id, preference_key) => {
                if (preference_key.includes("tracker_comment_invertorder_")) {
                    return $q.when(comment_order_preference);
                } else if (preference_key === "user_edition_default_format") {
                    return $q.when(text_format_preference);
                } else if (preference_key === "relative_dates_display") {
                    return $q.when({
                        key: "relative_dates_display",
                        value: false,
                    });
                }
            });
            updateFileUploadRulesWhenNeeded.mockReturnValue($q.when());
        });

        describe("Create modal edition model", () => {
            let artifact;
            beforeEach(() => {
                tracker = {
                    id: tracker_id,
                    color_name: "slackerism",
                    label: "unstainableness",
                    parent: null,
                    fields: [],
                };
                artifact = {
                    title: "onomatomania",
                    tracker,
                    values: [
                        {
                            field_id: 487,
                            value: "unwadded",
                        },
                    ],
                    Etag: "etag",
                    "Last-Modified": 1629097552,
                };
                getArtifactWithCompleteTrackerStructure.mockReturnValue($q.when(artifact));
            });

            it(`Given a user id, tracker id and an artifact id,
                When I create the modal's edition model,
                Then the artifact's field values will be retrieved,
                    the tracker's structure will be retrieved
                    and a promise will be resolved with the modal's model object`, async () => {
                const promise = NewTuleapArtifactModalService.initEditionModalModel(
                    user_id,
                    tracker_id,
                    artifact_id,
                    false,
                    false,
                    false
                );

                await expect(wrapPromise(promise)).resolves.toBeDefined();
                expect(getArtifactWithCompleteTrackerStructure).toHaveBeenCalledWith(artifact_id);
                expect(getUserPreference).toHaveBeenCalledWith(
                    user_id,
                    "tracker_comment_invertorder_93"
                );
                expect(getUserPreference).toHaveBeenCalledWith(user_id, "relative_dates_display");
                expect(getUserPreference).toHaveBeenCalledWith(
                    user_id,
                    "user_edition_default_format"
                );

                expect(updateFileUploadRulesWhenNeeded).toHaveBeenCalled();
                expect(getSelectedValues).toHaveBeenCalledWith(expect.any(Object), tracker);
                expect(tracker_transformer.transform).toHaveBeenCalledWith(tracker, false);
                expect(tracker_transformer.addFieldValuesToTracker).toHaveBeenCalledWith(
                    expect.any(Object),
                    tracker
                );
                expect(buildFormTree).toHaveBeenCalledWith(tracker);
                var model = promise.$$state.value;
                expect(model.invert_followups_comments_order).toBeTruthy();
                expect(model.text_fields_format).toEqual("html");
                expect(model.tracker_id).toEqual(tracker_id);
                expect(model.artifact_id).toEqual(artifact_id);
                expect(model.color).toEqual("slackerism");
                expect(model.tracker).toEqual(tracker);
                expect(model.values).toBeDefined();
                expect(model.ordered_fields).toBeDefined();
                expect(setCreationMode).toHaveBeenCalledWith(false);
                expect(model.title).toEqual("onomatomania");
                expect(model.etag).toEqual("etag");
                expect(model.last_modified).toEqual(1629097552);
            });

            it(`Given that the user didn't have a preference set for text fields format,
                when I create the modal's edition model,
                then the default text_field format will be "commonmark" by default`, async () => {
                const comment_order_preference = {
                    key: "tracker_comment_invertorder_93",
                    value: "1",
                };

                getUserPreference.mockImplementation((user_id, preference_key) => {
                    if (preference_key.includes("tracker_comment_invertorder_")) {
                        return $q.when(comment_order_preference);
                    } else if (preference_key === "user_edition_default_format") {
                        return $q.when({
                            key: "user_edition_default_format",
                            value: false,
                        });
                    } else if (preference_key === "relative_dates_display") {
                        return $q.when({
                            key: "relative_dates_display",
                            value: false,
                        });
                    }
                });

                const promise = NewTuleapArtifactModalService.initEditionModalModel(
                    user_id,
                    tracker_id,
                    artifact_id
                );

                await expect(wrapPromise(promise)).resolves.toBeDefined();
                const model = promise.$$state.value;

                expect(model.text_fields_format).toEqual("commonmark");
            });
        });

        describe("apply transitions -", () => {
            let workflow_field, artifact;

            beforeEach(() => {
                workflow_field = {
                    field_id: 189,
                    values: [],
                };
            });

            it("Given a tracker that had workflow transitions, when I create the modal's edition model, then the transitions will be enforced", async () => {
                var workflow = {
                    is_used: "1",
                    field_id: 189,
                    transitions: [
                        {
                            from_id: 757,
                            to_id: 511,
                        },
                    ],
                };
                tracker = {
                    id: tracker_id,
                    fields: [workflow_field],
                    workflow: workflow,
                };
                artifact = {
                    title: "onomatomania",
                    tracker,
                    values: [
                        {
                            field_id: 189,
                            bind_value_ids: [757],
                        },
                    ],
                };
                getArtifactWithCompleteTrackerStructure.mockReturnValue($q.when(artifact));

                var promise = wrapPromise(
                    NewTuleapArtifactModalService.initEditionModalModel(
                        user_id,
                        tracker_id,
                        artifact_id
                    )
                );

                await expect(promise).resolves.toBeDefined();
                expect(enforceWorkflowTransitions).toHaveBeenCalledWith(
                    757,
                    workflow_field,
                    workflow
                );
            });

            it("Given a tracker that had workflow transitions but were not used, when I create the modal's edition model, then the transitions won't be enforced", async () => {
                var workflow = {
                    is_used: "0",
                    field_id: 189,
                    transitions: [
                        {
                            from_id: 757,
                            to_id: 511,
                        },
                    ],
                };
                tracker = {
                    id: tracker_id,
                    fields: [workflow_field],
                    workflow: workflow,
                };
                artifact = {
                    title: "onomatomania",
                    tracker,
                    values: [
                        {
                            field_id: 487,
                            value: "unwadded",
                        },
                    ],
                };

                getArtifactWithCompleteTrackerStructure.mockReturnValue($q.when(artifact));

                var promise = wrapPromise(
                    NewTuleapArtifactModalService.initEditionModalModel(
                        user_id,
                        tracker_id,
                        artifact_id
                    )
                );

                await expect(promise).resolves.toBeDefined();
                expect(enforceWorkflowTransitions).not.toHaveBeenCalled();
            });

            it("Given a tracker that had workflow transitions on a field with missing values, when I create the modal's edition model, it does not crash and enforce the transition like in the creation", async () => {
                const workflow = {
                    is_used: "1",
                    field_id: 189,
                    transitions: [
                        {
                            from_id: 757,
                            to_id: 511,
                        },
                    ],
                };
                tracker = {
                    id: tracker_id,
                    fields: [workflow_field],
                    workflow: workflow,
                };
                artifact = {
                    title: "onomatomania",
                    tracker,
                    values: [],
                };
                getArtifactWithCompleteTrackerStructure.mockReturnValue($q.when(artifact));

                const promise = wrapPromise(
                    NewTuleapArtifactModalService.initEditionModalModel(
                        user_id,
                        tracker_id,
                        artifact_id
                    )
                );

                await expect(promise).resolves.toBeDefined();
                expect(enforceWorkflowTransitions).toHaveBeenCalledWith(
                    null,
                    workflow_field,
                    workflow
                );
            });
        });
    });
});
