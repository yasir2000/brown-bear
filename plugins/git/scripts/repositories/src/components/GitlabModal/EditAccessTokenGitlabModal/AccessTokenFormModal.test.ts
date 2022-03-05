/**
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

import { createStoreMock } from "@tuleap/core/scripts/vue-components/store-wrapper-jest";
import type { Wrapper } from "@vue/test-utils";
import { createLocalVue, shallowMount } from "@vue/test-utils";
import AccessTokenFormModal from "./AccessTokenFormModal.vue";
import VueDOMPurifyHTML from "vue-dompurify-html";
import GetTextPlugin from "vue-gettext";
import type { Store } from "vuex-mock-store";

describe("AccessTokenFormModal", () => {
    let store_options = {},
        propsData = {},
        localVue,
        store: Store;

    beforeEach(() => {
        store_options = {
            state: {},
            getters: {},
        };
    });

    function instantiateComponent(): Wrapper<AccessTokenFormModal> {
        localVue = createLocalVue();
        localVue.use(VueDOMPurifyHTML);
        localVue.use(GetTextPlugin, {
            translations: {},
            silent: true,
        });

        store = createStoreMock(store_options, { gitlab: {} });

        return shallowMount(AccessTokenFormModal, {
            propsData,
            mocks: { $store: store },
            localVue,
        });
    }

    it("When the user check token, Then the submit button is disabled and icon spin is displayed and api is called", async () => {
        propsData = {
            repository: {
                gitlab_data: {
                    gitlab_repository_url: "https://example.com/my/repo",
                    gitlab_repository_id: 12,
                },
                normalized_path: "my/repo",
            },
            gitlab_token: "",
        };

        const wrapper = instantiateComponent();
        expect(wrapper.find("[data-test=icon-spin]").exists()).toBeFalsy();

        wrapper.setData({
            gitlab_new_token: "AFREZF546",
        });

        await wrapper.vm.$nextTick();

        wrapper
            .find("[data-test=edit-token-gitlab-repository-modal-form]")
            .trigger("submit.prevent");
        await wrapper.vm.$nextTick();

        expect(
            wrapper.find("[data-test=button-check-new-token-gitlab-repository]").attributes()
                .disabled
        ).toBeTruthy();
        expect(wrapper.find("[data-test=icon-spin]").exists()).toBeTruthy();

        expect(store.dispatch).toHaveBeenCalledWith("gitlab/getGitlabRepositoryFromId", {
            credentials: {
                server_url: "https://example.com/",
                token: "AFREZF546",
            },
            id: 12,
        });

        const on_get_new_token = wrapper.emitted()["on-get-new-token-gitlab"];
        if (!on_get_new_token) {
            throw new Error("Should have emitted on-get-new-token");
        }

        expect(on_get_new_token[0]).toEqual([{ token: "AFREZF546" }]);
    });

    it("When there is an error message, Then it's displayed", async () => {
        propsData = {
            repository: {
                gitlab_data: {
                    gitlab_repository_url: "https://example.com/my/repo",
                    gitlab_repository_id: 12,
                },
                normalized_path: "my/repo",
            },
            gitlab_token: "",
        };

        const wrapper = instantiateComponent();

        wrapper.setData({
            error_message: "Error message",
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.find("[data-test=gitlab-fail-check-new-token]").text()).toEqual(
            "Error message"
        );
    });

    it("When there are no token and server url, Then submit button is disabled", async () => {
        propsData = {
            repository: {
                gitlab_data: {
                    gitlab_repository_url: "https://example.com/my/repo",
                    gitlab_repository_id: 12,
                },
                normalized_path: "my/repo",
            },
            gitlab_token: "",
        };

        const wrapper = instantiateComponent();
        wrapper.setData({
            gitlab_new_token: "",
        });

        await wrapper.vm.$nextTick();

        expect(
            wrapper.find("[data-test=button-check-new-token-gitlab-repository]").attributes()
                .disabled
        ).toBeTruthy();
    });

    it("When user submit but token is empty, Then error message is displayed", async () => {
        propsData = {
            repository: {
                gitlab_data: {
                    gitlab_repository_url: "https://example.com/my/repo",
                    gitlab_repository_id: 12,
                },
                normalized_path: "my/repo",
            },
            gitlab_token: "",
        };

        const wrapper = instantiateComponent();

        wrapper.setData({
            gitlab_new_token: "",
        });

        await wrapper.vm.$nextTick();

        wrapper
            .find("[data-test=edit-token-gitlab-repository-modal-form]")
            .trigger("submit.prevent");
        await wrapper.vm.$nextTick();

        expect(wrapper.find("[data-test=gitlab-fail-check-new-token]").text()).toEqual(
            "You must provide a valid GitLab API token"
        );
    });

    it("When api throws an error, Then error message is displayed", async () => {
        propsData = {
            repository: {
                gitlab_data: {
                    gitlab_repository_url: "https://example.com/my/repo",
                    gitlab_repository_id: 12,
                },
                normalized_path: "my/repo",
            },
            gitlab_token: "",
        };

        const wrapper = instantiateComponent();
        jest.spyOn(store, "dispatch").mockReturnValue(Promise.reject());

        wrapper.setData({
            gitlab_new_token: "AZERTY123",
        });

        await wrapper.vm.$nextTick();

        wrapper
            .find("[data-test=edit-token-gitlab-repository-modal-form]")
            .trigger("submit.prevent");
        await wrapper.vm.$nextTick();

        expect(wrapper.vm.$data.error_message).toEqual(
            "Submitted token is invalid to access to this repository on this GitLab server."
        );
    });
    it("When user cancel, Then data are reset", async () => {
        propsData = {
            repository: {
                gitlab_data: {
                    gitlab_repository_url: "https://example.com/my/repo",
                    gitlab_repository_id: 12,
                },
                normalized_path: "my/repo",
            },
            gitlab_token: "",
        };

        const wrapper = instantiateComponent();
        wrapper.setData({
            gitlab_new_token: "AZERTY123",
            error_message: "Error",
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.vm.$data.gitlab_new_token).toEqual("AZERTY123");
        expect(wrapper.vm.$data.error_message).toEqual("Error");

        wrapper.find("[data-test=button-cancel-new-token-gitlab-repository]").trigger("click");
        await wrapper.vm.$nextTick();

        expect(wrapper.vm.$data.gitlab_new_token).toEqual("");
        expect(wrapper.vm.$data.error_message).toEqual("");

        const on_close_modal = wrapper.emitted()["on-close-modal"];
        if (!on_close_modal) {
            throw new Error("Should have emitted on-close-modal");
        }

        expect(on_close_modal[0]).toStrictEqual([]);
    });
});
