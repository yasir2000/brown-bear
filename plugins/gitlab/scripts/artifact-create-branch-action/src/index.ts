/*
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

import codendi from "codendi";

document.addEventListener("DOMContentLoaded", () => {
    const create_branch_link = document.getElementById("artifact-create-gitlab-branches");
    const action_dropdown_icon = document.getElementById("tracker-artifact-action-icon");

    if (!create_branch_link || !action_dropdown_icon) {
        return;
    }

    create_branch_link.addEventListener("click", async () => {
        if (create_branch_link.classList.contains("disabled")) {
            return;
        }

        action_dropdown_icon.classList.add("fa-spin", "fa-spinner");
        create_branch_link.classList.add("disabled");

        const loading_modal_element = document.createElement("div");
        loading_modal_element.classList.add("tuleap-modal-loading");
        document.body.appendChild(loading_modal_element);

        try {
            const { init } = await import(
                /* webpackChunkName: "create-gitlab-branch-modal" */ "./modal"
            );

            await init(create_branch_link);
        } catch (e) {
            codendi.feedback.log("error", "Error while loading the GitLab branch creation modal.");
            throw e;
        } finally {
            document.body.removeChild(loading_modal_element);
            action_dropdown_icon.classList.remove("fa-spin", "fa-spinner");
            create_branch_link.classList.remove("disabled");
        }
    });
});
