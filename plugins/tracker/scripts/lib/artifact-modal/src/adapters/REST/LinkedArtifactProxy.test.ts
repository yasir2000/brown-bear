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

import type { APILinkedArtifact, APITracker } from "./APILinkedArtifact";
import type { LinkType } from "../../domain/fields/link-field-v2/LinkedArtifact";
import { LinkedArtifactProxy } from "./LinkedArtifactProxy";

const ARTIFACT_ID = 7;
const TITLE = "maeandroid";
const STATUS = "Ongoing";
const HTML_URI = "/plugins/tracker/?aid=" + ARTIFACT_ID;
const TRACKER_SHORTNAME = "story";
const COLOR = "neon-green";
const CROSS_REFERENCE = `${TRACKER_SHORTNAME} #${ARTIFACT_ID}`;

describe(`LinkedArtifactProxy`, () => {
    it(`builds a Linked Artifact from an Artifact representation from the API`, () => {
        const tracker: APITracker = { color_name: COLOR };
        const api_artifact: APILinkedArtifact = {
            id: ARTIFACT_ID,
            title: TITLE,
            status: STATUS,
            is_open: true,
            html_url: HTML_URI,
            xref: CROSS_REFERENCE,
            tracker,
        };
        const link_type: LinkType = {
            shortname: "_is_child",
            direction: "forward",
            label: "Parent",
        };

        const linked_artifact = LinkedArtifactProxy.fromAPILinkedArtifactAndType(
            api_artifact,
            link_type
        );

        expect(linked_artifact.identifier.id).toBe(ARTIFACT_ID);
        expect(linked_artifact.title).toBe(TITLE);
        expect(linked_artifact.status).toBe(STATUS);
        expect(linked_artifact.is_open).toBe(true);
        expect(linked_artifact.uri).toBe(HTML_URI);
        expect(linked_artifact.xref).toBe(CROSS_REFERENCE);
        expect(linked_artifact.link_type).toEqual(link_type);
        expect(linked_artifact.tracker).toEqual(tracker);
    });
});
