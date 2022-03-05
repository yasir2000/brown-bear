/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

import { PROJECT_ADMINISTRATORS_ID } from "../../../../../../../src/scripts/user/user-group-constants";
import { POST_ACTION_TYPE } from "../../../../../scripts/workflow-transitions/src/constants/workflow-constants";

function getTrackerIdFromTrackerListPage(): Cypress.Chainable<JQuery<HTMLElement>> {
    cy.visitProjectService("tracker-project", "Trackers");
    return cy.get("[data-test=tracker-link-workflow]").should("have.attr", "data-test-tracker-id");
}

describe(`Tracker Workflow`, () => {
    const STATUS_FIELD_LABEL = "Status";
    const REMAINING_EFFORT_FIELD_LABEL = "Remaining Effort";
    const INITIAL_EFFORT_FIELD_LABEL = "Initial Effort";

    before(function () {
        cy.clearSessionCookie();
        cy.projectAdministratorLogin();
        cy.getProjectId("tracker-project").as("project_id");
        getTrackerIdFromTrackerListPage()
            .as("workflow_tracker_id")
            .then((workflow_tracker_id: Cypress.ObjectLike) => {
                cy.visit(`/plugins/tracker/workflow/${workflow_tracker_id}/transitions`);
            });
    });

    beforeEach(function () {
        cy.preserveSessionCookies();
    });

    it(`has an empty state`, function () {
        cy.get("[data-test=tracker-workflow-first-configuration]");
    });

    context("Simple mode", () => {
        it(`can create and configure a workflow`, function () {
            /* Create the workflow */
            cy.get("[data-test=tracker-workflow-first-configuration]").within(() => {
                cy.get("[data-test=list-fields]").select(STATUS_FIELD_LABEL);
                cy.get("[data-test=create-workflow]").click();
            });

            /* Add transitions */
            cy.get("[data-test=tracker-workflow-matrix]").within(() => {
                cy.get("[data-test=matrix-row]")
                    .contains("On Going")
                    .parent("[data-test=matrix-row]")
                    .within(() => {
                        cy.get("[data-test-action=create-transition]").each(($button) => {
                            cy.wrap($button).click();
                        });
                        // Making sure the transition has been created by checking if we can delete it before continuing the test
                        cy.get("[data-test-action=confirm-delete-transition]");
                    });

                cy.get("[data-test=matrix-row]")
                    .contains("(New artifact)")
                    .parent("[data-test=matrix-row]")
                    .within(() => {
                        cy.get("[data-test-action=create-transition]").first().click();
                    });
                cy.get("[data-test=configure-state]").first().click();
            });
            /* Configure a state */
            cy.get("[data-test=transition-modal]").within(() => {
                const project_administrators_ugroup_id =
                    this.project_id + "_" + PROJECT_ADMINISTRATORS_ID;
                cy.get("[data-test=authorized-ugroups-select]").select(
                    project_administrators_ugroup_id,
                    { force: true }
                );
                cy.get("[data-test=not-empty-field-form-element]").within(() => {
                    cy.get("[data-test=list-picker-search-field]").type(
                        REMAINING_EFFORT_FIELD_LABEL + "{enter}"
                    );
                });
                cy.get("[data-test=not-empty-comment-checkbox]").check();
                cy.get("[data-test=add-post-action]").click();
                cy.get("[data-test=post-action-type-select]").select(
                    POST_ACTION_TYPE.FROZEN_FIELDS
                );
                cy.get("[data-test=frozen-fields-form-element]").within(() => {
                    cy.get("[data-test=list-picker-search-field]").type(
                        INITIAL_EFFORT_FIELD_LABEL + "{enter}"
                    );
                });
                cy.get("[data-test=save-button]").click();
            });
            /* Delete a transition */
            cy.get("[data-test=tracker-workflow-matrix]").within(() => {
                cy.get("[data-test=matrix-row]")
                    .contains("(New artifact)")
                    .parent("[data-test=matrix-row]")
                    .within(() => {
                        cy.get("[data-test-action=delete-transition]").first().click();
                        // Making sure the transition deletion is visible in the UI (aka there is no more a delete button) before continuing
                        cy.get("[data-test-action=confirm-delete-transition]").should("not.exist");
                    });
            });
            /* Delete the entire workflow */
            cy.get("[data-test=change-or-remove-button]").click();
            cy.get("[data-test=change-field-confirmation-modal]").within(() => {
                cy.get("[data-test=confirm-button]").click();
            });
        });
    });
});
