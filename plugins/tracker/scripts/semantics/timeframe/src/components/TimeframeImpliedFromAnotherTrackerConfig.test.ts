/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import { shallowMount } from "@vue/test-utils";

import { createSemanticTimeframeAdminLocalVue } from "../helpers/local-vue-for-tests";
import TimeframeImpliedFromAnotherTrackerConfig from "./TimeframeImpliedFromAnotherTrackerConfig.vue";

describe("TimeframeImpliedFromAnotherTrackerConfig", () => {
    it("should display the current configuration when the semantic timeframe is inherited from another tracker", async () => {
        const wrapper = shallowMount(TimeframeImpliedFromAnotherTrackerConfig, {
            localVue: await createSemanticTimeframeAdminLocalVue(),
            propsData: {
                suitable_trackers: [
                    { id: 150, name: "Sprints" },
                    { id: 151, name: "Releases" },
                    { id: 152, name: "Epics" },
                ],
                has_artifact_link_field: true,
                implied_from_tracker_id: 150,
                current_tracker_id: 10,
                has_other_trackers_implying_their_timeframes: false,
            },
        });

        const select_box = wrapper.find("[data-test=implied-from-tracker-select-box]").element;
        if (!(select_box instanceof HTMLSelectElement)) {
            throw new Error("<select> not found");
        }

        await wrapper.vm.$nextTick();

        expect(select_box.value).toEqual("150");
    });

    it("should display an error message when the semantic cannot be inherited because some trackers inherit from the current tracker", async () => {
        const wrapper = shallowMount(TimeframeImpliedFromAnotherTrackerConfig, {
            localVue: await createSemanticTimeframeAdminLocalVue(),
            propsData: {
                suitable_trackers: [],
                has_artifact_link_field: true,
                implied_from_tracker_id: "",
                current_tracker_id: 10,
                has_other_trackers_implying_their_timeframes: true,
            },
        });

        expect(
            wrapper
                .find("[data-test=error-message-other-trackers-implying-their-timeframe]")
                .exists()
        ).toBe(true);
    });

    it("should display an error message when the semantic cannot be inherited because the current tracker has no link field", async () => {
        const wrapper = shallowMount(TimeframeImpliedFromAnotherTrackerConfig, {
            localVue: await createSemanticTimeframeAdminLocalVue(),
            propsData: {
                suitable_trackers: [],
                has_artifact_link_field: false,
                implied_from_tracker_id: "",
                current_tracker_id: 10,
                has_other_trackers_implying_their_timeframes: false,
            },
        });

        expect(wrapper.find("[data-test=error-message-no-art-link-field]").exists()).toBe(true);
    });
});
