/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

import type { Instance, Options, Placement } from "@popperjs/core";
import { createPopper } from "@popperjs/core";
import { findClosestElement } from "../../../../themes/tlp/src/js/dom-walker";

export const POPOVER_SHOWN_CLASS_NAME = "tlp-popover-shown";

export type PopoverOptions = Partial<Options> & {
    anchor?: HTMLElement | undefined;
    trigger?: "click" | "hover";
};

export interface Popover {
    hide(): void;
    destroy(): void;
}

export function createPopover(
    doc: Document,
    popover_trigger: HTMLElement,
    popover_content: HTMLElement,
    options: PopoverOptions = {}
): Popover {
    const anchor = options.anchor || popover_trigger;
    const popper = createPopper(anchor, popover_content, getPopperOptions(anchor, options));

    const dismiss_buttons = popover_content.querySelectorAll('[data-dismiss="popover"]');
    const listeners = buildListeners(
        doc,
        popover_trigger,
        popover_content,
        dismiss_buttons,
        options,
        popper
    );
    attachListeners(listeners);

    return {
        destroy: (): void => {
            destroyListeners(listeners);
            popper.destroy();
        },
        hide: (): void => {
            popover_content.classList.remove(POPOVER_SHOWN_CLASS_NAME);
        },
    };
}

const allowed_placements = [
    "auto",
    "auto-start",
    "auto-end",
    "top",
    "top-start",
    "top-end",
    "right",
    "right-start",
    "right-end",
    "bottom",
    "bottom-start",
    "bottom-end",
    "left",
    "left-start",
    "left-end",
];

const isPlacement = (string_to_check: string): string_to_check is Placement =>
    allowed_placements.includes(string_to_check);

function getPopperOptions(anchor: HTMLElement, options: PopoverOptions): Partial<Options> {
    const placement = options.placement || anchor.dataset.placement || "bottom";

    if (!isPlacement(placement)) {
        throw new Error(
            "Invalid placement received: " +
                placement +
                ". Allowed values are: " +
                allowed_placements.join(",")
        );
    }

    return {
        placement,
        modifiers: [
            {
                name: "arrow",
                options: {
                    element: ".tlp-popover-arrow",
                    padding: 15,
                },
            },
            {
                name: "offset",
                options: {
                    offset: [0, 10],
                },
            },
            {
                name: "computeStyles",
                options: {
                    gpuAcceleration: false, // true by default
                },
            },
            ...(options.modifiers || []),
        ],
    };
}

type EventType = "click" | "mouseout" | "mouseover" | "keyup";

interface EventListener {
    element: EventTarget;
    type: EventType;
    handler: { (e: Event): unknown };
}

function buildListeners(
    doc: Document,
    popover_trigger: HTMLElement,
    popover_content: Element,
    dismiss_buttons: NodeListOf<Element>,
    options: PopoverOptions,
    popper: Instance
): EventListener[] {
    const trigger = options.trigger || popover_trigger.dataset.trigger || "hover";
    if (trigger === "hover") {
        return [
            buildMouseOverListener(doc, popover_trigger, popover_content, popper),
            buildMouseOutListener(doc, popover_trigger, popover_content),
        ];
    }
    if (trigger === "click") {
        const listeners = [
            buildTriggerClickListener(doc, popover_trigger, popover_content, popper),
            buildDocumentClickListener(doc, popover_trigger, popover_content),
            buildEscapeListener(doc, popover_content),
        ];
        for (const dismiss of dismiss_buttons) {
            listeners.push(buildDismissClickListener(doc, dismiss));
        }
        return listeners;
    }

    return [];
}

function attachListeners(listeners: EventListener[]): void {
    for (const { element, type, handler } of listeners) {
        element.addEventListener(type, handler);
    }
}

function destroyListeners(listeners: EventListener[]): void {
    for (const { element, type, handler } of listeners) {
        element.removeEventListener(type, handler);
    }
}

function buildMouseOverListener(
    doc: Document,
    popover_trigger: HTMLElement,
    popover_content: Element,
    popper: Instance
): EventListener {
    return {
        element: popover_trigger,
        type: "mouseover",
        handler(): void {
            hideAllShownPopovers(doc);
            showPopover(popover_content, popper);
        },
    };
}

function buildMouseOutListener(
    doc: Document,
    popover_trigger: Element,
    popover_content: Element
): EventListener {
    return {
        element: popover_trigger,
        type: "mouseout",
        handler(): void {
            hideAllShownPopovers(doc);
            popover_content.classList.remove(POPOVER_SHOWN_CLASS_NAME);
        },
    };
}

function buildTriggerClickListener(
    doc: Document,
    popover_trigger: Element,
    popover_content: Element,
    popper: Instance
): EventListener {
    return {
        element: popover_trigger,
        type: "click",
        handler(): void {
            const is_shown = popover_content.classList.contains(POPOVER_SHOWN_CLASS_NAME);
            hideAllShownPopovers(doc);
            if (!is_shown) {
                popover_content.setAttribute("x-trigger", "click");
                showPopover(popover_content, popper);
            }
        },
    };
}

function buildDocumentClickListener(
    doc: Document,
    popover_trigger: Element,
    popover_content: Element
): EventListener {
    return {
        element: doc,
        type: "click",
        handler(event): void {
            if (!(event.target instanceof Element)) {
                return;
            }
            if (
                popover_content.classList.contains(POPOVER_SHOWN_CLASS_NAME) &&
                findClosestElement(doc, event.target, popover_content) === null &&
                findClosestElement(doc, event.target, popover_trigger) === null
            ) {
                hideAllShownPopovers(doc);
            }
        },
    };
}

function buildEscapeListener(doc: Document, popover_content: Element): EventListener {
    return {
        element: doc,
        type: "keyup",
        handler(event): void {
            if (!(event instanceof KeyboardEvent) || event.key !== "Escape") {
                return;
            }
            if (popover_content.classList.contains(POPOVER_SHOWN_CLASS_NAME)) {
                hideAllShownPopovers(doc);
            }
        },
    };
}

function buildDismissClickListener(doc: Document, dismiss: Element): EventListener {
    return {
        element: dismiss,
        type: "click",
        handler(): void {
            hideAllShownPopovers(doc);
        },
    };
}

function hideAllShownPopovers(doc: Document): void {
    for (const popover of doc.querySelectorAll("." + POPOVER_SHOWN_CLASS_NAME)) {
        popover.classList.remove(POPOVER_SHOWN_CLASS_NAME);
    }
}

async function showPopover(popover_content: Element, popper: Instance): Promise<void> {
    popover_content.classList.add(POPOVER_SHOWN_CLASS_NAME);
    await popper.update();
}
