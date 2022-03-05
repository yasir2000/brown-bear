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
import { createStoreMock } from "../../../../../../../src/scripts/vue-components/store-wrapper-jest";
import type { RootState } from "../../store/type";
import type { BacklogItem } from "../../type";
import type { BacklogItemState } from "../../store/backlog-item/type";
import ListOfBacklogItems from "./ListOfBacklogItems.vue";
import BacklogItemSkeleton from "./BacklogItemSkeleton.vue";
import BacklogItemContainer from "./BacklogItemContainer.vue";
import { createTestPlanLocalVue } from "../../helpers/local-vue-for-test";

describe("ListOfBacklogItems", () => {
    async function createWrapper(
        backlog_item: BacklogItemState
    ): Promise<Wrapper<ListOfBacklogItems>> {
        return shallowMount(ListOfBacklogItems, {
            localVue: await createTestPlanLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: {
                        backlog_item,
                    } as RootState,
                }),
            },
            stubs: {
                "backlog-item-empty-state": true,
                "backlog-item-error-state": true,
            },
        });
    }

    it("Displays skeletons while loading", async () => {
        const wrapper = await createWrapper({
            is_loading: true,
            has_loading_error: false,
            backlog_items: [] as BacklogItem[],
        });

        expect(wrapper.findComponent(BacklogItemSkeleton).exists()).toBe(true);
    });

    it("Does not display skeletons when not loading", async () => {
        const wrapper = await createWrapper({
            is_loading: false,
            has_loading_error: false,
            backlog_items: [] as BacklogItem[],
        });

        expect(wrapper.findComponent(BacklogItemSkeleton).exists()).toBe(false);
    });

    it("Does not display any cards when there is no backlog_item", async () => {
        const wrapper = await createWrapper({
            is_loading: false,
            has_loading_error: false,
            backlog_items: [] as BacklogItem[],
        });

        expect(wrapper.findComponent(BacklogItemContainer).exists()).toBe(false);
    });

    it("Displays a card for each backlog_item", async () => {
        const wrapper = await createWrapper({
            is_loading: false,
            has_loading_error: false,
            backlog_items: [{ id: 1 }, { id: 2 }] as BacklogItem[],
        });

        expect(wrapper.findAllComponents(BacklogItemContainer).length).toBe(2);
    });

    it("Displays skeletons even if there are backlog_items to show loading indication", async () => {
        const wrapper = await createWrapper({
            is_loading: true,
            has_loading_error: false,
            backlog_items: [{ id: 1 }, { id: 2 }] as BacklogItem[],
        });

        expect(wrapper.findComponent(BacklogItemSkeleton).exists()).toBe(true);
    });

    it("Loads automatically the backlog_items", async () => {
        const $store = createStoreMock({
            state: {
                backlog_item: {
                    is_loading: true,
                    has_loading_error: false,
                    backlog_items: [] as BacklogItem[],
                },
            } as RootState,
        });
        shallowMount(ListOfBacklogItems, {
            localVue: await createTestPlanLocalVue(),
            mocks: {
                $store,
            },
        });

        expect($store.dispatch).toHaveBeenCalledWith("backlog_item/loadBacklogItems");
    });

    it("Displays empty state when there is no backlog_item", async () => {
        const wrapper = await createWrapper({
            is_loading: false,
            has_loading_error: false,
            backlog_items: [] as BacklogItem[],
        });

        expect(wrapper.find("backlog-item-empty-state-stub").exists()).toBe(true);
    });

    it("Does not display empty state when there is no backlog_item but it is still loading", async () => {
        const wrapper = await createWrapper({
            is_loading: true,
            has_loading_error: false,
            backlog_items: [] as BacklogItem[],
        });

        expect(wrapper.find("backlog-item-empty-state-stub").exists()).toBe(false);
    });

    it("Does not display empty state when there are backlog_items", async () => {
        const wrapper = await createWrapper({
            is_loading: false,
            has_loading_error: false,
            backlog_items: [{ id: 1 }] as BacklogItem[],
        });

        expect(wrapper.find("backlog-item-empty-state-stub").exists()).toBe(false);
    });

    it("Does not display empty state when there is an error", async () => {
        const wrapper = await createWrapper({
            is_loading: false,
            has_loading_error: true,
            backlog_items: [] as BacklogItem[],
        });

        expect(wrapper.find("backlog-item-empty-state-stub").exists()).toBe(false);
    });

    it("Displays error state when there is an error", async () => {
        const wrapper = await createWrapper({
            is_loading: false,
            has_loading_error: true,
            backlog_items: [] as BacklogItem[],
        });

        expect(wrapper.find("backlog-item-error-state-stub").exists()).toBe(true);
    });

    it("Does not display error state when there is no error", async () => {
        const wrapper = await createWrapper({
            is_loading: false,
            has_loading_error: false,
            backlog_items: [] as BacklogItem[],
        });

        expect(wrapper.find("backlog-item-error-state-stub").exists()).toBe(false);
    });
});
