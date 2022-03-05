/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

export type Direction = "BOTTOM" | "TOP" | "NEXT" | "PREVIOUS";
export const BOTTOM: Direction = "BOTTOM";
export const TOP: Direction = "TOP";
export const NEXT: Direction = "NEXT";
export const PREVIOUS: Direction = "PREVIOUS";

export interface GettextProvider {
    getString(translatable_string: string, scope?: null, context?: string): string;
}

export type StatusButton =
    | "[data-shortcut-passed]"
    | "[data-shortcut-blocked]"
    | "[data-shortcut-not-run]";
export const PASSED: StatusButton = "[data-shortcut-passed]";
export const BLOCKED: StatusButton = "[data-shortcut-blocked]";
export const NOTRUN: StatusButton = "[data-shortcut-not-run]";
