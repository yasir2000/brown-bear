/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

import { get } from "tlp";
import type { RepositoryFineGrainedPermissions } from "./type";

export { getGitPermissions };

async function getGitPermissions(
    project_id: number,
    selected_ugroup_id: string
): Promise<{ repositories: RepositoryFineGrainedPermissions[] }> {
    const response = await get("/plugins/git/", {
        params: {
            group_id: project_id,
            selected_ugroup_id: selected_ugroup_id,
            action: "permission-per-group",
        },
    });

    return response.json();
}
