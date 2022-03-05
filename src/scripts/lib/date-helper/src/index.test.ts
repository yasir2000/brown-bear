/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

import { formatFromPhpToMoment, formatDateYearMonthDay } from "./index";

describe("formatFromPhpToMoment", () => {
    it.each([
        ["french date", "d/m/Y", "DD/MM/YYYY"],
        ["english date", "Y-m-d", "YYYY-MM-DD"],
        ["french time", "d/m/Y H:i", "DD/MM/YYYY HH:mm"],
        ["english time", "Y-m-d H:i", "YYYY-MM-DD HH:mm"],
    ])(
        `Given %s is provided, then it returns the moment format`,
        (format_title, php_date_format, expected_moment_format) => {
            expect(formatFromPhpToMoment(php_date_format)).toEqual(expected_moment_format);
        }
    );

    it("Given non supported format is provided, then it throws an Error", () => {
        const php_date_format = "dd/mm/YYYY";

        expect(() => formatFromPhpToMoment(php_date_format)).toThrow(
            "Only french and english date are supported for display"
        );
    });
});

describe("formatDateYearMonthDay", () => {
    it("Given date, When I call this function with an ISO date, then it should return date at good format", () => {
        const date_iso = new Date("2017-01-22T13:42:08+02:00");
        expect(formatDateYearMonthDay("en-US", date_iso.toDateString())).toEqual("Jan 22, 2017");
    });

    it("Given empty string, When I call this function with date null, then it should return empty string", () => {
        expect(formatDateYearMonthDay("en-US", null)).toEqual("");
    });

    it("Given empty string, When I call this function with an empty string, then it should return empty string", () => {
        expect(formatDateYearMonthDay("en-US", "")).toEqual("");
    });
});
