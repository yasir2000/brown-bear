/*
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

import { replaceFolderContentByItem } from "./item-mutations";
import type { Item, LockInfo, State, User } from "../type";

describe("Item mutations", () => {
    describe("replaceFolderContentByItem", () => {
        it("replace the element in folder content", () => {
            let folder_content: Array<Item> = [];
            folder_content = [
                { id: 1, lock_info: { lock_by: { id: 1 } as User, lock_date: "" } } as Item,
                { id: 2, lock_info: { lock_by: { id: 2 } as User, lock_date: "" } } as Item,
            ];
            const state: State = { folder_content } as unknown as State;

            replaceFolderContentByItem(state, { id: 1, lock_info: null } as Item);

            expect(state.folder_content[0].lock_info).toBe(null);
            expect(state.folder_content[1].lock_info).toEqual({
                lock_by: { id: 2 } as User,
                lock_date: "",
            });
        });

        it("does nothing when element is not found in folder content", () => {
            const state: State = {
                folder_content: [
                    { id: 1, lock_info: null } as Item,
                    { id: 2, lock_info: null } as Item,
                ],
            } as unknown as State;

            replaceFolderContentByItem(state, {
                id: 3,
                lock_info: { lock_by: { id: 1 } as User, lock_date: "" },
            } as Item);

            expect(state.folder_content[0].lock_info).toBe(null);
            expect(state.folder_content[1].lock_info).toBe(null);
        });

        it("replace the preview item", () => {
            const item = {
                id: 3,
                lock_info: { lock_by: { id: 1 } as User, lock_date: "" } as LockInfo,
            } as Item;

            const state: State = {
                folder_content: [item],
                currently_previewed_item: item,
            } as unknown as State;

            replaceFolderContentByItem(state, { id: 3, lock_info: null } as Item);

            expect(state.folder_content[0].lock_info).toBe(null);
            expect(state.currently_previewed_item?.lock_info).toBe(null);
        });

        it("don't do anything when preview item is not the one updated", () => {
            const lock_info: LockInfo = { lock_by: { id: 1 } as User, lock_date: "" } as LockInfo;
            const item = { id: 9, lock_info: lock_info } as Item;

            const state: State = {
                folder_content: [item],
                currently_previewed_item: item,
            } as unknown as State;

            replaceFolderContentByItem(state, { id: 3, lock_info: null } as Item);

            expect(state.folder_content[0].lock_info).toBe(lock_info);
            expect(state.currently_previewed_item?.lock_info).toBe(lock_info);
        });
    });
});
