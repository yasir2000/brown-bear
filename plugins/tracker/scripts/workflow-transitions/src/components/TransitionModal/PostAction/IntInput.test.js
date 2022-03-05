/*
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
 *
 */

import { shallowMount } from "@vue/test-utils";
import IntInput from "./IntInput.vue";
import localVue from "../../../support/local-vue.js";

describe("IntInput", () => {
    let wrapper;

    beforeEach(() => {
        wrapper = shallowMount(IntInput, {
            propsData: { value: 2 },
            localVue,
        });
    });

    describe("without value", () => {
        beforeEach(() => wrapper.setProps({ value: null }));

        it("Shows nothing", () => {
            expect(wrapper.text()).toEqual("");
        });
    });

    describe("when changing value", () => {
        beforeEach(() => {
            const input = wrapper.get("input");
            input.element.value = "6";
            input.trigger("input");
        });

        it("emits input event with corresponding value", () => {
            expect(wrapper.emitted().input).toBeTruthy();
            expect(wrapper.emitted().input[0]).toEqual([6]);
        });
    });

    describe("when trying to input not int value", () => {
        beforeEach(() => {
            const input = wrapper.get("input");
            input.element.value = "invalid format";
            input.trigger("input");
        });

        it("does not emit input event", () => {
            expect(wrapper.emitted().input).toBeFalsy();
        });
    });
});
