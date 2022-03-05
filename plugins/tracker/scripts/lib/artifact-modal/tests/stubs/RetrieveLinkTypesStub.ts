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

import type { RetrieveLinkTypes } from "../../src/domain/fields/link-field-v2/RetrieveLinkTypes";
import type { LinkType } from "../../src/domain/fields/link-field-v2/LinkedArtifact";

export const RetrieveLinkTypesStub = {
    withTypes: (link_type: LinkType, ...other_link_types: LinkType[]): RetrieveLinkTypes => {
        const types = [link_type, ...other_link_types];
        return {
            getAllLinkTypes: (): Promise<LinkType[]> => Promise.resolve(types),
        };
    },

    withError: (error_message: string): RetrieveLinkTypes => ({
        getAllLinkTypes: (): Promise<never> => Promise.reject(new Error(error_message)),
    }),
};
