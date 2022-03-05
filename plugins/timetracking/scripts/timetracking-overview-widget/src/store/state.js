/*
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
import { DateTime } from "luxon";

const state = {
    report_id: null,
    user_id: null,
    are_void_trackers_hidden: null,
    start_date: DateTime.local().minus({ months: 1 }).toISODate(),
    end_date: DateTime.local().toISODate(),
    error_message: null,
    success_message: null,
    selected_trackers: [],
    trackers_times: [],
    is_loading: false,
    is_loading_trackers: false,
    is_report_saved: true,
    reading_mode: true,
    trackers: [],
    trackers_ids: [],
    projects: [],
    users: [],
    is_added_tracker: true,
    selected_user: null,
};

export default state;
