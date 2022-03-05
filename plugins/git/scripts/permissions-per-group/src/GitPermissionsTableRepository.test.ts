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
import GitPermissionsTableRepository from "./GitPermissionsTableRepository.vue";
import GitRepositoryTableSimplePermissions from "./GitRepositoryTableSimplePermissions.vue";
import type {
    FineGrainedPermission,
    RepositoryFineGrainedPermissions,
    RepositorySimplePermissions,
} from "./type";
import GitRepositoryTableFineGrainedPermissionsRepository from "./GitRepositoryTableFineGrainedPermissionsRepository.vue";
import GitRepositoryTableFineGrainedPermission from "./GitRepositoryTableFineGrainedPermission.vue";

describe("GitPermissionsTableRepository", () => {
    const store_options = {};
    let propsData = {};

    function instantiateComponent(): Wrapper<GitPermissionsTableRepository> {
        const store = createStoreMock(store_options);
        return shallowMount(GitPermissionsTableRepository, {
            propsData,
            mocks: { $store: store },
        });
    }

    it("When repository hasn't fine grained permission, Then GitRepositoryTableSimplePermissions is displayed", () => {
        propsData = {
            repository: {
                repository_id: 1,
                has_fined_grained_permissions: false,
            } as RepositorySimplePermissions,
        };

        const wrapper = instantiateComponent();

        expect(wrapper.findComponent(GitRepositoryTableSimplePermissions).exists()).toBeTruthy();
        expect(
            wrapper.findComponent(GitRepositoryTableSimplePermissions).props("repositoryPermission")
        ).toEqual({ repository_id: 1, has_fined_grained_permissions: false });
        expect(
            wrapper.findComponent(GitRepositoryTableFineGrainedPermissionsRepository).exists()
        ).toBeFalsy();
        expect(wrapper.findComponent(GitRepositoryTableFineGrainedPermission).exists()).toBeFalsy();
    });

    it("When repository is hidden and hasn't fine grained permission, Then no components are displayed", async () => {
        propsData = {
            repository: {
                repository_id: 1,
                has_fined_grained_permissions: false,
            } as RepositorySimplePermissions,
        };

        const wrapper = instantiateComponent();

        wrapper.setData({ is_hidden: true });
        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(GitRepositoryTableSimplePermissions).exists()).toBeFalsy();
        expect(
            wrapper.findComponent(GitRepositoryTableFineGrainedPermissionsRepository).exists()
        ).toBeFalsy();
        expect(wrapper.findComponent(GitRepositoryTableFineGrainedPermission).exists()).toBeFalsy();
    });

    it("When repository is hidden and has fine grained permission, Then no components are displayed", async () => {
        propsData = {
            repository: {
                repository_id: 1,
                has_fined_grained_permissions: true,
            } as RepositoryFineGrainedPermissions,
        };

        const wrapper = instantiateComponent();

        wrapper.setData({ is_hidden: true });
        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(GitRepositoryTableSimplePermissions).exists()).toBeFalsy();
        expect(
            wrapper.findComponent(GitRepositoryTableFineGrainedPermissionsRepository).exists()
        ).toBeFalsy();
        expect(wrapper.findComponent(GitRepositoryTableFineGrainedPermission).exists()).toBeFalsy();
    });

    it("When repository has fine grained permission, Then GitRepositoryTableFineGrainedPermissionsRepository is displayed", () => {
        propsData = {
            repository: {
                repository_id: 1,
                has_fined_grained_permissions: true,
                fine_grained_permission: [
                    {
                        id: 101,
                    },
                    {
                        id: 102,
                    },
                ] as FineGrainedPermission[],
            } as RepositoryFineGrainedPermissions,
        };

        const wrapper = instantiateComponent();

        expect(wrapper.findComponent(GitRepositoryTableSimplePermissions).exists()).toBeFalsy();
        expect(
            wrapper.findComponent(GitRepositoryTableFineGrainedPermissionsRepository).exists()
        ).toBeTruthy();
        expect(
            wrapper.find("[data-test=git-repository-fine-grained-permission-101]").exists()
        ).toBeTruthy();
        expect(
            wrapper.find("[data-test=git-repository-fine-grained-permission-102]").exists()
        ).toBeTruthy();
    });
});
