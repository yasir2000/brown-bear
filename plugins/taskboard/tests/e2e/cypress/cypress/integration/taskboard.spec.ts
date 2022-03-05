/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

import type { Card, ColumnDefinition } from "../../../../../scripts/taskboard/src/type";

describe(`Taskboard`, function () {
    before(function () {
        cy.clearSessionCookie();
        cy.projectMemberLogin();
        cy.getProjectId("taskboard-project")
            .then((project_id: number) =>
                cy.getFromTuleapAPI(`/api/projects/${project_id}/milestones?fields=slim`)
            )
            .then((response) => response.body[0].id)
            .as("release_id")
            .then(function () {
                cy.visit(`/taskboard/taskboard-project/${this.release_id}`);
            });
    });

    beforeEach(function () {
        cy.preserveSessionCookies();
    });

    it(`loads`, function () {
        cy.get("[data-test=taskboard-body]");
    });

    context(`Cell functionalities`, function () {
        before(function () {
            cy.getFromTuleapAPI(`/api/taskboard/${this.release_id}/columns`)
                .then((response) => response.body)
                .as("taskboard_columns");
            cy.getFromTuleapAPI(`/api/taskboard/${this.release_id}/cards?limit=100`)
                .then((response) => response.body)
                .as("taskboard_swimlanes");
        });

        it(`adds a card in a swimlane`, function () {
            // Wait for the swimlane to be loaded and displayed
            cy.get("[data-test=card-with-remaining-effort]").contains("Quality Sunshine");
            // Wait for children to be loaded and displayed
            cy.get("[data-test=child-card]").contains("Golden Wrench");

            const on_going_column = this.taskboard_columns.find(
                (column: ColumnDefinition) => column.label === "On Going"
            );
            const quality_sunshine_swimlane = this.taskboard_swimlanes.find(
                (swimlane: Card) => swimlane.label === "Quality Sunshine"
            );
            cy.get(
                `[data-column-id=${on_going_column.id}][data-swimlane-id=${quality_sunshine_swimlane.id}]`
            ).within(() => {
                cy.get("[data-test=add-in-place-form]")
                    .should("be.hidden")
                    .invoke("css", "opacity", "100")
                    .within(() => {
                        cy.get("[data-test=add-in-place-button]").click();
                        cy.get("[data-test=label-editor]").type("Discarded Epsilon{enter}");
                    });

                cy.get("[data-card-id]").contains("Discarded Epsilon");
            });
        });

        it(`edits the title of a card`, function () {
            cy.get("[data-card-id]")
                .contains("Lonesome Galaxy")
                .parents("[data-card-id]")
                .as("card")
                .within(() => {
                    cy.get("[data-test=card-edit-button]").click();
                    cy.get("[data-test=label-editor]").then(($label_editor) => {
                        expect($label_editor.val()).to.equal("Lonesome Galaxy");
                        cy.wrap($label_editor).clear().type("Deserted Torpedo{enter}");
                    });
                });
            cy.get("[data-card-id]").contains("Deserted Torpedo");
            // Edit back the name for re-playability
            cy.get("@card").within(() => {
                cy.get("[data-test=card-edit-button]").click();
                cy.get("[data-test=label-editor]").clear().type("Lonesome Galaxy{enter}");
            });
        });

        it(`hides the swimlanes and cards that are "Done"`, function () {
            // need to force click because buttons are out of viewport
            cy.get("[data-test=hide-closed-items]").click({ force: true });
            cy.get("[data-card-id]").then(($body) => {
                expect($body).not.to.contain("Elastic Notorious");
                expect($body).not.to.contain("Grim Crayon");
                expect($body).not.to.contain("Severe Storm");
            });
        });

        it(`show the swimlanes and cards that are "Done"`, function () {
            // need to force click because buttons are out of viewport
            cy.get("[data-test=show-closed-items]").click({ force: true });
            cy.get("[data-card-id]").then(($body) => {
                expect($body).to.contain("Elastic Notorious");
                expect($body).to.contain("Grim Crayon");
                expect($body).to.contain("Severe Storm");
            });
        });
    });
});
