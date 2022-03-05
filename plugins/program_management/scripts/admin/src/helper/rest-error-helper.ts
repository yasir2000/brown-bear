/**
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

export function resetRestErrorAlert(doc: Document, element_id: string): void {
    const alert = doc.getElementById(element_id);
    if (!alert) {
        throw new Error("Rest Error Alert with id " + element_id + " does not exist");
    }
    alert.textContent = "";
    alert.classList.add("program-management-error-rest-not-show");
}

export function setRestErrorMessage(doc: Document, element_id: string, message: string): void {
    const alert = doc.getElementById(element_id);
    if (!alert) {
        throw new Error("Rest Error Alert with id " + element_id + " does not exist");
    }
    alert.textContent = message;
    alert.classList.remove("program-management-error-rest-not-show");
}
