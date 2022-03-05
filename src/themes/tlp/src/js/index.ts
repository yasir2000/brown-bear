/**
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

import type { Modal, ModalOptions } from "./modal";
import { createModal as createModalImplementation } from "./modal";
import type { Dropdown, DropdownOptions } from "./dropdowns";
import { createDropdown as createDropdownImplementation } from "./dropdowns";

export * from "@tuleap/tlp-fetch";
export * from "@tuleap/tlp-popovers";

export { default as locale } from "./default_locale";

export type { Modal, ModalOptions } from "./modal";
// Apply partially the modal creation function to pass document
export const createModal = (element: Element, options?: ModalOptions): Modal =>
    createModalImplementation(document, element, options);
export * from "./dropdowns";
// Apply partially the dropdowns creation function to pass document
export const createDropdown = (trigger: Element, options?: DropdownOptions): Dropdown =>
    createDropdownImplementation(document, trigger, options);

import jQuery from "jquery";
// Many scripts still depend on jQuery being on window
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore
window.jQuery = jQuery;

export * from "../vendor-overrides/select2";
export * from "../vendor-overrides/flatpickr";
