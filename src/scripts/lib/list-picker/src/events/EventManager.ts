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
import type { DropdownManager } from "../dropdown/DropdownManager";
import type { DropdownContentRenderer } from "../renderers/DropdownContentRenderer";
import type { SelectionManager } from "../type";
import type { KeyboardNavigationManager } from "../navigation/KeyboardNavigationManager";
import type { ListItemHighlighter } from "../navigation/ListItemHighlighter";
import {
    isArrowDown,
    isArrowUp,
    isBackspaceKey,
    isEnterKey,
    isEscapeKey,
    isShiftKey,
    isTabKey,
} from "../helpers/keys-helper";
import type { FieldFocusManager } from "../navigation/FieldFocusManager";

export class EventManager {
    private escape_key_handler!: (event: Event) => void;
    private click_outside_handler!: (event: Event) => void;
    private keyboard_events_handler!: (event: Event) => void;
    private prevent_form_submit_on_enter_handler!: (event: Event) => void;
    private has_keyboard_selection_occurred = false;

    constructor(
        private readonly doc: HTMLDocument,
        private readonly wrapper_element: Element,
        private readonly list_picker_element: Element,
        private readonly dropdown_element: Element,
        private readonly search_field_element: HTMLInputElement | null,
        private readonly source_select_box: HTMLSelectElement,
        private readonly selection_manager: SelectionManager,
        private readonly dropdown_manager: DropdownManager,
        private readonly dropdown_content_renderer: DropdownContentRenderer,
        private readonly keyboard_navigation_manager: KeyboardNavigationManager,
        private readonly list_item_highlighter: ListItemHighlighter,
        private readonly field_focus_manager: FieldFocusManager
    ) {}

    public attachEvents(): void {
        if (this.source_select_box.disabled) {
            return;
        }

        this.attachOpenCloseEvent();
        this.attachItemListEvent();
        this.attachSourceSelectBoxChangeEvent();
        this.escape_key_handler = this.attachEscapeKeyPressedEvent();
        this.click_outside_handler = this.attachClickOutsideEvent();
        this.keyboard_events_handler = this.attachKeyboardNavigationEvents();
        this.prevent_form_submit_on_enter_handler = this.preventEnterKeyInSearchFieldToSubmitForm();

        if (this.search_field_element !== null) {
            this.attachSearchEvent(this.search_field_element);
        }
    }

    public removeEventsListenersOnDocument(): void {
        this.doc.removeEventListener("keyup", this.escape_key_handler);
        this.doc.removeEventListener("pointerdown", this.click_outside_handler);
        this.doc.removeEventListener("keydown", this.keyboard_events_handler);
        this.doc.removeEventListener("keypress", this.prevent_form_submit_on_enter_handler);
    }

    private attachEscapeKeyPressedEvent(): (event: Event) => void {
        const handler = (event: Event): void => {
            this.handleEscapeKey(event);
        };

        this.doc.addEventListener("keyup", handler);

        return handler;
    }

    private attachClickOutsideEvent(): (event: Event) => void {
        const handler = (event: Event): void => {
            this.handleClicksOutsideListPicker(event);
        };
        this.doc.addEventListener("pointerdown", handler);

        return handler;
    }

    private attachOpenCloseEvent(): void {
        this.list_picker_element.addEventListener("pointerdown", (event: Event) => {
            event.preventDefault();
            if (
                event.target instanceof Element &&
                this.isElementOnWhichClickShouldNotCloseListPicker(event.target)
            ) {
                return;
            }

            if (
                event.target instanceof Element &&
                event.target.classList.contains("list-picker-search-field")
            ) {
                this.dropdown_manager.openListPicker();
                return;
            }

            if (this.dropdown_manager.isDropdownOpen()) {
                this.resetSearchField();
                this.dropdown_manager.closeListPicker();
            } else {
                this.list_item_highlighter.resetHighlight();
                this.dropdown_manager.openListPicker();
            }
        });
    }

    private isElementOnWhichClickShouldNotCloseListPicker(element: Element): boolean {
        return (
            element.classList.contains("list-picker-dropdown-option-value-disabled") ||
            element.classList.contains("list-picker-group-label") ||
            element.classList.contains("list-picker-item-group")
        );
    }

    public attachItemListEvent(): void {
        const items = this.dropdown_element.querySelectorAll(".list-picker-dropdown-option-value");
        let mouse_target_id: string | null = null;

        items.forEach((item) => {
            item.addEventListener("pointerup", () => {
                this.selection_manager.processSelection(item);
                this.resetSearchField();
                this.dropdown_manager.closeListPicker();
            });

            item.addEventListener("pointerenter", () => {
                if (!(item instanceof HTMLElement) || !item.dataset.itemId) {
                    throw new Error("item is not an highlightable item");
                }
                if (mouse_target_id === item.dataset.itemId) {
                    // keyboard navigation occurring, let's not mess things up.
                    return;
                }

                mouse_target_id = item.dataset.itemId;
                this.list_item_highlighter.highlightItem(item);
            });
        });
    }

    private attachSearchEvent(search_field_element: HTMLInputElement): void {
        search_field_element.addEventListener("keyup", (event: Event) => {
            if (isArrowUp(event) || isArrowDown(event) || isTabKey(event) || isShiftKey(event)) {
                return;
            }

            if (isEnterKey(event)) {
                if (this.has_keyboard_selection_occurred) {
                    this.has_keyboard_selection_occurred = false;
                } else {
                    this.list_item_highlighter.resetHighlight();
                    this.dropdown_manager.openListPicker();
                }
                return;
            }

            const filter_query = search_field_element.value;
            this.dropdown_content_renderer.renderFilteredListPickerDropdownContent(filter_query);

            this.list_item_highlighter.resetHighlight();
            this.dropdown_manager.openListPicker();
        });

        if (
            search_field_element.parentElement &&
            search_field_element.parentElement.classList.contains(
                "list-picker-multiple-search-section"
            )
        ) {
            search_field_element.addEventListener("pointerdown", () => {
                this.list_item_highlighter.resetHighlight();
                this.dropdown_manager.openListPicker();
            });
        }

        search_field_element.addEventListener("keydown", (event: Event) => {
            if (isBackspaceKey(event)) {
                this.selection_manager.handleBackspaceKey(event);
                event.stopPropagation();
            }

            if (isTabKey(event)) {
                this.resetSearchField();
            }
        });
    }

    private handleClicksOutsideListPicker(event: Event): void {
        const target_element = event.target;

        if (
            !(target_element instanceof Element) ||
            (!this.wrapper_element.contains(target_element) &&
                !this.dropdown_element.contains(target_element))
        ) {
            this.resetSearchField();
            this.has_keyboard_selection_occurred = false;
            this.dropdown_manager.closeListPicker();
        }
    }

    private resetSearchField(): void {
        if (!this.dropdown_manager.isDropdownOpen()) {
            return;
        }
        if (!this.search_field_element) {
            return;
        }

        this.search_field_element.value = "";
        this.dropdown_content_renderer.renderFilteredListPickerDropdownContent("");
        this.list_item_highlighter.resetHighlight();
    }

    private handleEscapeKey(event: Event): void {
        if (isEscapeKey(event)) {
            this.resetSearchField();
            this.dropdown_manager.closeListPicker();
            this.has_keyboard_selection_occurred = false;
            event.stopPropagation();
        }
    }

    private attachSourceSelectBoxChangeEvent(): void {
        this.source_select_box.addEventListener("change", () => {
            const is_valid = this.source_select_box.checkValidity();
            if (!is_valid) {
                this.wrapper_element.classList.add("list-picker-error");
            } else {
                this.wrapper_element.classList.remove("list-picker-error");
            }
        });
    }

    private attachKeyboardNavigationEvents(): (event: Event) => void {
        const handler = (event: Event): void => {
            const is_dropdown_open = this.dropdown_manager.isDropdownOpen();
            if (isTabKey(event) && is_dropdown_open) {
                this.dropdown_manager.closeListPicker();
                return;
            }

            if (
                !is_dropdown_open &&
                isEnterKey(event) &&
                this.field_focus_manager.doesSelectionElementHaveTheFocus()
            ) {
                this.list_item_highlighter.resetHighlight();
                this.dropdown_manager.openListPicker();
                this.has_keyboard_selection_occurred = false;
                return;
            }

            if (!(event instanceof KeyboardEvent) || !is_dropdown_open) {
                return;
            }

            const highlighted_item = this.list_item_highlighter.getHighlightedItem();
            if (isEnterKey(event) && highlighted_item) {
                this.selection_manager.processSelection(highlighted_item);
                this.resetSearchField();
                this.dropdown_manager.closeListPicker();
                this.has_keyboard_selection_occurred = true;
            } else {
                this.keyboard_navigation_manager.navigate(event);
            }
        };
        this.doc.addEventListener("keydown", handler);
        return handler;
    }

    private preventEnterKeyInSearchFieldToSubmitForm(): (event: Event) => void {
        const handler = (event: Event): void => {
            if (
                event.target &&
                event.target instanceof HTMLElement &&
                event.target.classList.contains("list-picker-search-field") &&
                isEnterKey(event)
            ) {
                event.preventDefault();
            }
        };
        this.doc.addEventListener("keypress", handler);
        return handler;
    }
}
