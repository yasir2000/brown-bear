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

import type { ShallowMountOptions, Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import ReleaseBadgesOpenSprint from "./ReleaseBadgesOpenSprint.vue";
import { createStoreMock } from "../../../../../../../../src/scripts/vue-components/store-wrapper-jest";
import type { MilestoneData, StoreOptions } from "../../../type";
import { createReleaseWidgetLocalVue } from "../../../helpers/local-vue-for-test";
import ReleaseButtonsDescription from "../ReleaseDescription/ReleaseButtonsDescription.vue";

let sprint_data: MilestoneData & Required<Pick<MilestoneData, "planning">>;
const total_sprint = 10;
const component_options: ShallowMountOptions<ReleaseBadgesOpenSprint> = {};

const project_id = 102;

describe("ReleaseBadgesOpenSprint", () => {
    let store_options: StoreOptions;
    let store;

    async function getPersonalWidgetInstance(
        store_options: StoreOptions
    ): Promise<Wrapper<ReleaseBadgesOpenSprint>> {
        store = createStoreMock(store_options);

        component_options.mocks = { $store: store };
        component_options.localVue = await createReleaseWidgetLocalVue();

        return shallowMount(ReleaseBadgesOpenSprint, component_options);
    }

    beforeEach(() => {
        store_options = {
            state: {
                project_id: project_id,
            },
        };

        sprint_data = {
            label: "mile",
            id: 2,
            total_sprint,
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
            },
        } as MilestoneData;

        component_options.propsData = { sprint_data };
    });

    describe("Display sprint data", () => {
        it("When the component is rendered, Then the label of sprint is displayed", async () => {
            const wrapper = await getPersonalWidgetInstance(store_options);

            expect(wrapper.get("[data-test=sprint-label]").text()).toEqual("mile");
        });

        it("When the component is rendered, Then ReleaseBadgesButtonOpenSprint is rendered", async () => {
            const wrapper = await getPersonalWidgetInstance(store_options);

            expect(wrapper.findComponent(ReleaseButtonsDescription).exists()).toBe(true);
        });

        it("When a release is not in progress, Then the badge is outline", async () => {
            const wrapper = await getPersonalWidgetInstance(store_options);

            expect(wrapper.get("[data-test=sprint-label]").attributes("class")).toContain(
                "tlp-badge-outline"
            );
        });

        it("When a release is in progress, Then the badge is not outline", async () => {
            const end_date = new Date();
            end_date.setDate(end_date.getDate() + 1);

            sprint_data = {
                label: "mile",
                id: 2,
                start_date: new Date().toString(),
                end_date: end_date.toString(),
                total_sprint,
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
                },
            } as MilestoneData;

            component_options.propsData = { sprint_data };

            const wrapper = await getPersonalWidgetInstance(store_options);

            expect(wrapper.get("[data-test=sprint-label]").attributes("class")).not.toContain(
                "tlp-badge-outline"
            );
        });
    });
});
