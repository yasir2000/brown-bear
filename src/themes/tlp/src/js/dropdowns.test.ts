/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

import type { Dropdown } from "./dropdowns";
import {
    createDropdown,
    EVENT_TLP_DROPDOWN_SHOWN,
    EVENT_TLP_DROPDOWN_HIDDEN,
    DROPDOWN_MENU_CLASS_NAME,
    DROPDOWN_SHOWN_CLASS_NAME,
} from "./dropdowns";

jest.useFakeTimers();

describe(`Dropdowns`, () => {
    let trigger_element: HTMLElement, dropdown_element: HTMLElement;
    let doc: Document;

    beforeEach(() => {
        doc = createLocalDocument();
        trigger_element = doc.createElement("button");
        dropdown_element = doc.createElement("div");
        dropdown_element.classList.add(DROPDOWN_MENU_CLASS_NAME);
        doc.body.append(trigger_element, dropdown_element);
    });

    afterEach(() => {
        trigger_element.remove();
        dropdown_element.remove();
    });

    describe(`constructor`, () => {
        it(`will iterate on the trigger element's siblings
            until it finds the dropdown menu`, () => {
            trigger_element.insertAdjacentHTML(
                "afterend",
                `<div></div><!-- comment element --><div></div>`
            );

            const dropdown = createDropdown(doc, trigger_element);
            dropdown.show();
            expectTheDropdownToBeShown(dropdown_element);

            doc.body.innerHTML = "";
        });

        it(`will throw when it can't find the dropdown menu among its siblings`, () => {
            dropdown_element.remove();

            expect(() => createDropdown(doc, trigger_element)).toThrow(
                "Could not find .tlp-dropdown-menu"
            );
        });

        it(`will throw when the dropdown menu is not an HTML element`, () => {
            const svg_element = document.createElementNS("http://www.w3.org/2000/svg", "svg");

            expect(() =>
                createDropdown(doc, trigger_element, { dropdown_menu: svg_element })
            ).toThrow("Dropdown menu must be an HTML element");

            svg_element.remove();
        });

        it(`will use the dropdown menu element passed in its options`, () => {
            const dropdown = createDropdown(doc, trigger_element, {
                dropdown_menu: dropdown_element,
            });
            dropdown.show();
            expectTheDropdownToBeShown(dropdown_element);
        });
    });

    describe(`show()`, () => {
        let dropdown: Dropdown;
        beforeEach(() => {
            dropdown = createDropdown(doc, trigger_element);
        });

        it(`will add the "shown" CSS class to the dropdown menu element`, () => {
            dropdown.show();

            expectTheDropdownToBeShown(dropdown_element);
        });

        it(`will dispatch the "shown" event`, () => {
            let event_dispatched = false;
            dropdown.addEventListener(EVENT_TLP_DROPDOWN_SHOWN, () => {
                event_dispatched = true;
            });
            dropdown.show();
            expect(event_dispatched).toBe(true);
        });
    });

    describe(`hide()`, () => {
        let dropdown: Dropdown;
        beforeEach(() => {
            dropdown = createDropdown(doc, trigger_element);
            dropdown.show();
        });

        it(`will remove the "shown" CSS class from the dropdown menu element`, () => {
            dropdown.hide();
            expectTheDropdownToBeHidden(dropdown_element);
        });

        it(`will dispatch the "hidden" event after a delay`, () => {
            let event_dispatched = false;
            dropdown.addEventListener(EVENT_TLP_DROPDOWN_HIDDEN, () => {
                event_dispatched = true;
            });

            dropdown.hide();
            jest.runAllTimers();
            expect(event_dispatched).toBe(true);
        });
    });

    describe(`toggle()`, () => {
        let dropdown: Dropdown;
        beforeEach(() => {
            dropdown = createDropdown(doc, trigger_element);
        });

        it(`when the dropdown is hidden, it will show it`, () => {
            dropdown.toggle();

            expectTheDropdownToBeShown(dropdown_element);
        });

        it(`when the dropdown is shown, it will hide it`, () => {
            dropdown.show();
            dropdown.toggle();

            expectTheDropdownToBeHidden(dropdown_element);
        });
    });

    it(`when I click on the trigger element, it will show the dropdown`, () => {
        const dropdown = createDropdown(doc, trigger_element);
        dropdown.show();
        simulateClick(trigger_element);

        expectTheDropdownToBeHidden(dropdown_element);
    });

    describe(`close events`, () => {
        let dropdown: Dropdown;
        beforeEach(() => {
            dropdown = createDropdown(doc, trigger_element);
        });

        it(`when the dropdown is already hidden and I click outside of it, nothing happens`, () => {
            simulateClick(doc.body);
            expectTheDropdownToBeHidden(dropdown_element);
        });

        describe(`when the dropdown is shown`, () => {
            beforeEach(() => {
                dropdown.show();
            });

            it(`and I click on something that is not an Element, it won't close it`, () => {
                const text_in_dropdown = document.createTextNode("Some text");
                dropdown_element.append(text_in_dropdown);

                simulateClick(text_in_dropdown);
                expectTheDropdownToBeShown(dropdown_element);
            });

            it(`and I click inside the dropdown, it won't close it`, () => {
                const p_in_dropdown = doc.createElement("p");
                p_in_dropdown.innerText = "I am a paragraph";
                dropdown_element.append(p_in_dropdown);

                simulateClick(p_in_dropdown);
                expectTheDropdownToBeShown(dropdown_element);
            });

            it(`and I click inside the trigger, it will close the dropdown`, () => {
                simulateClick(trigger_element);
                expectTheDropdownToBeHidden(dropdown_element);
            });

            it(`and I click outside of the dropdown, it will close it`, () => {
                simulateClick(doc.body);
                expectTheDropdownToBeHidden(dropdown_element);
            });
        });
    });

    describe(`removeEventListener`, () => {
        it(`removes a listener from the dropdown`, () => {
            const dropdown = createDropdown(doc, trigger_element);
            const listener = jest.fn();
            dropdown.addEventListener(EVENT_TLP_DROPDOWN_HIDDEN, listener);
            dropdown.show();

            dropdown.removeEventListener(EVENT_TLP_DROPDOWN_HIDDEN, listener);
            dropdown.hide();
            expect(listener).not.toHaveBeenCalled();
        });
    });

    describe(`when the dropdown has the keyboard option`, () => {
        let dropdown: Dropdown;
        beforeEach(() => {
            dropdown = createDropdown(doc, trigger_element);
            dropdown.show();
        });

        it(`and I hit a key that isn't Escape, nothing happens`, () => {
            doc.body.dispatchEvent(new KeyboardEvent("keyup", { key: "A", bubbles: true }));

            expectTheDropdownToBeShown(dropdown_element);
        });

        it(`and I hit the Escape key inside an input element, nothing happens`, () => {
            const input = doc.createElement("input");
            doc.body.append(input);
            simulateEscapeKey(input);

            expectTheDropdownToBeShown(dropdown_element);

            input.remove();
        });

        it(`and I hit the Escape key inside a select element, nothing happens`, () => {
            const select = doc.createElement("select");
            doc.body.append(select);
            simulateEscapeKey(select);

            expectTheDropdownToBeShown(dropdown_element);

            select.remove();
        });

        it(`and I hit the Escape key inside a textarea element, nothing happens`, () => {
            const textarea = doc.createElement("textarea");
            doc.body.append(textarea);
            simulateEscapeKey(textarea);

            expectTheDropdownToBeShown(dropdown_element);

            textarea.remove();
        });

        it(`and given the dropdown was hidden, when I hit the Escape key, nothing happens`, () => {
            dropdown.hide();
            simulateEscapeKey(doc.body);

            expectTheDropdownToBeHidden(dropdown_element);
        });
        it(`and I hit the Escape key, the modal will be hidden`, () => {
            simulateEscapeKey(doc.body);

            expectTheDropdownToBeHidden(dropdown_element);
        });
    });
});

function createLocalDocument(): HTMLDocument {
    return document.implementation.createHTMLDocument();
}

function expectTheDropdownToBeShown(dropdown_element: HTMLElement): void {
    expect(dropdown_element.classList.contains(DROPDOWN_SHOWN_CLASS_NAME)).toBe(true);
}

function expectTheDropdownToBeHidden(dropdown_element: HTMLElement): void {
    expect(dropdown_element.classList.contains(DROPDOWN_SHOWN_CLASS_NAME)).toBe(false);
}

function simulateClick(element: EventTarget): void {
    element.dispatchEvent(new Event("click", { bubbles: true }));
}

function simulateEscapeKey(element: EventTarget): void {
    element.dispatchEvent(new KeyboardEvent("keyup", { key: "Escape", bubbles: true }));
}
