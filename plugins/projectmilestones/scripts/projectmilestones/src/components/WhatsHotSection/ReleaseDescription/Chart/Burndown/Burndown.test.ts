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

import type { MilestoneData, StoreOptions } from "../../../../../type";
import type { ShallowMountOptions, Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { createStoreMock } from "../../../../../../../../../../src/scripts/vue-components/store-wrapper-jest";
import { createReleaseWidgetLocalVue } from "../../../../../helpers/local-vue-for-test";
import Burndown from "./Burndown.vue";

const component_options: ShallowMountOptions<Burndown> = {};
let release_data: MilestoneData;

describe("Burndown", () => {
    let store_options: StoreOptions;
    let store;

    async function getPersonalWidgetInstance(
        store_options: StoreOptions
    ): Promise<Wrapper<Burndown>> {
        store = createStoreMock(store_options);

        component_options.mocks = { $store: store };
        component_options.localVue = await createReleaseWidgetLocalVue();

        return shallowMount(Burndown, component_options);
    }

    beforeEach(() => {
        store_options = {
            state: {},
        };

        release_data = {
            id: 2,
        } as MilestoneData;

        component_options.propsData = {
            release_data,
        };

        getPersonalWidgetInstance(store_options);
    });

    it("When component is renderer, Then there is a svg element with id of release", async () => {
        const wrapper = await getPersonalWidgetInstance(store_options);
        expect(wrapper.element).toMatchSnapshot();
    });
});
