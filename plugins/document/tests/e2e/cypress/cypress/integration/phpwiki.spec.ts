/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import { disableSpecificErrorThrownByCkeditor } from "../support/disable-specific-error-thrown-by-ckeditor";

describe("Document PhpWiki integration", () => {
    let project_unixname: string, public_name: string, now: number;

    before(() => {
        cy.clearSessionCookie();
        now = Date.now();

        project_unixname = "doc-phpwiki-" + now;
        public_name = "Doc PhpWiki " + now;
        cy.projectAdministratorLogin();
    });

    beforeEach(() => {
        cy.preserveSessionCookies();
        disableSpecificErrorThrownByCkeditor();
    });

    it("Creates a project with document service", () => {
        cy.visit("/project/new");
        cy.get("[data-test=project-registration-advanced-templates-tab]").click();
        cy.get(
            "[data-test=project-registration-card-label][for=project-registration-tuleap-template-other-user-project]"
        ).click();
        cy.get("[data-test=from-another-project]").select("PhpWiki Template");
        cy.get("[data-test=project-registration-next-button]").click();

        cy.get("[data-test=new-project-name]").type(public_name);
        cy.get("[data-test=project-shortname-slugified-section]").click();
        cy.get("[data-test=new-project-shortname]").type("{selectall}" + project_unixname);
        cy.get("[data-test=approve_tos]").click();
        cy.get("[data-test=project-registration-next-button]").click();
        cy.get("[data-test=start-working]").click({
            timeout: 20000,
        });
    });

    it("Creates wiki service pages", () => {
        cy.visitProjectService(project_unixname, "Wiki");
        cy.get("[data-test=create-wiki]").click();
    });

    it("Multiple document can references the same wiki page", function () {
        cy.visitProjectService(project_unixname, "Documents");

        const now = Date.now();

        createAWikiDocument(`A wiki document${now}`, "Wiki page");
        createAWikiDocument(`An other wiki document${now}`, "Wiki page");
        createAWikiDocument(`A third wiki document${now}`, "Wiki page");

        cy.get("[data-test=wiki-document-link]").first().click();

        cy.get("[data-test=wiki-document-location-toggle]").click();
        cy.get("[data-test=wiki-document-location]").contains(`A wiki document${now}`);
        cy.get("[data-test=wiki-document-location]").contains(`An other wiki document${now}`);
        cy.get("[data-test=wiki-document-location]").contains(`A third wiki document${now}`);
    });

    it("Phpwiki permissions", function () {
        cy.visitProjectService(project_unixname, "Documents");

        const now = Date.now();

        cy.log("wiki document have their permissions in document service");

        createAWikiDocument(`private${now}`, `My Wiki & Page document${now}`);
        cy.visitProjectService(project_unixname, "Documents");
        cy.get("[data-test=document-tree-content]").contains("td", `private${now}`).click();
        cy.get("[data-test=go-to-the-wiki-page]").click();

        // ignore rule for phpwiki generated content
        updateWikiPage("My wiki content");
        updateWikiPage("My wiki content updated");
        cy.get("[data-test=main-content]").contains(`My Wiki & Page document${now}`);

        cy.visitProjectService(project_unixname, "Wiki");
        cy.get("[data-test=wiki-admin]").click();
        cy.get("[data-test=manage-wiki-page]").click();

        cy.log("Document delegated permissions");
        cy.get("[data-test=table-test]")
            .first()
            .contains("Permissions controlled by documents manager");

        cy.log("Wiki permissions");
        cy.get("[data-test=table-test]").last().contains("[Define Permissions]");

        cy.log("Document events");
        cy.visitProjectService(project_unixname, "Documents");
        cy.get("[data-test=document-tree-content]").contains("td", `private${now}`).click();
        cy.get("[data-test=document-history]").last().click({ force: true });

        cy.get("[data-test=table-test]").contains("Wiki page content change");
        cy.get("[data-test=table-test]").contains("Create");

        cy.log("project member can not see document when lack of permissions");
        cy.visitProjectService(project_unixname, "Documents");
        cy.get("[data-test=document-tree-content]").contains("td", `private${now}`).click();

        cy.get("[data-test=document-permissions]").last().click({ force: true });

        cy.get("[data-test=document-permission-Reader]").select("Project administrators");
        cy.get("[data-test=document-permission-Writer]").select("Project administrators");
        cy.get("[data-test=document-permission-Manager]").select("Project administrators");
        cy.get("[data-test=document-modal-submit-button]").last().click();

        cy.log("wiki page have their permissions in wiki service");

        cy.visitProjectService(project_unixname, "Documents");
        cy.get("[data-test=document-tree-content]").contains("td", `private${now}`).click();

        cy.url().then((url) => {
            cy.userLogout();
            cy.projectMemberLogin();

            cy.visit(url);
            cy.get("[data-test=document-user-can-not-read-document]").contains(
                "granted read permission"
            );
        });

        cy.userLogout();
        cy.projectAdministratorLogin();

        cy.log("Delete wiki page");
        cy.visitProjectService(project_unixname, "Documents");
        cy.get("[data-test=document-tree-content]").contains("tr", `private${now}`).click();
        cy.get("[data-test=quick-look-button]").last().click({ force: true });
        cy.get("[data-test=document-quick-look]");
        cy.get("[data-test=document-quick-look-delete-button]").click({ force: true });
        cy.get("[data-test=delete-associated-wiki-page-checkbox]").click();
        cy.get("[data-test=document-confirm-deletion-button]").click();
    });
});

function createAWikiDocument(document_title: string, page_name: string): void {
    cy.get("[data-test=document-header-actions]").within(() => {
        cy.get("[data-test=document-item-action-new-button]").click();
    });

    cy.get("[data-test=document-new-item-modal]").within(() => {
        cy.get("[data-test=wiki]").click();

        cy.get("[data-test=document-new-item-title]").type(document_title);
        cy.get("[data-test=document-new-item-wiki-page-name]").type(page_name);
        cy.get("[data-test=document-modal-submit-button]").click();
    });
}

function updateWikiPage(page_content: string): void {
    cy.get("[data-test=php-wiki-edit-page]").contains("Edit").click();
    cy.get("[data-test=textarea-wiki-content]").clear().type(page_content);
    cy.get("[data-test=edit-page-action-buttons]").contains("Save").click();
}
