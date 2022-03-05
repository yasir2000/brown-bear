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

import Vue from "vue";
import VueDOMPurifyHTML from "vue-dompurify-html";
import App from "./src/components/App.vue";
import { createStore } from "./src/store";
import { setUserLocale } from "./src/helpers/user-locale-helper";
import {
    initVueGettext,
    getPOFileFromLocale,
} from "../../../../src/scripts/tuleap/gettext/vue-gettext-init";
import type { TrackerAgileDashboard } from "./src/type";
import { COUNT, EFFORT } from "./src/type";

document.addEventListener("DOMContentLoaded", async () => {
    Vue.use(VueDOMPurifyHTML);

    const locale = document.body.dataset.userLocale;
    if (locale !== undefined) {
        Vue.config.language = locale;
        setUserLocale(locale.replace("_", "-"));
    }
    await initVueGettext(
        Vue,
        (locale: string) =>
            import(
                /* webpackChunkName: "projectmilestones-po-" */ "./po/" +
                    getPOFileFromLocale(locale)
            )
    );

    const widgets: NodeListOf<HTMLElement> = document.querySelectorAll(".projectmilestones");

    for (const widget of widgets) {
        const project_id_dataset = widget.dataset.projectId;
        const project_name = widget.dataset.projectName;
        const nb_upcoming_releases_dataset = widget.dataset.nbUpcomingReleases;
        const nb_backlog_items_dataset = widget.dataset.nbBacklogItems;
        const trackers_agile_dashboard_dataset = widget.dataset.jsonTrackersAgileDashboard;
        const label_tracker_planning = widget.dataset.labelTrackerPlanning;
        const is_timeframe_duration_dataset = widget.dataset.isTimeframeDuration;
        const label_start_date = widget.dataset.labelStartDate;
        const label_timeframe = widget.dataset.labelTimeframe;
        const user_can_view_sub_milestones_planning_dataset =
            widget.dataset.userCanViewSubMilestonesPlanning;
        const burnup_mode = widget.dataset.burnupMode;

        if (!project_id_dataset) {
            throw new Error("Project Id is missing.");
        }

        if (!project_name) {
            throw new Error("Project Name is missing.");
        }

        if (!nb_upcoming_releases_dataset) {
            throw new Error("Number Upcoming Releases is missing.");
        }

        if (!nb_backlog_items_dataset) {
            throw new Error("Number Backlog Items is missing.");
        }

        if (!trackers_agile_dashboard_dataset) {
            throw new Error("Trackers Agile Dashboard is missing.");
        }

        if (!label_tracker_planning) {
            throw new Error("Label Tracker Planning is missing.");
        }

        if (!label_start_date) {
            throw new Error("Label Start Date is missing.");
        }

        if (!label_timeframe) {
            throw new Error("Label Timeframe is missing.");
        }

        if (!burnup_mode) {
            throw new Error("Mode Burnup is missing.");
        }

        const project_id = Number.parseInt(project_id_dataset, 10);
        const nb_upcoming_releases = Number.parseInt(nb_upcoming_releases_dataset, 10);
        const nb_backlog_items = Number.parseInt(nb_backlog_items_dataset, 10);
        const trackers_agile_dashboard: TrackerAgileDashboard[] = JSON.parse(
            trackers_agile_dashboard_dataset
        );
        const is_timeframe_duration = Boolean(is_timeframe_duration_dataset);
        const user_can_view_sub_milestones_planning = Boolean(
            user_can_view_sub_milestones_planning_dataset
        );

        const AppComponent = Vue.extend(App);

        new AppComponent({
            store: createStore(
                project_id,
                project_name,
                nb_upcoming_releases,
                nb_backlog_items,
                trackers_agile_dashboard,
                label_tracker_planning,
                is_timeframe_duration,
                label_start_date,
                label_timeframe,
                user_can_view_sub_milestones_planning,
                burnup_mode === "count" ? COUNT : EFFORT
            ),
        }).$mount(widget);
    }
});
