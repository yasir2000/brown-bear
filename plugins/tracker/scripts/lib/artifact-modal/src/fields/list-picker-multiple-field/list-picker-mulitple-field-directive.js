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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

import tpl from "./list-picker-multiple-field.tpl.html";
import ListPickerMultipleFieldController from "./list-picker-multiple-field-controller.js";

export default function ListPickerMultipleFieldDirective() {
    return {
        restrict: "A",
        replace: false,
        scope: {
            field: "=tuleapArtifactModalListPickerMultipleField",
            value_model: "=valueModel",
            isDisabled: "&isDisabled",
            options_value: "=optionsValue",
            is_list_picker_enabled: "=isListPickerEnabled",
        },
        controller: ListPickerMultipleFieldController,
        controllerAs: "list_picker_multiple_field",
        bindToController: true,
        template: tpl,
    };
}
