/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import slugify from "slugify";

export function getSlugifiedShortname(tracker_name: string): string {
    slugify.extend({
        "+": "_",
        ".": "_",
        "~": "_",
        "(": "_",
        ")": "_",
        "!": "_",
        ":": "_",
        "@": "_",
        '"': "_",
        "'": "_",
        "*": "_",
        "©": "_",
        "®": "_",
        "<": "_",
        ">": "_",
        "-": "_",
    });

    const slugified_shortname = slugify(tracker_name, {
        lower: true,
        replacement: "_",
    }).replace(/_+/, "_");

    if (slugified_shortname.length > 25) {
        return slugified_shortname.substring(0, 25);
    }

    return slugified_shortname;
}
