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

import type { RetrieveLinkTypes } from "./RetrieveLinkTypes";
import type { RetrieveLinkedArtifactsByType } from "./RetrieveLinkedArtifactsByType";
import type { LinkedArtifact, LinkType } from "./LinkedArtifact";
import type { RetrieveAllLinkedArtifacts } from "./RetrieveAllLinkedArtifacts";
import type { CurrentArtifactIdentifier } from "../../CurrentArtifactIdentifier";
import { Fault, isFault } from "@tuleap/fault";
import { NoLinksInCreationModeFault } from "./NoLinksInCreationModeFault";
import type { AddLinkedArtifactCollection } from "./AddLinkedArtifactCollection";

export const LinksRetriever = (
    types_retriever: RetrieveLinkTypes,
    artifacts_retriever: RetrieveLinkedArtifactsByType,
    links_adder: AddLinkedArtifactCollection
): RetrieveAllLinkedArtifacts => ({
    async getLinkedArtifacts(
        current_artifact_identifier: CurrentArtifactIdentifier | null
    ): Promise<Fault | LinkedArtifact[]> {
        if (current_artifact_identifier === null) {
            return Promise.resolve(NoLinksInCreationModeFault());
        }
        const link_types = await types_retriever
            .getAllLinkTypes(current_artifact_identifier)
            .catch(Fault.fromError);
        if (isFault(link_types)) {
            return Promise.resolve(link_types);
        }
        const promises = link_types.map((type: LinkType) => {
            return artifacts_retriever.getLinkedArtifactsByLinkType(
                current_artifact_identifier,
                type
            );
        });

        return Promise.all(promises).then((collections) => {
            const all_links = collections.flat();
            links_adder.addLinkedArtifacts(all_links);
            return all_links;
        }, Fault.fromError);
    },
});
