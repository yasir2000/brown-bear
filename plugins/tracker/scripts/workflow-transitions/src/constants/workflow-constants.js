/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

const POST_ACTION_TYPE = {
    RUN_JOB: "run_job",
    SET_FIELD_VALUE: "set_field_value",
    FROZEN_FIELDS: "frozen_fields",
    HIDDEN_FIELDSETS: "hidden_fieldsets",
};

const EXTERNAL_POST_ACTION_TYPE = {
    ADD_TO_BACKLOG_AGILE_DASHBOARD: "add_to_top_backlog",
    ADD_TO_BACKLOG_PROGRAM_MANAGEMENT: "program_management_add_to_top_backlog",
};

const DATE_FIELD_VALUE = {
    CLEAR: "",
    CURRENT: "current",
};

export { POST_ACTION_TYPE, DATE_FIELD_VALUE, EXTERNAL_POST_ACTION_TYPE };
