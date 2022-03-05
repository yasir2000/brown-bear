/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import { ScrollingManager } from "./ScrollingManager";

describe("ScrollingManager", () => {
    function getModalElements(): {
        modal_section: HTMLElement;
        wrapper_element: HTMLElement;
    } {
        const modal = document.createElement("div");
        modal.setAttribute("class", "a-modal");
        const modal_body = document.createElement("div");
        modal_body.setAttribute("class", "tlp-modal-body");
        const modal_section = document.createElement("div");
        modal_section.setAttribute("class", "a-modal-section");
        const form_element = document.createElement("div");
        form_element.setAttribute("class", "form-element-section");
        const wrapper_element = document.createElement("span");
        wrapper_element.setAttribute("class", "list-picker-component-wrapper");

        form_element.appendChild(wrapper_element);
        modal_section.appendChild(form_element);
        modal_body.appendChild(modal_section);
        modal.appendChild(modal_body);

        return {
            modal_section,
            wrapper_element,
        };
    }

    describe("findListPickerFirstScrollableParent", () => {
        it("should return the first scrollable parent of the list-picker", () => {
            ["overflow", "overflow-y", "overflow-x"].forEach((overflow_property) => {
                ["auto", "scroll"].forEach((overflow_value) => {
                    const { modal_section, wrapper_element } = getModalElements();
                    modal_section.setAttribute("style", `${overflow_property}: ${overflow_value}`);

                    const scrolling_manager = new ScrollingManager(wrapper_element);
                    expect(
                        scrolling_manager.findListPickerFirstScrollableParent(wrapper_element)
                    ).toEqual(modal_section);
                });
            });
        });

        it("should return the nothing if the first scrollable parent is document.body", () => {
            const { wrapper_element } = getModalElements();
            const scrolling_manager = new ScrollingManager(wrapper_element);

            expect(scrolling_manager.findListPickerFirstScrollableParent(wrapper_element)).toEqual(
                null
            );
        });
    });

    describe("scrolling lock/unlock", () => {
        beforeEach(() => {
            jest.spyOn(window, "addEventListener");
            jest.spyOn(window, "removeEventListener");
        });

        afterEach(() => {
            jest.clearAllMocks();
        });

        describe("lockScrolling", () => {
            it("should not lock the page scrolling when the list-picker is not in a modal", () => {
                const doc = document.implementation.createHTMLDocument();
                const wrapper_element = document.createElement("span");
                const scrolling_manager = new ScrollingManager(wrapper_element);
                doc.body.appendChild(wrapper_element);

                scrolling_manager.lockScrolling();

                expect(window.addEventListener).not.toHaveBeenCalled();
            });

            it("should lock the page scrolling when the list-picker is not in a modal", () => {
                const { wrapper_element } = getModalElements();
                const scrolling_manager = new ScrollingManager(wrapper_element);

                scrolling_manager.lockScrolling();

                expect(window.addEventListener).toHaveBeenCalled();
            });

            it("should not lock scrolling when the list-picker is not in a scrollable parent", () => {
                const { wrapper_element, modal_section } = getModalElements();
                jest.spyOn(modal_section, "addEventListener");

                const scrolling_manager = new ScrollingManager(wrapper_element);
                scrolling_manager.lockScrolling();

                expect(window.addEventListener).toHaveBeenCalled();
                expect(modal_section.addEventListener).not.toHaveBeenCalled();
            });

            it("should lock scrolling when the list-picker is in a scrollable parent", () => {
                const { wrapper_element, modal_section } = getModalElements();
                modal_section.style.overflowY = "scroll";
                jest.spyOn(modal_section, "addEventListener");

                const scrolling_manager = new ScrollingManager(wrapper_element);
                scrolling_manager.lockScrolling();

                expect(window.addEventListener).toHaveBeenCalled();
                expect(modal_section.addEventListener).toHaveBeenCalled();
            });

            it("should lock the page and the scrolling parent at the same position it was before the dropdown has been open", () => {
                const { wrapper_element, modal_section } = getModalElements();
                modal_section.style.overflowY = "scroll";

                const scrolling_manager = new ScrollingManager(wrapper_element);
                scrolling_manager.lockScrolling();

                jest.spyOn(window, "scroll").mockImplementation();
                window.dispatchEvent(new Event("scroll"));
                expect(window.scroll).toHaveBeenCalledWith(0, 0);

                // eslint-disable-next-line jest/prefer-spy-on
                modal_section.scroll = jest.fn();
                modal_section.dispatchEvent(new Event("scroll"));
                expect(modal_section.scroll).toHaveBeenCalledWith(0, 0);
            });
        });

        describe("unlockScrolling", () => {
            it("should release locks on window and scrollable parent", () => {
                const { wrapper_element, modal_section } = getModalElements();
                modal_section.style.overflowY = "scroll";
                jest.spyOn(modal_section, "removeEventListener");

                const scrolling_manager = new ScrollingManager(wrapper_element);
                scrolling_manager.lockScrolling();

                scrolling_manager.unlockScrolling();
                expect(window.removeEventListener).toHaveBeenCalled();
                expect(modal_section.removeEventListener).toHaveBeenCalled();
            });
        });
    });
});
