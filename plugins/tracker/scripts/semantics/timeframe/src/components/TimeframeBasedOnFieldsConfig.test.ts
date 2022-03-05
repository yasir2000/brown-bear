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

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { createSemanticTimeframeAdminLocalVue } from "../helpers/local-vue-for-tests";
import TimeframeBasedOnFieldsConfig from "./TimeframeBasedOnFieldsConfig.vue";

describe("TimeframeBasedOnFieldsConfig", () => {
    const config_using_end_date_mode = {
        selected_start_date_field_id: 1001,
        selected_end_date_field_id: 1002,
        selected_duration_field_id: "",
    };

    const config_using_duration_mode = {
        selected_start_date_field_id: 1001,
        selected_end_date_field_id: "",
        selected_duration_field_id: 1004,
    };

    const empty_config = {
        selected_start_date_field_id: "",
        selected_end_date_field_id: "",
        selected_duration_field_id: "",
    };

    async function getWrapper(config: {
        selected_start_date_field_id: number | string;
        selected_end_date_field_id: number | string;
        selected_duration_field_id: number | string;
    }): Promise<Wrapper<TimeframeBasedOnFieldsConfig>> {
        return shallowMount(TimeframeBasedOnFieldsConfig, {
            localVue: await createSemanticTimeframeAdminLocalVue(),
            propsData: {
                ...config,
                usable_date_fields: [
                    { id: 1001, label: "start date" },
                    { id: 1002, label: "end date" },
                    { id: 1003, label: "due date" },
                ],
                usable_numeric_fields: [
                    { id: 1004, label: "duration" },
                    { id: 1005, label: "nb days" },
                ],
            },
        });
    }

    function getHTMLInputElement(
        wrapper: Wrapper<TimeframeBasedOnFieldsConfig>,
        selector: string
    ): HTMLInputElement {
        const target = wrapper.find(selector).element;
        if (!(target instanceof HTMLInputElement)) {
            throw new Error(`${selector} does not point an HTMLInputElement`);
        }

        return target;
    }

    function getHTMLSelectElement(
        wrapper: Wrapper<TimeframeBasedOnFieldsConfig>,
        selector: string
    ): HTMLSelectElement {
        const target = wrapper.find(selector).element;
        if (!(target instanceof HTMLSelectElement)) {
            throw new Error(`${selector} does not point an HTMLSelectElement`);
        }

        return target;
    }

    function assertSelectContainsValues(
        select_box: HTMLSelectElement,
        expected_values: string[]
    ): void {
        expect(
            Array.from(select_box.options)
                .map((option: HTMLOptionElement) => option.value)
                .sort()
        ).toStrictEqual(expected_values);
    }

    describe("initialisation", () => {
        it.each([empty_config, config_using_end_date_mode])(
            "should select the start date/end date by default, and when the mode is active at initialisation",
            async (timeframe_config) => {
                const wrapper = await getWrapper(timeframe_config);

                const option_duration_radio_button = getHTMLInputElement(
                    wrapper,
                    "[data-test=option-duration]"
                );
                const option_end_date_radio_button = getHTMLInputElement(
                    wrapper,
                    "[data-test=option-end-date]"
                );

                expect(option_duration_radio_button.checked).toBe(false);
                expect(option_end_date_radio_button.checked).toBe(true);

                const start_date_select_box = getHTMLSelectElement(
                    wrapper,
                    "[data-test=start-date-field-select-box]"
                );
                const end_date_select_box = getHTMLSelectElement(
                    wrapper,
                    "[data-test=end-date-field-select-box]"
                );
                const duration_select_box = getHTMLSelectElement(
                    wrapper,
                    "[data-test=duration-field-select-box]"
                );

                expect(start_date_select_box.value).toEqual(
                    String(timeframe_config.selected_start_date_field_id)
                );
                expect(end_date_select_box.value).toEqual(
                    String(timeframe_config.selected_end_date_field_id)
                );
                expect(duration_select_box.value).toEqual(
                    String(timeframe_config.selected_duration_field_id)
                );

                expect(duration_select_box.hasAttribute("disabled")).toBe(true);
                expect(duration_select_box.hasAttribute("required")).toBe(false);
                expect(
                    wrapper.find("[data-test=duration-field-highlight-field-required").exists()
                ).toBe(false);

                expect(end_date_select_box.hasAttribute("disabled")).toBe(false);
                expect(end_date_select_box.hasAttribute("required")).toBe(true);
                expect(
                    wrapper.find("[data-test=end-date-field-highlight-field-required").exists()
                ).toBe(true);
            }
        );

        it("should select the start date/duration mode when active at initialisation", async () => {
            const wrapper = await getWrapper(config_using_duration_mode);

            const option_duration_radio_button = getHTMLInputElement(
                wrapper,
                "[data-test=option-duration]"
            );
            const option_end_date_radio_button = getHTMLInputElement(
                wrapper,
                "[data-test=option-end-date]"
            );

            expect(option_duration_radio_button.checked).toBe(true);
            expect(option_end_date_radio_button.checked).toBe(false);

            const start_date_select_box = getHTMLSelectElement(
                wrapper,
                "[data-test=start-date-field-select-box]"
            );
            const end_date_select_box = getHTMLSelectElement(
                wrapper,
                "[data-test=end-date-field-select-box]"
            );
            const duration_select_box = getHTMLSelectElement(
                wrapper,
                "[data-test=duration-field-select-box]"
            );

            expect(start_date_select_box.value).toEqual(
                String(config_using_duration_mode.selected_start_date_field_id)
            );
            expect(end_date_select_box.value).toEqual(
                String(config_using_duration_mode.selected_end_date_field_id)
            );
            expect(duration_select_box.value).toEqual(
                String(config_using_duration_mode.selected_duration_field_id)
            );

            expect(duration_select_box.hasAttribute("disabled")).toBe(false);
            expect(duration_select_box.hasAttribute("required")).toBe(true);
            expect(
                wrapper.find("[data-test=duration-field-highlight-field-required").exists()
            ).toBe(true);

            expect(end_date_select_box.hasAttribute("disabled")).toBe(true);
            expect(end_date_select_box.hasAttribute("required")).toBe(false);
            expect(
                wrapper.find("[data-test=end-date-field-highlight-field-required").exists()
            ).toBe(false);
        });
    });

    it("should toggle the start date/duration mode and the start date/end date mode", async () => {
        const wrapper = await getWrapper(config_using_end_date_mode);
        const option_duration_radio_button = getHTMLInputElement(
            wrapper,
            "[data-test=option-duration]"
        );
        const option_end_date_radio_button = getHTMLInputElement(
            wrapper,
            "[data-test=option-end-date]"
        );

        option_duration_radio_button.dispatchEvent(new Event("click"));
        await wrapper.vm.$nextTick();

        expect(getHTMLSelectElement(wrapper, "[data-test=end-date-field-select-box").disabled).toBe(
            true
        );
        expect(wrapper.find("[data-test=end-date-field-highlight-field-required").exists()).toBe(
            false
        );

        option_end_date_radio_button.dispatchEvent(new Event("click"));
        await wrapper.vm.$nextTick();

        expect(getHTMLSelectElement(wrapper, "[data-test=duration-field-select-box").disabled).toBe(
            true
        );
        expect(wrapper.find("[data-test=duration-field-highlight-field-required").exists()).toBe(
            false
        );
    });

    it("should remove in the end date select box the value selected in the start date select box and conversely", async () => {
        const wrapper = await getWrapper(empty_config);

        const start_date_select_box = getHTMLSelectElement(
            wrapper,
            "[data-test=start-date-field-select-box]"
        );
        const end_date_select_box = getHTMLSelectElement(
            wrapper,
            "[data-test=end-date-field-select-box]"
        );

        assertSelectContainsValues(start_date_select_box, ["", "1001", "1002", "1003"]);
        assertSelectContainsValues(end_date_select_box, ["", "1001", "1002", "1003"]);

        await wrapper.setData({ user_selected_start_date_field_id: 1001 });

        assertSelectContainsValues(end_date_select_box, ["", "1002", "1003"]);

        await wrapper.setData({ user_selected_end_date_field_id: 1002 });

        assertSelectContainsValues(start_date_select_box, ["", "1001", "1003"]);
    });
});
