/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import { patch } from "@tuleap/tlp-fetch";
import { installProjectSidebarElement } from "@tuleap/project-sidebar-internal";

const SIDEBAR_COLLAPSED_CLASS = "sidebar-collapsed";
const SIDEBAR_EXPANDED_CLASS = "sidebar-expanded";

installProjectSidebarElement(window, () => {
    // Nothing to do here, we already load everything we need
});

function setupSidebarInteractions(sidebar: Element): void {
    const collapse_observer = new MutationObserver(async (mutations): Promise<void> => {
        for (const mutation of mutations) {
            const state = sidebar.hasAttribute(mutation.attributeName ?? "")
                ? SIDEBAR_COLLAPSED_CLASS
                : SIDEBAR_EXPANDED_CLASS;
            document.body.classList.remove(SIDEBAR_EXPANDED_CLASS, SIDEBAR_COLLAPSED_CLASS);
            document.body.classList.add(state);
            await patch("/api/users/self/preferences", {
                headers: {
                    "content-type": "application/json",
                },
                body: JSON.stringify({ key: "sidebar_state", value: state }),
            });
        }
    });
    collapse_observer.observe(sidebar, { attributes: true, attributeFilter: ["collapsed"] });
}

document.addEventListener("DOMContentLoaded", () => {
    const sidebars = document.getElementsByTagName("tuleap-project-sidebar");

    for (const sidebar of sidebars) {
        setupSidebarInteractions(sidebar);
    }
});
