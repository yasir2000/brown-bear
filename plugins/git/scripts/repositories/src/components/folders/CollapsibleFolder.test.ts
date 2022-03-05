/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
import { createLocalVue, shallowMount } from "@vue/test-utils";
import CollapsibleFolder from "./CollapsibleFolder.vue";
import VueDOMPurifyHTML from "vue-dompurify-html";
import GetTextPlugin from "vue-gettext";
import type { Folder, Repository } from "../../type";
import GitRepository from "../GitRepository.vue";

describe("CollapsibleFolder", () => {
    function instantiateComponent(propsData: {
        is_root_folder?: boolean;
        is_base_folder?: boolean;
        children: Array<Folder | Repository>;
        label?: string;
    }): Wrapper<CollapsibleFolder> {
        const localVue = createLocalVue();
        localVue.use(VueDOMPurifyHTML);
        localVue.use(GetTextPlugin, {
            translations: {},
            silent: true,
        });

        return shallowMount(CollapsibleFolder, {
            propsData,
            mocks: {
                $store: createStoreMock({
                    state: {},
                    getters: {},
                }),
            },
            localVue,
        });
    }

    it("When folder is root, Then there are not icon and label", () => {
        const wrapper = instantiateComponent({
            is_root_folder: true,
            is_base_folder: false,
            children: [],
        });

        expect(wrapper.find("[data-test=git-repository-list-folder-icon]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=git-repository-list-folder-label]").exists()).toBeFalsy();
    });

    it("When folder is not root, Then there are icon and label and icon changes on click", async () => {
        const wrapper = instantiateComponent({
            is_root_folder: false,
            is_base_folder: true,
            label: "Repositories",
            children: [],
        });

        expect(wrapper.find("[data-test=git-repository-list-folder-icon]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=git-repository-list-folder-icon]").classes()).toContain(
            "fa-caret-down"
        );

        expect(wrapper.find("[data-test=git-repository-list-folder-label]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=git-repository-list-folder-label]").text()).toEqual(
            "Repositories"
        );

        wrapper.find("[data-test=git-repository-list-folder-collapse]").trigger("click");
        await wrapper.vm.$nextTick();

        expect(wrapper.find("[data-test=git-repository-list-folder-icon]").classes()).toContain(
            "fa-caret-right"
        );
    });

    it("When children is repository, Then GitRepository is rendered", () => {
        const wrapper = instantiateComponent({
            is_root_folder: true,
            children: [{ id: 10 } as Repository],
        });

        expect(wrapper.findComponent(GitRepository).exists()).toBeTruthy();
        expect(wrapper.find("[data-test=git-repository-collapsible-folder]").exists()).toBeFalsy();
    });

    it("When children is folder, Then CollapsibleFolder is rendered", () => {
        const wrapper = instantiateComponent({
            is_root_folder: true,
            children: [{ label: "folder", is_folder: true } as Folder],
        });

        expect(wrapper.findComponent(GitRepository).exists()).toBeFalsy();
        expect(wrapper.find("[data-test=git-repository-collapsible-folder]").exists()).toBeTruthy();
    });
});
