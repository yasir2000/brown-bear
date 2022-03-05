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

import { shallowMount } from "@vue/test-utils";
import AppBurningParrot from "./AppBurningParrot.vue";
import { createSwitchToLocalVue } from "../helpers/local-vue-for-test";
import { createStoreMock } from "../../../vue-components/store-wrapper-jest";
import type { State } from "../store/type";

describe("AppBurningParrot", () => {
    beforeEach(() => {
        document.body.innerHTML = "";
    });

    it("Display the modal when user click on the trigger button", async () => {
        const div = document.createElement("div");
        document.body.appendChild(div);

        const button = document.createElement("button");
        if (!(button instanceof HTMLButtonElement)) {
            throw Error("Unable to create a button");
        }
        button.id = "switch-to-button";
        document.body.appendChild(button);

        const wrapper = await shallowMount(AppBurningParrot, {
            localVue: await createSwitchToLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: {} as State,
                }),
            },
            attachTo: div,
        });

        expect(wrapper.classes("tlp-modal-shown")).toBe(false);
        button.click();
        expect(wrapper.classes("tlp-modal-shown")).toBe(true);
    });

    it("Load the history when the modal is shown", async () => {
        const div = document.createElement("div");
        document.body.appendChild(div);

        const button = document.createElement("button");
        if (!(button instanceof HTMLButtonElement)) {
            throw Error("Unable to create a button");
        }
        button.id = "switch-to-button";
        document.body.appendChild(button);

        const wrapper = await shallowMount(AppBurningParrot, {
            localVue: await createSwitchToLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: {} as State,
                }),
            },
            attachTo: div,
        });

        expect(wrapper.vm.$store.dispatch).not.toHaveBeenCalledWith("loadHistory");
        button.click();
        expect(wrapper.vm.$store.dispatch).toHaveBeenCalledWith("loadHistory");
    });
});
