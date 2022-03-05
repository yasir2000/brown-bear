/**
 * Copyright (c) Enalean SAS - 2016 - Present. All rights reserved
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

import { createModal, datePicker } from "tlp";
import { openTargetModalIdOnClick } from "../tuleap/modals/modal-opener";

document.addEventListener("DOMContentLoaded", function () {
    initExpirationDatepicker();
    initChangePasswordModal();
    initWarningModalRestrictedStatusRemovalFromProjectNotAcceptingRestricted();
});

function initExpirationDatepicker(): void {
    const expiry_element = document.querySelector("#expiry");
    if (expiry_element && expiry_element instanceof HTMLInputElement) {
        datePicker(expiry_element);
    }
}

function initChangePasswordModal(): void {
    openTargetModalIdOnClick(document, "siteadmin-user-details-change-password");
}

function initWarningModalRestrictedStatusRemovalFromProjectNotAcceptingRestricted(): void {
    const warning_project_without_restricted_removal_modal_element = document.getElementById(
        "modal-warning-removal-project-not-including-restricted"
    );

    if (!warning_project_without_restricted_removal_modal_element) {
        return;
    }

    if (
        !warning_project_without_restricted_removal_modal_element.dataset
            .nbProjectNotAcceptingRestricted
    ) {
        return;
    }

    const nb_project_user_is_member_of_that_dont_accept_restricted = parseInt(
        warning_project_without_restricted_removal_modal_element.dataset
            .nbProjectNotAcceptingRestricted,
        10
    );

    if (
        Number.isNaN(nb_project_user_is_member_of_that_dont_accept_restricted) ||
        nb_project_user_is_member_of_that_dont_accept_restricted <= 0
    ) {
        return;
    }

    const confirm_button = document.getElementById(
        "modal-warning-removal-project-not-including-restricted-confirm"
    );

    if (!confirm_button) {
        return;
    }

    const user_details_form = document.getElementById("siteadmin-user-details-form");
    if (!user_details_form || !(user_details_form instanceof HTMLFormElement)) {
        return;
    }

    let has_submission_been_confirmed = false;
    confirm_button.addEventListener("click", () => {
        has_submission_been_confirmed = true;
        user_details_form.submit();
    });

    user_details_form.addEventListener("submit", (event) => {
        if (has_submission_been_confirmed) {
            return;
        }

        const user_status_input = document.getElementById("status");
        if (!user_status_input || !(user_status_input instanceof HTMLInputElement)) {
            return;
        }

        // Only display warning if we are going to mark the user as restricted
        if (user_status_input.value !== "R") {
            return;
        }

        event.preventDefault();

        const modal = createModal(warning_project_without_restricted_removal_modal_element, {
            destroy_on_hide: true,
        });
        modal.show();
    });
}
