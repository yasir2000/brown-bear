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
 */

import Vue from "vue";
import type { Store } from "vuex";
import Vuex from "vuex";
import type { RootState, State } from "./type";
import mutations from "./mutations";
import * as actions from "./actions";
import * as getters from "./getters";
import type { ConfigurationState } from "./configuration";
import { createConfigurationModule } from "./configuration";

Vue.use(Vuex);

export function createStore(
    root_state: RootState,
    configuration_state: ConfigurationState
): Store<State> {
    const configuration = createConfigurationModule(configuration_state);

    return new Vuex.Store({
        state: root_state,
        mutations,
        actions,
        getters,
        modules: {
            configuration,
        },
    });
}
