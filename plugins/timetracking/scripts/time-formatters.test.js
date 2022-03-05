/*
 * Copyright Enalean (c) 2018 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

import { Settings } from "luxon";

import {
    formatMinutes,
    formatDatetimeToISO,
    formatDateDayMonthYear,
    sortTimesChronologically,
} from "./time-formatters.js";

describe("Time formatters", () => {
    describe("formatMinutes", () => {
        it("Given minutes, When I call this function, Then it should format it in a ISO-compliant format", () => {
            const minutes = 600;

            expect(formatMinutes(minutes)).toEqual("10:00");
        });
    });

    describe("getISODatetime", () => {
        it("When I call this method with a string date, then it should return an ISO formatted date", () => {
            Settings.defaultZoneName = "Europe/Paris";
            const formatted_date = formatDatetimeToISO("2018-01-01");

            expect(formatted_date).toEqual("2018-01-01T00:00:00+01:00");
        });
    });

    describe("getFormatedDateYearMonthsDay", () => {
        it("When I call this method with an ISO string date, then it should return a human readable date depending on the user's locale", () => {
            Settings.defaultZoneName = "Europe/Paris";
            const formatted_date = formatDateDayMonthYear("2018-11-11T00:00:00+01:00");

            expect(formatted_date).toEqual("11/11/2018");
        });
    });

    describe("sortTimesChronologically", () => {
        it("When I call this method with times, then it return times sorted on dates", () => {
            const times = [
                {
                    artifact: {},
                    project: {},
                    minutes: 20,
                    date: "2018-03-01",
                },
                {
                    artifact: {},
                    project: {},
                    minutes: 20,
                    date: "2018-02-01",
                },
                {
                    artifact: {},
                    project: {},
                    minutes: 20,
                    date: "2018-01-01",
                },
            ];
            const sorted_times = sortTimesChronologically([times[1], times[0], times[2]]);
            expect(sorted_times).toEqual(times);
        });
    });
});
