/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
 *
 */

import localVue from "../../../../../helpers/local-vue";
import { shallowMount } from "@vue/test-utils";
import { createStoreMock } from "../../../../../../../../../src/scripts/vue-components/store-wrapper-jest.js";
import CustomPropertyListMultipleValue from "./CustomPropertyListMultipleValue.vue";
import emitter from "../../../../../helpers/emitter";

jest.mock("../../../../../helpers/emitter");

describe("CustomPropertyListMultipleValue", () => {
    let store, factory;
    beforeEach(() => {
        store = createStoreMock({}, { properties: {} });

        factory = (props = {}) => {
            return shallowMount(CustomPropertyListMultipleValue, {
                localVue,
                propsData: { ...props },
                mocks: { $store: store },
            });
        };
    });

    it(`Given a list property
        Then it renders only the possible values of this list property`, async () => {
        store.state.properties = {
            project_properties: [
                {
                    short_name: "list",
                    allowed_list_values: [
                        { id: 100, value: "None" },
                        { id: 101, value: "abcde" },
                        { id: 102, value: "fghij" },
                    ],
                },
                {
                    short_name: "an other list",
                    allowed_list_values: [{ id: 100, value: "None" }],
                },
            ],
        };

        const currentlyUpdatedItemProperty = {
            short_name: "list",
            name: "custom list",
            list_value: [101],
            is_required: false,
            type: "list",
            is_multiple_value_allowed: true,
        };
        const wrapper = factory({ currentlyUpdatedItemProperty });

        await wrapper.vm.$nextTick().then(() => {});

        const all_options = wrapper
            .get("[data-test=document-custom-list-multiple-select]")
            .findAll("option");

        expect(all_options.length).toBe(3);
        expect(
            wrapper.find("[data-test=document-custom-list-multiple-value-100]").exists()
        ).toBeTruthy();
        expect(
            wrapper.find("[data-test=document-custom-list-multiple-value-101]").exists()
        ).toBeTruthy();
        expect(
            wrapper.find("[data-test=document-custom-list-multiple-value-102]").exists()
        ).toBeTruthy();
    });
    it(`Given a list property is required
        Then the input is required`, () => {
        store.state.properties = {
            project_properties: [
                {
                    short_name: "list",
                    allowed_list_values: [{ id: 101, value: "abcde" }],
                },
            ],
        };

        const currentlyUpdatedItemProperty = {
            short_name: "list",
            name: "custom list",
            list_value: [101],
            is_required: true,
            type: "list",
            is_multiple_value_allowed: true,
        };

        const wrapper = factory({ currentlyUpdatedItemProperty });
        expect(
            wrapper.find("[data-test=document-custom-list-multiple-select]").exists()
        ).toBeTruthy();

        expect(
            wrapper.find("[data-test=document-custom-property-is-required]").exists()
        ).toBeTruthy();
    });

    it(`Given a list property is updated
        Then the binding is updated as well`, () => {
        store.state.properties = {
            project_properties: [
                {
                    short_name: "list",
                    allowed_list_values: [
                        { id: 100, value: "None" },
                        { id: 101, value: "abcde" },
                        { id: 102, value: "fghij" },
                    ],
                },
            ],
        };
        const currentlyUpdatedItemProperty = {
            short_name: "list",
            name: "custom list",
            list_value: [101],
            is_required: true,
            type: "list",
            is_multiple_value_allowed: true,
        };

        const wrapper = factory({ currentlyUpdatedItemProperty });

        wrapper.vm.multiple_list_values.values = [100, 102];
        expect(wrapper.vm.currentlyUpdatedItemProperty.list_value.values).toEqual([100, 102]);
        expect(wrapper.vm.$data.project_properties_list_possible_values).toEqual(
            store.state.properties.project_properties[0]
        );
    });

    it(`DOES NOT renders the component when there is only one value allowed for the list`, () => {
        store.state.properties = {
            project_properties: [
                {
                    short_name: "list",
                    allowed_list_values: [
                        { id: 100, value: "None" },
                        { id: 101, value: "abcde" },
                        { id: 102, value: "fghij" },
                    ],
                },
            ],
        };

        const currentlyUpdatedItemProperty = {
            short_name: "list",
            name: "custom list",
            value: 101,
            is_required: true,
            type: "list",
            is_multiple_value_allowed: false,
        };

        const wrapper = factory({ currentlyUpdatedItemProperty });
        expect(
            wrapper.find("[data-test=document-custom-property-list-multiple]").exists()
        ).toBeFalsy();
        expect(wrapper.vm.$data.project_properties_list_possible_values).toEqual([]);
    });

    it(`does not render the component when type does not match`, () => {
        store.state.properties = {
            project_properties: [
                {
                    short_name: "text",
                    allowed_list_values: [
                        { id: 100, value: "None" },
                        { id: 101, value: "abcde" },
                        { id: 102, value: "fghij" },
                    ],
                },
            ],
        };

        const currentlyUpdatedItemProperty = {
            short_name: "text",
            name: "custom text",
            value: 101,
            is_required: true,
            type: "text",
            is_multiple_value_allowed: true,
        };

        const wrapper = factory({ currentlyUpdatedItemProperty });
        expect(
            wrapper.find("[data-test=document-custom-property-list-multiple]").exists()
        ).toBeFalsy();
    });

    it(`throws an event when list value is changed`, () => {
        store.state.properties = {
            project_properties: [
                {
                    short_name: "list",
                    allowed_list_values: [
                        { id: 100, value: "None" },
                        { id: 101, value: "abcde" },
                        { id: 102, value: "fghij" },
                    ],
                },
            ],
        };
        const currentlyUpdatedItemProperty = {
            short_name: "list",
            name: "custom list",
            list_value: [101],
            is_required: true,
            type: "list",
            is_multiple_value_allowed: true,
        };

        const wrapper = factory({ currentlyUpdatedItemProperty });

        wrapper.vm.updatePropertiesListValue();

        expect(emitter.emit).toHaveBeenCalledWith("update-multiple-properties-list-value", {
            detail: {
                value: wrapper.vm.multiple_list_values,
                id: "list",
            },
        });
    });
});
