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

import type { HostElement } from "./FloatField";
import { onInput } from "./FloatField";

const FIELD_ID = 87;

const noop = (): void => {
    //Do nothing
};

const getDocument = (): Document => document.implementation.createHTMLDocument();

function getHost(): HostElement {
    return {
        fieldId: FIELD_ID,
        label: "Float Field",
        required: false,
        disabled: false,
        value: 0,
        dispatchEvent: noop,
    } as unknown as HostElement;
}

describe(`FloatField`, () => {
    it.each([
        ["when the input is emptied", "empty string", "", ""],
        ["when the input's value is a number", "the number", "26.79", 26.79],
        ["when the input's value is not a number", "empty string", "not a number", ""],
    ])(
        `%s, it dispatches a "value-changed" event with value as %s`,
        (when_statement, expected_statement, input_value, expected_manual_value) => {
            const host = getHost();
            const dispatchEvent = jest.spyOn(host, "dispatchEvent");
            const inner_input = getDocument().createElement("input");
            inner_input.addEventListener("input", (event) => onInput(host, event));

            inner_input.value = input_value;
            inner_input.dispatchEvent(new InputEvent("input"));

            const event = dispatchEvent.mock.calls[0][0];
            if (!(event instanceof CustomEvent)) {
                throw new Error("Expected a CustomEvent");
            }
            expect(event.type).toBe("value-changed");
            expect(event.detail.field_id).toBe(FIELD_ID);
            expect(event.detail.value).toBe(expected_manual_value);
        }
    );
});
