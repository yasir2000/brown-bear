/**
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

import { createListPicker } from "@tuleap/list-picker";
import { getPOFileFromLocale, initGettext } from "@tuleap/core/scripts/tuleap/gettext/gettext-init";

document.addEventListener("DOMContentLoaded", async () => {
    const locale = document.body.dataset.userLocale ?? "en_US";

    const select = document.querySelector("#link-with-parent-select");
    if (!(select instanceof HTMLSelectElement)) {
        return;
    }

    const gettext_provider = await initGettext(
        locale,
        "tracker_artifact",
        (locale) =>
            import(
                /* webpackChunkName: "tracker-artifact-po-" */ "../po/" +
                    getPOFileFromLocale(locale)
            )
    );

    createListPicker(select, {
        locale: locale,
        placeholder: gettext_provider.gettext("Choose a value in the list"),
        is_filterable: true,
    });
});
