/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import type { ShallowMountOptions, Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import ReleaseDescriptionBadgesTracker from "./ReleaseDescriptionBadgesTracker.vue";
import { createStoreMock } from "../../../../../../../../src/scripts/vue-components/store-wrapper-jest";
import type { MilestoneData, StoreOptions, TrackerNumberArtifacts } from "../../../type";
import { createReleaseWidgetLocalVue } from "../../../helpers/local-vue-for-test";

let release_data: MilestoneData;
const component_options: ShallowMountOptions<ReleaseDescriptionBadgesTracker> = {};
const project_id = 102;

describe("ReleaseDescriptionBadgesTracker", () => {
    let store_options: StoreOptions;
    let store;

    async function getPersonalWidgetInstance(
        store_options: StoreOptions
    ): Promise<Wrapper<ReleaseDescriptionBadgesTracker>> {
        store = createStoreMock(store_options);
        component_options.mocks = { $store: store };
        component_options.localVue = await createReleaseWidgetLocalVue();

        return shallowMount(ReleaseDescriptionBadgesTracker, component_options);
    }

    beforeEach(() => {
        store_options = {
            state: {},
        };

        release_data = {
            id: 2,
            planning: {
                id: "100",
            },
            number_of_artifact_by_trackers: [
                {
                    label: "Bug",
                    id: 1,
                    total_artifact: 2,
                    color_name: "red-fiesta",
                },
            ],
        } as MilestoneData;

        component_options.propsData = {
            release_data,
        };
    });

    it("Given user display widget, Then the good number of artifacts and good color of the tracker is rendered", async () => {
        store_options.state.project_id = project_id;

        const wrapper = await getPersonalWidgetInstance(store_options);

        expect(wrapper.get("[data-test=color-name-tracker-1]").classes()).toEqual([
            "release-number-artifacts-tracker",
            "release-number-artifacts-tracker-red-fiesta",
        ]);

        expect(wrapper.get("[data-test=total-artifact-tracker]").text()).toEqual("2");

        expect(wrapper.get("[data-test=artifact-tracker-name]").text()).toEqual("Bug");
    });

    it("When there is a tracker but wihout artifact, Then it is not displayed", async () => {
        release_data = {
            id: 2,
            number_of_artifact_by_trackers: [
                {
                    label: "Bug",
                    id: 1,
                    total_artifact: 0,
                    color_name: "red-fiesta",
                },
                {
                    label: "Sprints",
                    id: 2,
                    total_artifact: 2,
                    color_name: "deep-blue",
                },
            ],
        } as MilestoneData;

        component_options.propsData = {
            release_data,
        };

        const wrapper = await getPersonalWidgetInstance(store_options);

        expect(wrapper.find("[data-test=color-name-tracker-1]").exists()).toBe(false);
        expect(wrapper.find("[data-test=color-name-tracker-2]").exists()).toBe(true);
    });

    it("When there are no artifacts, Then there is no title", async () => {
        release_data = {
            id: 2,
            number_of_artifact_by_trackers: [] as TrackerNumberArtifacts[],
        } as MilestoneData;

        component_options.propsData = {
            release_data,
        };

        const wrapper = await getPersonalWidgetInstance(store_options);

        expect(wrapper.find("[data-test=subtitle-tracker]").exists()).toBe(false);
    });

    it("When trackers are displayed, Then there is a tooltip on the labels", async () => {
        release_data = {
            id: 2,
            number_of_artifact_by_trackers: [
                {
                    label: "Bug",
                    id: 1,
                    total_artifact: 0,
                    color_name: "red-fiesta",
                },
                {
                    label: "Sprints",
                    id: 2,
                    total_artifact: 2,
                    color_name: "deep-blue",
                },
            ],
        } as MilestoneData;

        component_options.propsData = {
            release_data,
        };

        const wrapper = await getPersonalWidgetInstance(store_options);

        expect(wrapper.get("[data-test=badges-tracker-tooltip-2]").text()).toEqual("Sprints");
    });
});
