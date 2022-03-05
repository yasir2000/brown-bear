/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

describe("Document new UI", () => {
    before(() => {
        cy.clearSessionCookie();
        cy.projectMemberLogin();
        cy.visitProjectService("document-project", "Documents");
    });

    beforeEach(() => {
        cy.preserveSessionCookies();
    });

    it("has an empty state", () => {
        cy.get("[data-test=document-empty-state]");
        cy.get("[data-test=docman-new-item-button]");
    });

    context("Item manipulation", () => {
        before(() => {
            cy.visitProjectService("document-project", "Documents");
        });

        beforeEach(() => {
            disableSpecificErrorThrownByCkeditor();
        });

        it("user can manipulate folders", () => {
            cy.get("[data-test=document-header-actions]").within(() => {
                cy.get("[data-test=document-drop-down-button]").click();

                cy.get("[data-test=document-new-folder-creation-button]").click();
            });

            cy.get("[data-test=document-new-folder-modal]").within(() => {
                cy.get("[data-test=document-new-item-title]").type("My new folder");
                cy.get("[data-test=document-property-description]").type(
                    "With a description because I like to describe what I'm doing"
                );

                cy.get("[data-test=document-modal-submit-button]").click();
            });

            cy.get("[data-test=document-tree-content]")
                .contains("tr", "My new folder")
                .within(() => {
                    // button is displayed on tr::hover, so we need to force click
                    cy.get("[data-test=quick-look-button]").click({ force: true });
                });

            cy.get("[data-test=document-quick-look]").contains("My new folder");

            // force: true is mandatory because on small screen button might be displayed with only an icon + ellipsis and cause following error:
            // This element '...' is not visible because it has an effective width and height of: '0 x 0' pixels.
            cy.get("[data-test=document-quick-look-delete-button]").click({ force: true });
            cy.get("[data-test=document-confirm-deletion-button]").click();

            cy.get("[data-test=document-tree-content]").should("not.exist");
        });

        it("user can manipulate empty document", () => {
            cy.get("[data-test=document-header-actions]").within(() => {
                cy.get("[data-test=document-item-action-new-button]").click();
            });
            cy.get("[data-test=document-new-item-modal]").within(() => {
                cy.get("[data-test=empty]").click();

                cy.get("[data-test=document-new-item-title]").type("My new empty document");
                cy.get("[data-test=document-modal-submit-button]").click();
            });

            cy.get("[data-test=document-tree-content]")
                .contains("tr", "My new empty document")
                .within(() => {
                    // button is displayed on tr::hover, so we need to force click
                    cy.get("[data-test=quick-look-button]").click({ force: true });
                });

            cy.get("[data-test=document-quick-look]").contains("My new empty document");

            // force: true is mandatory because on small screen button might be displayed with only an icon + ellipsis and cause following error:
            // This element '...' is not visible because it has an effective width and height of: '0 x 0' pixels.
            cy.get("[data-test=document-quick-look-delete-button]").click({ force: true });
            cy.get("[data-test=document-confirm-deletion-button]").click();

            cy.get("[data-test=document-tree-content]").should("not.exist");
        });

        it("user can manipulate links", () => {
            cy.get("[data-test=document-header-actions]").within(() => {
                cy.get("[data-test=document-item-action-new-button]").click();
            });

            cy.get("[data-test=document-new-item-modal]").within(() => {
                cy.get("[data-test=link]").click();
                cy.get("[data-test=document-new-item-title]").type("My new link document");
                cy.get("[data-test=document-new-item-link-url]").type("https://example.com");
                cy.get("[data-test=document-modal-submit-button]").click();
            });

            cy.get("[data-test=document-tree-content]")
                .contains("tr", "My new link document")
                .within(() => {
                    // button is displayed on tr::hover, so we need to force click
                    cy.get("[data-test=quick-look-button]").click({ force: true });
                });

            cy.get("[data-test=document-quick-look]").contains("My new link document");

            cy.get("[data-test=document-quicklook-action-button-new-version").click({
                force: true,
            });

            cy.get("[data-test=document-new-version-modal]").within(() => {
                cy.get("[data-test=document-new-item-link-url]").clear();
                cy.get("[data-test=document-new-item-link-url]").type("https://example-bis.com");

                cy.get("[data-test=document-modal-submit-button]").click();
            });

            // force: true is mandatory because on small screen button might be displayed with only an icon + ellipsis and cause following error:
            // This element '...' is not visible because it has an effective width and height of: '0 x 0' pixels.
            cy.get("[data-test=document-quick-look-delete-button]").click({ force: true });
            cy.get("[data-test=document-confirm-deletion-button]").click();

            cy.get("[data-test=document-tree-content]").should("not.exist");
        });

        it("user should be able to create an embedded file", () => {
            cy.get("[data-test=document-header-actions]").within(() => {
                cy.get("[data-test=document-item-action-new-button]").click();
            });

            cy.get("[data-test=document-new-item-modal]").within(() => {
                cy.get("[data-test=embedded]").click();

                cy.get("[data-test=document-new-item-title]").type("My new html content");

                cy.window().then((win) => {
                    // eslint-disable-next-line @typescript-eslint/ban-ts-comment
                    // @ts-ignore
                    win.CKEDITOR.instances["document-new-item-embedded"].setData(
                        `<strong>This is the story of my life </strong>`
                    );
                });
                cy.get("[data-test=document-modal-submit-button]").click();
            });

            cy.get("[data-test=document-tree-content]")
                .contains("tr", "My new html content")
                .within(() => {
                    // button is displayed on tr::hover, so we need to force click
                    cy.get("[data-test=quick-look-button]").click({ force: true });
                });

            // force: true is mandatory because on small screen button might be displayed with only an icon + ellipsis and cause following error:
            // This element '...' is not visible because it has an effective width and height of: '0 x 0' pixels.
            cy.get("[data-test=document-quick-look-delete-button]").click({ force: true });
            cy.get("[data-test=document-confirm-deletion-button]").click();

            cy.get("[data-test=document-tree-content]").should("not.exist");
        });

        it(`user can download a folder as a zip archive`, () => {
            // Create a folder
            cy.get("[data-test=document-header-actions]").within(() => {
                cy.get("[data-test=document-drop-down-button]").click();
                // need to force click because buttons can be out of viewport
                cy.get("[data-test=document-new-folder-creation-button]").click({ force: true });
            });

            cy.get("[data-test=document-new-folder-modal]").within(() => {
                cy.get("[data-test=document-new-item-title]").type("Folder download");
                cy.get("[data-test=document-modal-submit-button]").click();
            });

            // Go to the folder
            cy.get("[data-test=document-tree-content]").contains("a", "Folder download").click();

            // Create an embedded file in this folder
            cy.get("[data-test=document-header-actions]").within(() => {
                cy.get("[data-test=document-item-action-new-button]").click();
            });

            cy.get("[data-test=document-new-item-modal]").within(() => {
                cy.get("[data-test=embedded]").click();
                cy.get("[data-test=document-new-item-title]").type("Embedded file");

                cy.window().then((win) => {
                    // eslint-disable-next-line @typescript-eslint/ban-ts-comment
                    // @ts-ignore
                    win.CKEDITOR.instances["document-new-item-embedded"].setData(
                        `<strong>Our deeds determine us, as much as we determine our deeds.</strong>`
                    );
                });

                cy.get("[data-test=document-modal-submit-button]").click();
            });

            cy.visitProjectService("document-project", "Documents");

            cy.get("[data-test=document-tree-content]")
                .contains("tr", "Folder download")
                .within(($row) => {
                    // We cannot click the download button, otherwise the browser will ask "Where to save this file ?"
                    // and will stop the test.
                    cy.get("[data-test=document-dropdown-download-folder-as-zip]").should("exist");
                    const folder_id = $row.data("itemId");
                    if (folder_id === undefined) {
                        throw new Error("Could not retrieve the folder id from its <tr>");
                    }
                    const download_uri = `/plugins/document/document-project/folders/${encodeURIComponent(
                        folder_id
                    )}/download-folder-as-zip`;

                    // Verify the download URI returns code 200 and has the correct headers
                    cy.request({
                        url: download_uri,
                    }).then((response) => {
                        expect(response.status).to.equal(200);
                        expect(response.headers["content-type"]).to.equal("application/zip");
                        expect(response.headers["content-disposition"]).to.equal(
                            'attachment; filename="Folder download.zip"'
                        );
                    });

                    // Open quick look so we can delete the folder
                    // button is displayed on tr::hover, so we need to force click
                    cy.get("[data-test=quick-look-button]").click({ force: true });
                });

            // force: true is mandatory because on small screen button might be displayed with only an icon + ellipsis and cause following error:
            // This element '...' is not visible because it has an effective width and height of: '0 x 0' pixels.
            cy.get("[data-test=document-quick-look-delete-button]").click({ force: true });
            cy.get("[data-test=document-confirm-deletion-button]").click();

            cy.get("[data-test=document-tree-content]").should("not.exist");
        });

        it("user can navigate and manipulate items using keyboard shortcuts", () => {
            cy.get("[data-test=document-header-actions]").should("be.visible");

            testNewFolderShortcut();
            testNewItemShortcut();
            testNavigationShortcuts();
            deleteItems();
        });
    });
});

function testNewFolderShortcut(): void {
    typeShortcut("b");
    cy.get("[data-test=document-new-folder-modal]")
        .should("be.visible")
        .within(() => {
            cy.focused()
                .should("have.attr", "data-test", "document-new-item-title")
                .type("First item");
            cy.get("[data-test=document-modal-submit-button]").click();
        });
    cy.get("[data-test=document-new-folder-modal]").should("not.be.visible");
    cy.get("[data-test=folder-title]").contains("First item");
}

function testNewItemShortcut(): void {
    typeShortcut("n");
    cy.get("[data-test=document-new-item-modal]")
        .should("be.visible")
        .within(() => {
            cy.focused()
                .should("have.attr", "data-test", "document-new-item-title")
                .type("Last item");
            cy.get("[data-test=empty]").click();
            cy.get("[data-test=document-modal-submit-button]").click();
        });
    cy.get("[data-test=document-new-item-modal]").should("not.be.visible");
    cy.get("[data-test=empty-file-title]").contains("Last item");
}

function testNavigationShortcuts(): void {
    typeShortcut("{ctrl}{uparrow}");
    cy.focused().should("contain", "First item");

    typeShortcut("{downarrow}");
    cy.focused().should("contain", "Last item");
}

function deleteItems(): void {
    typeShortcut("{del}");
    cy.get("[data-test=document-confirm-deletion-button]").click();
    cy.get("[data-test=document-delete-item-modal]").should("not.exist");

    typeShortcut("{ctrl}{uparrow}", "{del}");
    cy.get("[data-test=document-confirm-deletion-button]").click();
    cy.get("[data-test=document-delete-item-modal]").should("not.exist");

    cy.get("[data-test=document-tree-content]").should("not.exist");
}

function typeShortcut(...inputs: string[]): void {
    for (const input of inputs) {
        // eslint-disable-next-line cypress/require-data-selectors
        cy.get("body").type(input);
    }
}
