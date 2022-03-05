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

import type { CampaignState } from "./campaign/type";
import type { BacklogItemState } from "./backlog-item/type";
import type { ArtifactLinkType } from "@tuleap/plugin-docgen-docx";

export interface State {
    readonly user_display_name: string;
    readonly user_timezone: string;
    readonly user_locale: string;
    readonly project_id: number;
    readonly project_name: string;
    readonly milestone_id: number;
    readonly milestone_title: string;
    readonly parent_milestone_title: string;
    readonly milestone_url: string;
    readonly user_can_create_campaign: boolean;
    readonly testdefinition_tracker_id: number | null;
    readonly testdefinition_tracker_name: string;
    readonly expand_backlog_item_id: number;
    readonly highlight_test_definition_id: number | null;
    readonly platform_name: string;
    readonly platform_logo_url: string;
    readonly base_url: string;
    readonly artifact_links_types: ReadonlyArray<ArtifactLinkType>;
}

export interface RootState extends State {
    readonly campaign: CampaignState;
    readonly backlog_item: BacklogItemState;
}
