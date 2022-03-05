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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

import { createStoreMock } from "@tuleap/core/scripts/vue-components/store-wrapper-jest";
import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import GitInlineFilter from "./GitInlineFilter.vue";
import localVueForTest from "./helper/local-vue-for-test";

describe("GitInlineFilter", () => {
    const store_options = {};

    function instantiateComponent(): Wrapper<GitInlineFilter> {
        const store = createStoreMock(store_options);
        return shallowMount(GitInlineFilter, {
            mocks: { $store: store },
            localVue: localVueForTest,
        });
    }

    it("When user types on keyboard, Then event is emitted", () => {
        const wrapper = instantiateComponent();
        wrapper.find("[data-test=git-inline-filter-input]").trigger("keyup");
        expect(wrapper.emitted("input")).toBeTruthy();
    });
});
