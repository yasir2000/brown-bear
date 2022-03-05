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

import type { APILinkedArtifact } from "./APILinkedArtifact";
import type { LinkedArtifact, LinkType } from "../../domain/fields/link-field-v2/LinkedArtifact";
import { LinkedArtifactIdentifierProxy } from "./LinkedArtifactIdentifierProxy";

export const LinkedArtifactProxy = {
    fromAPILinkedArtifactAndType: (
        artifact: APILinkedArtifact,
        link_type: LinkType
    ): LinkedArtifact => {
        const identifier = LinkedArtifactIdentifierProxy.fromAPILinkedArtifact(artifact);
        return {
            identifier,
            title: artifact.title,
            status: artifact.status,
            xref: artifact.xref,
            uri: artifact.html_url,
            is_open: artifact.is_open,
            tracker: artifact.tracker,
            link_type,
        };
    },
};
