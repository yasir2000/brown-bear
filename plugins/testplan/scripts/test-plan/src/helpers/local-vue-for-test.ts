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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import type { Vue } from "vue/types/vue";
import Vuex from "vuex";
import { createLocalVue } from "@vue/test-utils";
import { initVueGettext } from "../../../../../../src/scripts/tuleap/gettext/vue-gettext-init";
import VueCompositionAPI from "@vue/composition-api";

export async function createTestPlanLocalVue(): Promise<typeof Vue> {
    const local_vue = createLocalVue();
    local_vue.use(VueCompositionAPI);
    await initVueGettext(local_vue, () => {
        throw new Error("Fallback to default");
    });
    local_vue.use(Vuex);

    return local_vue;
}