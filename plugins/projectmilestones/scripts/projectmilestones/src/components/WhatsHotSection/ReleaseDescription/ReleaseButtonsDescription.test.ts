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
import ReleaseButtonsDescription from "./ReleaseButtonsDescription.vue";
import { createStoreMock } from "../../../../../../../../src/scripts/vue-components/store-wrapper-jest";
import type { MilestoneData, Pane, StoreOptions } from "../../../type";
import { createReleaseWidgetLocalVue } from "../../../helpers/local-vue-for-test";

let release_data: MilestoneData & Required<Pick<MilestoneData, "planning">>;
const component_options: ShallowMountOptions<ReleaseButtonsDescription> = {};
const project_id = 102;

describe("ReleaseButtonsDescription", () => {
    let store_options: StoreOptions;
    let store;

    async function getPersonalWidgetInstance(
        store_options: StoreOptions
    ): Promise<Wrapper<ReleaseButtonsDescription>> {
        store = createStoreMock(store_options);

        component_options.mocks = { $store: store };
        component_options.localVue = await createReleaseWidgetLocalVue();

        return shallowMount(ReleaseButtonsDescription, component_options);
    }

    beforeEach(() => {
        store_options = {
            state: {
                label_tracker_planning: "Releases",
            },
        };

        release_data = {
            id: 2,
            planning: {
                id: "100",
            },
            resources: {
                milestones: {
                    accept: {
                        trackers: [
                            {
                                label: "Sprint1",
                            },
                        ],
                    },
                },
                additional_panes: [
                    {
                        icon_name: "fa-tlp-taskboard",
                        title: "Taskboard",
                        uri: "/taskboard/project/6",
                        identifier: "taskboard",
                    },
                    {
                        icon_name: "fa-check",
                        title: "Tests",
                        uri: "/testplan/project/6",
                        identifier: "testplan",
                    },
                ],
                cardwall: {
                    uri: "/cardwall/",
                },
            },
        } as MilestoneData;

        component_options.propsData = {
            release_data,
        };

        store_options.state.user_can_view_sub_milestones_planning = true;
    });

    it("Given user display widget, Then a good link to TestPlan is renderer", async () => {
        store_options.state.project_id = project_id;

        const wrapper = await getPersonalWidgetInstance(store_options);
        const ttm_element = wrapper.get("[data-test=pane-link-testplan]");
        expect(ttm_element.attributes("href")).toEqual("/testplan/project/6");
        expect(ttm_element.attributes("data-tlp-tooltip")).toEqual("Tests");
        expect(wrapper.get("[data-test=pane-icon-testplan]").classes()).toContain("fa-check");
    });

    it("Given user display widget, Then a good link to taskboard is renderer", async () => {
        store_options.state.project_id = project_id;

        const wrapper = await getPersonalWidgetInstance(store_options);
        const taskboard_element = wrapper.get("[data-test=pane-link-taskboard]");
        expect(taskboard_element.attributes("href")).toEqual("/taskboard/project/6");
        expect(taskboard_element.attributes("data-tlp-tooltip")).toEqual("Taskboard");
        expect(wrapper.get("[data-test=pane-icon-taskboard]").classes()).toContain(
            "fa-tlp-taskboard"
        );
    });

    it("Given user display widget, Then a good link to overview is renderer", async () => {
        store_options.state.project_id = project_id;

        const wrapper = await getPersonalWidgetInstance(store_options);
        expect(wrapper.get("[data-test=overview-link]").attributes("href")).toEqual(
            "/plugins/agiledashboard/?group_id=" +
                encodeURIComponent(project_id) +
                "&planning_id=" +
                encodeURIComponent(release_data.planning.id) +
                "&action=show&aid=" +
                encodeURIComponent(release_data.id) +
                "&pane=details"
        );
    });

    it("Given user display widget, Then a good link to cardwall is renderer", async () => {
        store_options.state.project_id = project_id;

        const wrapper = await getPersonalWidgetInstance(store_options);
        expect(wrapper.get("[data-test=cardwall-link]").attributes("href")).toEqual(
            "/plugins/agiledashboard/?group_id=" +
                encodeURIComponent(project_id) +
                "&planning_id=" +
                encodeURIComponent(release_data.planning.id) +
                "&action=show&aid=" +
                encodeURIComponent(release_data.id) +
                "&pane=cardwall"
        );
    });

    it("When there isn't taskboard, Then there isn't any link to taskboard", async () => {
        release_data = {
            id: 2,
            planning: {
                id: "100",
            },
            resources: {
                milestones: {
                    accept: {
                        trackers: [
                            {
                                label: "Sprint1",
                            },
                        ],
                    },
                },
                additional_panes: [
                    {
                        title: "random",
                        identifier: "random",
                        icon_name: "fa-random",
                        uri: "/project/random",
                    },
                ],
                cardwall: null,
            },
        } as MilestoneData;

        component_options.propsData = {
            release_data,
        };

        const wrapper = await getPersonalWidgetInstance(store_options);
        expect(wrapper.find("[data-test=taskboard-link]").exists()).toBe(false);
    });

    it("When there isn't cardwall in resources, Then there isn't any link to cardwall", async () => {
        release_data = {
            id: 2,
            planning: {
                id: "100",
            },
            resources: {
                milestones: {
                    accept: {
                        trackers: [
                            {
                                label: "Sprint1",
                            },
                        ],
                    },
                },
                additional_panes: [] as Pane[],
                cardwall: null,
            },
        } as MilestoneData;

        component_options.propsData = {
            release_data,
        };

        const wrapper = await getPersonalWidgetInstance(store_options);
        expect(wrapper.find("[data-test=cardwall-link]").exists()).toBe(false);
    });
});
