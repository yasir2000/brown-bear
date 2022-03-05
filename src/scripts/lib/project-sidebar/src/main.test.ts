/**
 * Copyright (c) 2022-Present Enalean
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

describe("Main instantiation of the Tuleap Project Sidebar element", () => {
    it("instantiates the element", async () => {
        expect(document.head.getElementsByTagName("style")).toHaveLength(0);
        expect(window.customElements.get("tuleap-project-sidebar")).toBe(undefined);

        await import("./main");

        expect(document.head.getElementsByTagName("style")).toHaveLength(1);
        expect(window.customElements.get("tuleap-project-sidebar")).not.toBe(undefined);
    });
});

// Since we only do an async import we have to add an explicit empty export so the file can be considered as a module
// eslint-disable-next-line jest/no-export
export {};
