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

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { createTestPlanLocalVue } from "../../helpers/local-vue-for-test";
import type { RootState } from "../../store/type";
import type { BacklogItem } from "../../type";
import { createStoreMock } from "../../../../../../../src/scripts/vue-components/store-wrapper-jest";
import AddTestButtonWithAdditionalActionsMenu from "./AddTestButtonWithAdditionalActionsMenu.vue";

describe("AddTestButtonWithAdditionalActionsMenu", () => {
    async function createWrapper(
        state: RootState,
        backlog_item: BacklogItem,
        should_empty_state_be_displayed: boolean
    ): Promise<Wrapper<AddTestButtonWithAdditionalActionsMenu>> {
        return shallowMount(AddTestButtonWithAdditionalActionsMenu, {
            localVue: await createTestPlanLocalVue(),
            propsData: {
                backlog_item,
                should_empty_state_be_displayed,
            },
            mocks: {
                $store: createStoreMock({
                    state,
                }),
            },
        });
    }

    it("Does not display the button if the backlog items says it can not add a test", async () => {
        const wrapper = await createWrapper(
            { milestone_id: 69, testdefinition_tracker_id: null } as RootState,
            { id: 123, can_add_a_test: false } as BacklogItem,
            false
        );

        expect(wrapper.element).toMatchInlineSnapshot(`<!---->`);
    });

    it("Does not display the button if there is no test definition tracker id", async () => {
        const wrapper = await createWrapper(
            { milestone_id: 69, testdefinition_tracker_id: null } as RootState,
            { id: 123, can_add_a_test: true } as BacklogItem,
            false
        );

        expect(wrapper.element).toMatchInlineSnapshot(`<!---->`);
    });

    it("Does not display the button if the test definitions are still loading", async () => {
        const wrapper = await createWrapper(
            { milestone_id: 69, testdefinition_tracker_id: 42 } as RootState,
            {
                id: 123,
                is_loading_test_definitions: true,
                can_add_a_test: true,
                is_expanded: true,
            } as BacklogItem,
            false
        );

        expect(wrapper.element).toMatchInlineSnapshot(`<!---->`);
    });

    it("Does not display the button if an error occurred during the load of the test definitions", async () => {
        const wrapper = await createWrapper(
            { milestone_id: 69, testdefinition_tracker_id: 42 } as RootState,
            {
                id: 123,
                is_loading_test_definitions: false,
                has_test_definitions_loading_error: true,
                can_add_a_test: true,
                is_expanded: true,
            } as BacklogItem,
            false
        );

        expect(wrapper.element).toMatchInlineSnapshot(`<!---->`);
    });

    it("Displays the button if conditions are ok", async () => {
        const wrapper = await createWrapper(
            { milestone_id: 69, testdefinition_tracker_id: 42 } as RootState,
            {
                id: 123,
                is_loading_test_definitions: false,
                has_test_definitions_loading_error: false,
                can_add_a_test: true,
                is_expanded: true,
            } as BacklogItem,
            false
        );

        expect(wrapper.element).toMatchSnapshot();
    });
});
