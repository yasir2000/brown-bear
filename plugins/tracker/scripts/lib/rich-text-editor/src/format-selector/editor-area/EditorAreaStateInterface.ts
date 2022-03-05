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

import type { TextFieldFormat } from "../../../../../constants/fields-constants";

export interface EditorAreaStateInterface {
    readonly selectbox_id: string;
    readonly selectbox_name: string | undefined;
    readonly current_format: TextFieldFormat;
    readonly textarea: HTMLTextAreaElement;
    readonly mount_point: HTMLDivElement;
    readonly rendered_html: Promise<string> | null;

    isInEditMode(): boolean;
    isCurrentFormatCommonMark(): boolean;
    switchToPreviewMode(): void;
    switchToEditMode(): void;
    changeFormat(new_format: TextFieldFormat): void;
}
