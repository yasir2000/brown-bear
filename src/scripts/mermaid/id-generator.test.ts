/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import crypto from "crypto";
import { generateMermaidElementId } from "./id-generator";

describe("generateMermaidElementId", () => {
    it("Generates unique id", () => {
        Object.defineProperty(window, "crypto", {
            value: {
                getRandomValues: (array: Uint8Array): Buffer => crypto.randomBytes(array.length),
            },
        });

        const an_id = generateMermaidElementId();
        const another_id = generateMermaidElementId();
        expect(an_id).not.toBe(another_id);
    });
});
