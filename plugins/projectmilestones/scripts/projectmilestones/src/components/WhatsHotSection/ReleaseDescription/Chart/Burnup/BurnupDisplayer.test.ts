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

import type {
    BurnupData,
    MilestoneData,
    PointsWithDateForBurnup,
    StoreOptions,
} from "../../../../../type";
import type { ShallowMountOptions, Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { createStoreMock } from "../../../../../../../../../../src/scripts/vue-components/store-wrapper-jest";
import { createReleaseWidgetLocalVue } from "../../../../../helpers/local-vue-for-test";
import ChartError from "../ChartError.vue";
import BurnupDisplayer from "./BurnupDisplayer.vue";
import Burnup from "./Burnup.vue";

let release_data: MilestoneData;
const component_options: ShallowMountOptions<BurnupDisplayer> = {};
const project_id = 102;

describe("BurnupDisplayer", () => {
    let store_options: StoreOptions;
    let store;

    async function getPersonalWidgetInstance(
        store_options: StoreOptions
    ): Promise<Wrapper<BurnupDisplayer>> {
        store = createStoreMock(store_options);

        component_options.mocks = { $store: store };
        component_options.localVue = await createReleaseWidgetLocalVue();

        return shallowMount(BurnupDisplayer, component_options);
    }

    beforeEach(() => {
        store_options = {
            state: {
                project_id,
                is_timeframe_duration: true,
            },
        };

        release_data = {
            id: 2,
            start_date: new Date("2017-01-22T13:42:08+02:00").toDateString(),
            burnup_data: {
                start_date: new Date("2017-01-22T13:42:08+02:00").toDateString(),
                duration: 10,
                capacity: 10,
                points: [] as number[],
                is_under_calculation: true,
                opening_days: [] as number[],
                points_with_date: [] as PointsWithDateForBurnup[],
                label: "burnup",
                points_with_date_count_elements: [],
            } as BurnupData,
        } as MilestoneData;

        component_options.propsData = {
            release_data,
        };

        getPersonalWidgetInstance(store_options);
    });

    it("When the burnup is under calculation, Then ChartError component is rendered", async () => {
        component_options.propsData = {
            release_data,
        };
        const wrapper = await getPersonalWidgetInstance(store_options);
        const chart_error = wrapper.findComponent(ChartError);

        expect(chart_error.attributes("is_under_calculation")).toBeTruthy();
        expect(chart_error.attributes("has_error_start_date")).toBeFalsy();
        expect(chart_error.attributes("has_error_duration")).toBeFalsy();
    });

    it("When there isn't start date, Then ChartError component is rendered", async () => {
        release_data = {
            id: 2,
            start_date: null,
            burnup_data: {
                start_date: "",
                duration: 10,
                capacity: 10,
                points: [] as number[],
                is_under_calculation: false,
                opening_days: [] as number[],
                points_with_date: [] as PointsWithDateForBurnup[],
                label: "burup",
                points_with_date_count_elements: [],
            } as BurnupData,
        } as MilestoneData;

        component_options.propsData = {
            release_data,
        };

        const wrapper = await getPersonalWidgetInstance(store_options);
        const chart_error = wrapper.findComponent(ChartError);

        expect(chart_error.attributes("is_under_calculation")).toBeFalsy();
        expect(chart_error.attributes("has_error_start_date")).toBeTruthy();
        expect(chart_error.attributes("has_error_duration")).toBeFalsy();
    });

    it("When there duration is equal to 0, Then ChartError component is rendered", async () => {
        release_data = {
            id: 2,
            start_date: new Date("2017-01-22T13:42:08+02:00").toDateString(),
            burnup_data: {
                start_date: new Date("2017-01-22T13:42:08+02:00").toDateString(),
                duration: 0,
                capacity: 10,
                points: [] as number[],
                is_under_calculation: false,
                opening_days: [] as number[],
                points_with_date: [] as PointsWithDateForBurnup[],
                label: "burup",
                points_with_date_count_elements: [],
            } as BurnupData,
        } as MilestoneData;

        component_options.propsData = {
            release_data,
        };

        const wrapper = await getPersonalWidgetInstance(store_options);
        const chart_error = wrapper.findComponent(ChartError);

        expect(chart_error.attributes("is_under_calculation")).toBeFalsy();
        expect(chart_error.attributes("has_error_start_date")).toBeFalsy();
        expect(chart_error.attributes("has_error_duration")).toBeTruthy();
    });

    it("When duration is null, Then ChartError component is rendered", async () => {
        release_data = {
            id: 2,
            start_date: new Date("2017-01-22T13:42:08+02:00").toDateString(),
            burnup_data: {
                start_date: new Date("2017-01-22T13:42:08+02:00").toDateString(),
                duration: null,
                capacity: 10,
                points: [] as number[],
                is_under_calculation: false,
                opening_days: [] as number[],
                points_with_date: [] as PointsWithDateForBurnup[],
                label: "burup",
                points_with_date_count_elements: [],
            } as BurnupData,
        } as MilestoneData;

        component_options.propsData = {
            release_data,
        };

        const wrapper = await getPersonalWidgetInstance(store_options);
        const chart_error = wrapper.findComponent(ChartError);

        expect(chart_error.attributes("is_under_calculation")).toBeFalsy();
        expect(chart_error.attributes("has_error_start_date")).toBeFalsy();
        expect(chart_error.attributes("has_error_duration")).toBeTruthy();
    });

    it("When duration is null and start date is null, Then ChartError component is rendered", async () => {
        release_data = {
            id: 2,
            start_date: null,
            burnup_data: {
                start_date: "",
                duration: null,
                capacity: 10,
                points: [] as number[],
                is_under_calculation: false,
                opening_days: [] as number[],
                points_with_date: [] as PointsWithDateForBurnup[],
                label: "burup",
                points_with_date_count_elements: [],
            } as BurnupData,
        } as MilestoneData;

        component_options.propsData = {
            release_data,
        };

        const wrapper = await getPersonalWidgetInstance(store_options);
        const chart_error = wrapper.findComponent(ChartError);

        expect(chart_error.attributes("is_under_calculation")).toBeFalsy();
        expect(chart_error.attributes("has_error_start_date")).toBeTruthy();
        expect(chart_error.attributes("has_error_duration")).toBeTruthy();
    });

    it("When duration is null and it is under calculation, Then ChartError component is rendered", async () => {
        release_data = {
            id: 2,
            start_date: new Date("2017-01-22T13:42:08+02:00").toDateString(),
            burnup_data: {
                start_date: new Date("2017-01-22T13:42:08+02:00").toDateString(),
                duration: null,
                capacity: 10,
                points: [] as number[],
                is_under_calculation: true,
                opening_days: [] as number[],
                points_with_date: [] as PointsWithDateForBurnup[],
                label: "burup",
                points_with_date_count_elements: [],
            } as BurnupData,
        } as MilestoneData;

        component_options.propsData = {
            release_data,
        };

        const wrapper = await getPersonalWidgetInstance(store_options);
        const chart_error = wrapper.findComponent(ChartError);

        expect(chart_error.attributes("is_under_calculation")).toBeTruthy();
        expect(chart_error.attributes("has_error_start_date")).toBeFalsy();
        expect(chart_error.attributes("has_error_duration")).toBeTruthy();
    });

    it("When the burnup can be created, Then a message is displayed", async () => {
        release_data = {
            id: 2,
            start_date: new Date("2017-01-22T13:42:08+02:00").toDateString(),
            burnup_data: {
                start_date: new Date("2017-01-22T13:42:08+02:00").toDateString(),
                duration: 10,
                capacity: 10,
                points: [] as number[],
                is_under_calculation: false,
                opening_days: [] as number[],
                points_with_date: [] as PointsWithDateForBurnup[],
                label: "burup",
                points_with_date_count_elements: [],
            } as BurnupData,
        } as MilestoneData;

        component_options.propsData = {
            release_data,
        };

        const wrapper = await getPersonalWidgetInstance(store_options);

        expect(wrapper.findComponent(Burnup).exists()).toBe(true);
    });

    it("When the timeframe is not on duration field and end date field is null, Then there is an error", async () => {
        store_options.state.is_timeframe_duration = false;
        release_data = {
            id: 2,
            start_date: new Date("2017-01-22T13:42:08+02:00").toDateString(),
            end_date: null,
            burnup_data: {
                start_date: new Date("2017-01-22T13:42:08+02:00").toDateString(),
                duration: 10,
                capacity: 10,
                points: [] as number[],
                is_under_calculation: false,
                opening_days: [] as number[],
                points_with_date: [] as PointsWithDateForBurnup[],
                label: "burup",
                points_with_date_count_elements: [],
            } as BurnupData,
        } as MilestoneData;

        component_options.propsData = {
            release_data,
        };

        const wrapper = await getPersonalWidgetInstance(store_options);
        const chart_error = wrapper.findComponent(ChartError);

        expect(chart_error.attributes("has_error_duration")).toBeTruthy();
    });

    it("When the timeframe is not on duration field and there is end date, Then there is no error", async () => {
        store_options.state.is_timeframe_duration = false;
        release_data = {
            id: 2,
            planning: {
                id: "100",
            },
            start_date: new Date("2017-01-22T13:42:08+02:00").toDateString(),
            end_date: new Date("2019-02-05T11:41:01+02:00").toDateString(),
            burnup_data: {
                start_date: new Date("2017-01-22T13:42:08+02:00").toDateString(),
                duration: 10,
                capacity: 10,
                points: [] as number[],
                is_under_calculation: false,
                opening_days: [] as number[],
                points_with_date: [] as PointsWithDateForBurnup[],
                label: "burup",
                points_with_date_count_elements: [],
            } as BurnupData,
        } as MilestoneData;

        component_options.propsData = {
            release_data,
        };

        const wrapper = await getPersonalWidgetInstance(store_options);

        expect(wrapper.findComponent(ChartError).exists()).toBeFalsy();
    });
});
