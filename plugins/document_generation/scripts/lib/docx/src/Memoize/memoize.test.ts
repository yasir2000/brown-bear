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

import { memoize } from "./memoize";

describe("memoize", () => {
    it("memoizes result value of function", () => {
        const fn = jest.fn((a: string, b: string): string => `${a}:${b}`);
        const memoized_fn = memoize(fn);

        expect(memoized_fn("A", "B")).toStrictEqual("A:B");
        expect(memoized_fn("A", "B")).toStrictEqual("A:B");
        expect(memoized_fn("C", "D")).toStrictEqual("C:D");
        expect(fn).toBeCalledTimes(2);
    });

    it("memoizes errors", () => {
        const fn = jest.fn((a: string): string => {
            throw new Error(a);
        });
        const memoized_fn = memoize(fn);

        expect(() => memoized_fn("A")).toThrow();
        expect(() => memoized_fn("A")).toThrow();
        expect(() => memoized_fn("B")).toThrow();
        expect(fn).toBeCalledTimes(2);
    });

    it("memoizes result value of function without args", () => {
        const fn = jest.fn((): string => "A");
        const memoized_fn = memoize(fn);

        expect(memoized_fn()).toStrictEqual("A");
        expect(memoized_fn()).toStrictEqual("A");
        expect(fn).toBeCalledTimes(1);
    });
});
