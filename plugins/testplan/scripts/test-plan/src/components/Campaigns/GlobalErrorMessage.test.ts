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
import GlobalErrorMessage from "./GlobalErrorMessage.vue";
import { createStoreMock } from "../../../../../../../src/scripts/vue-components/store-wrapper-jest";
import type { RootState } from "../../store/type";
import type { CampaignState } from "../../store/campaign/type";

describe("GlobalErrorMessage", () => {
    async function createWrapper(campaign: CampaignState): Promise<Wrapper<GlobalErrorMessage>> {
        return shallowMount(GlobalErrorMessage, {
            localVue: await createTestPlanLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: {
                        campaign,
                    } as RootState,
                }),
            },
        });
    }

    it("displays nothing if there is no error", async () => {
        const wrapper = await createWrapper({
            has_refreshing_error: false,
        } as CampaignState);

        expect(wrapper.element).toMatchInlineSnapshot(`<!---->`);
    });

    it("displays error message when a campaign cannot be refreshed", async () => {
        const wrapper = await createWrapper({
            has_refreshing_error: true,
        } as CampaignState);

        expect(wrapper.element).toMatchSnapshot();
    });
});
