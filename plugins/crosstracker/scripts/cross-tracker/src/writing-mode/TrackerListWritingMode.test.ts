/*
 * Copyright (c) BrownBear, 2018 - Present. All Rights Reserved.
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

import { shallowMount } from "@vue/test-utils";
import type { Wrapper } from "@vue/test-utils";
import type { Tracker } from "../type";
import TrackerListWritingMode from "./TrackerListWritingMode.vue";

describe("TrackerListWritingMode", () => {
    function instantiateComponent(): Wrapper<TrackerListWritingMode> {
        return shallowMount(TrackerListWritingMode, {
            propsData: {
                trackers: [
                    { label: "fake_tracker", id: 1 } as Tracker,
                    { label: "bugs", id: 2 } as Tracker,
                ],
            },
        });
    }
    it("renders a list of trackers", () => {
        const wrapper = instantiateComponent();
        expect(wrapper.element).toMatchSnapshot();
    });

    it("when I remove a tracker, then an event will be emitted", () => {
        const wrapper = instantiateComponent();
        const tracker = { label: "fake_tracker", id: 1 } as Tracker;

        wrapper.get("[data-test=remove-tracker-1]").trigger("click");

        const emitted = wrapper.emitted("tracker-removed");
        if (!emitted) {
            throw new Error("Event has not been emitted");
        }
        expect(emitted[0][0]).toEqual(tracker);
    });
});
