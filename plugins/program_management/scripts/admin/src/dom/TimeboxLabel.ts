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

import type { RetrieveElement } from "./RetrieveElement";

export type InputCallback = (value: string) => void;
type Handler = () => void;

const DISABLED_CLASSNAME = "tlp-form-element-disabled";

export class TimeboxLabel {
    private handlers: Handler[] = [];

    private constructor(private readonly input: HTMLInputElement) {}

    get value(): string {
        return this.input.value;
    }

    disable(): void {
        this.input.parentElement?.classList.add(DISABLED_CLASSNAME);
        this.input.disabled = true;
    }

    enable(): void {
        this.input.parentElement?.classList.remove(DISABLED_CLASSNAME);
        this.input.disabled = false;
    }

    addInputListener(callback: InputCallback): void {
        const handler = (): void => callback(this.value);
        this.handlers.push(handler);
        this.input.addEventListener("input", handler);
    }

    removeInputListeners(): void {
        for (const handler of this.handlers) {
            this.input.removeEventListener("input", handler);
        }
        this.handlers = [];
    }

    static fromId(retriever: RetrieveElement, id: string): TimeboxLabel {
        return new TimeboxLabel(retriever.getInputById(id));
    }
}
