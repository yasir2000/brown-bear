/*
 * Copyright (c) BrownBear, 2020 - Present. All Rights Reserved.
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

import Vue from "vue";
import { initVueGettext, getPOFileFromLocale } from "../../../tuleap/gettext/vue-gettext-init";
import App from "./src/components/App.vue";

document.addEventListener("DOMContentLoaded", async () => {
    const vue_mount_point = document.getElementById("platform-banner-admin");
    if (!vue_mount_point) {
        return;
    }

    await initVueGettext(
        Vue,
        (locale: string) =>
            import(
                /* webpackChunkName: "site-admin/platform-banner-po-" */ "./po/" +
                    getPOFileFromLocale(locale)
            )
    );

    const AppComponent = Vue.extend(App);
    new AppComponent({
        propsData: {
            message: vue_mount_point.dataset.bannerMessage || "",
            importance: vue_mount_point.dataset.bannerImportance || "standard",
            expiration_date: vue_mount_point.dataset.bannerExpirationDate || "",
            location: window.location,
        },
    }).$mount(vue_mount_point);
});
