/*
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

import type { Identifier } from "../../Identifier";

// I identify an artifact linked to the current artifact under edition
export type LinkedArtifactIdentifier = Identifier<"LinkedArtifactIdentifier">;

export const FORWARD_DIRECTION = "forward";

export interface LinkType {
    readonly shortname: string;
    readonly direction: string;
    readonly label: string;
}

export interface Tracker {
    readonly color_name: string;
}

export interface LinkedArtifact {
    readonly identifier: LinkedArtifactIdentifier;
    readonly xref: string;
    readonly title: string;
    readonly uri: string;
    readonly tracker: Tracker;
    readonly status: string;
    readonly is_open: boolean;
    readonly link_type: LinkType;
}
