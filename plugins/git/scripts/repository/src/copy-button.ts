/*
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

export default function initCopyButton(): void {
    const buttons = document.querySelectorAll(".git-repository-copy-url");
    if (buttons.length === 0) {
        return;
    }

    for (const copy_button of buttons) {
        if (!(copy_button instanceof HTMLElement)) {
            throw new Error("Copy Button is not HTMLElement");
        }

        const input_id = copy_button.dataset.inputId;

        if (!input_id) {
            throw new Error("Copy Button has not input id");
        }

        const input = document.getElementById(input_id);
        if (!(input instanceof HTMLInputElement)) {
            continue;
        }

        const copy_clicked_message = copy_button.dataset.copyClickedMessage;

        if (!copy_clicked_message) {
            continue;
        }

        const original_title = copy_button.getAttribute("data-tlp-tooltip");

        if (!original_title) {
            continue;
        }

        copy_button.addEventListener("click", function () {
            input.select();
            document.execCommand("copy");
            copy_button.setAttribute("data-tlp-tooltip", copy_clicked_message);

            removeTooltipDisplay(copy_button, original_title);
        });
    }
}

function removeTooltipDisplay(copy_button: Element, original_title: string): void {
    setTimeout(function () {
        copy_button.setAttribute("data-tlp-tooltip", original_title);
    }, 5000);
}
