/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

import { postInterpretCommonMark } from "./tuleap-api";
import type { FetchWrapperError } from "tlp";
import * as tlp from "tlp";

jest.mock("tlp");

describe(`tuleap-api`, () => {
    describe(`postInterpretCommonMark()`, () => {
        const markdown_string = "**Markdown** content";
        const project_id = 110;

        it(`will POST the source markdown to the interpret-commonmark route
            and will return the rendered HTML string`, async () => {
            const html_string = "<p><strong>Markdown</strong> content</p>";

            const post = jest.spyOn(tlp, "post").mockResolvedValue({
                text: () => Promise.resolve(html_string),
            } as Response);

            const result = await postInterpretCommonMark(markdown_string, project_id);

            expect(result).toEqual(html_string);
            expect(post).toHaveBeenCalledWith(
                `/project/110/interpret-commonmark`,
                expect.anything()
            );
            const options = post.mock.calls[0][1];
            if (!options) {
                throw new Error("Expected post() to have been called with options");
            }
            const body = options.body;
            if (!(body instanceof FormData)) {
                throw new Error("Expected post() to have been called with a body as FormData");
            }
            expect(body.get("content")).toEqual(markdown_string);
        });

        it(`when there is an error, it will return it`, async () => {
            const error = {
                response: {
                    text: () => Promise.resolve("Internal Server Error"),
                } as Response,
            } as FetchWrapperError;

            jest.spyOn(tlp, "post").mockRejectedValue(error);

            await expect(() =>
                postInterpretCommonMark(markdown_string, project_id)
            ).rejects.toThrowError("Internal Server Error");
        });
    });
});
