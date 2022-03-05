/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
import { createCrossTrackerLocalVue } from "../helpers/local-vue-for-test";
import WritingMode from "./WritingMode.vue";
import {
    default as WritingCrossTrackerReport,
    TooManyTrackersSelectedError,
} from "./writing-cross-tracker-report";
import * as rest_querier from "../api/rest-querier";
import { createStoreMock } from "../../../../../../src/scripts/vue-components/store-wrapper-jest";
import type { Project, Tracker, State } from "../type";
import TrackerListWritingMode from "./TrackerListWritingMode.vue";
import TrackerSelection from "./TrackerSelection.vue";

describe("WritingMode", () => {
    let store = {
        commit: jest.fn(),
    };

    beforeEach(() => {
        jest.spyOn(rest_querier, "getSortedProjectsIAmMemberOf").mockResolvedValue([
            { id: 102 } as Project,
        ]);
    });

    async function instantiateComponent(
        writingCrossTrackerReport: WritingCrossTrackerReport
    ): Promise<Wrapper<WritingMode>> {
        const store_options = { state: { is_user_admin: true } as State, commit: jest.fn() };
        store = createStoreMock(store_options);

        return shallowMount(WritingMode, {
            localVue: await createCrossTrackerLocalVue(),
            propsData: {
                writingCrossTrackerReport,
            },
            mocks: { $store: store },
        });
    }

    describe("mounted()", () => {
        it("on init, the selected trackers will be formatted from the writing report", async () => {
            const writingCrossTrackerReport = new WritingCrossTrackerReport();
            writingCrossTrackerReport.addTracker(
                { id: 804, label: "fanatical" } as Project,
                { id: 29, label: "charry" } as Tracker
            );
            writingCrossTrackerReport.addTracker(
                { id: 146, label: "surly" } as Project,
                { id: 51, label: "monodynamism" } as Tracker
            );

            const wrapper = await instantiateComponent(writingCrossTrackerReport);

            expect(wrapper.vm.$data.selected_trackers).toEqual([
                {
                    tracker_id: 29,
                    tracker_label: "charry",
                    project_label: "fanatical",
                },
                {
                    tracker_id: 51,
                    tracker_label: "monodynamism",
                    project_label: "surly",
                },
            ]);
        });
    });

    describe("cancel()", () => {
        it("when I hit cancel, then an event will be emitted to switch the widget to reading mode in saved state", async () => {
            const writingCrossTrackerReport = new WritingCrossTrackerReport();
            const wrapper = await instantiateComponent(writingCrossTrackerReport);

            wrapper.find("[data-test=writing-mode-cancel-button]").trigger("click");
            const emitted = wrapper.emitted()["switch-to-reading-mode"];
            if (!emitted) {
                throw new Error("Event has not been emitted");
            }

            expect(emitted[0][0]).toEqual({ saved_state: true });
        });
    });

    describe("search()", () => {
        it("when I hit search, then an event will be emitted to switch the widget to reading mode in unsaved state", async () => {
            const writingCrossTrackerReport = new WritingCrossTrackerReport();
            const wrapper = await instantiateComponent(writingCrossTrackerReport);

            wrapper.find("[data-test=search-report-button]").trigger("click");
            const emitted = wrapper.emitted()["switch-to-reading-mode"];
            if (!emitted) {
                throw new Error("Event has not been emitted");
            }

            expect(emitted[0][0]).toEqual({ saved_state: false });
        });
    });

    describe("removeTrackerFromSelection()", () => {
        it("when I remove a tracker, then the writing report will be updated and the errors hidden", async () => {
            const writingCrossTrackerReport = new WritingCrossTrackerReport();
            writingCrossTrackerReport.addTracker(
                { id: 172, label: "undiuretic" } as Project,
                { id: 61, label: "Dipneumona" } as Tracker
            );
            writingCrossTrackerReport.addTracker(
                { id: 288, label: "defectless" } as Project,
                { id: 46, label: "knothorn" } as Tracker
            );
            jest.spyOn(writingCrossTrackerReport, "removeTracker");
            const wrapper = await instantiateComponent(writingCrossTrackerReport);

            wrapper
                .findComponent(TrackerListWritingMode)
                .vm.$emit("tracker-removed", { tracker_id: 46 });

            expect(writingCrossTrackerReport.removeTracker).toHaveBeenCalledWith(46);
            expect(wrapper.vm.$store.commit).toHaveBeenCalledWith("resetFeedbacks");
            expect(wrapper.vm.$data.selected_trackers).toEqual([
                {
                    tracker_id: 61,
                    tracker_label: "Dipneumona",
                    project_label: "undiuretic",
                },
            ]);
        });
    });

    describe("addTrackerToSelection()", () => {
        it("when I add a tracker, then the writing report will be updated", async () => {
            const writingCrossTrackerReport = new WritingCrossTrackerReport();
            jest.spyOn(writingCrossTrackerReport, "addTracker");
            const wrapper = await instantiateComponent(writingCrossTrackerReport);
            const selected_project = { id: 656, label: "ergatogyne" } as Project;
            const selected_tracker = { id: 53, label: "observingly" } as Tracker;

            wrapper.findComponent(TrackerSelection).vm.$emit("tracker-added", {
                selected_project,
                selected_tracker,
            });

            expect(writingCrossTrackerReport.addTracker).toHaveBeenCalledWith(
                selected_project,
                selected_tracker
            );
            expect(wrapper.vm.$data.selected_trackers).toEqual([
                {
                    tracker_id: 53,
                    tracker_label: "observingly",
                    project_label: "ergatogyne",
                },
            ]);
        });

        it("Given I had already added 10 trackers, when I try to add another, then an error will be shown", async () => {
            const writingCrossTrackerReport = new WritingCrossTrackerReport();
            jest.spyOn(writingCrossTrackerReport, "addTracker").mockImplementation(() => {
                throw new TooManyTrackersSelectedError();
            });
            const wrapper = await instantiateComponent(writingCrossTrackerReport);
            const selected_project = { id: 656, label: "ergatogyne" } as Project;
            const selected_tracker = { id: 53, label: "observingly" } as Tracker;

            wrapper.findComponent(TrackerSelection).vm.$emit("tracker-added", {
                selected_project,
                selected_tracker,
            });

            expect(wrapper.vm.$store.commit).toHaveBeenCalledWith(
                "setErrorMessage",
                expect.any(String)
            );
        });
    });
});
