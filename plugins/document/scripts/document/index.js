/*
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

import Vue from "vue";
import VueCompositionAPI from "@vue/composition-api";
import VueDOMPurifyHTML from "vue-dompurify-html";

import App from "./components/App.vue";
import { createStore } from "./store/index.js";
import { createRouter } from "./router/index.js";
import moment from "moment";
import "moment-timezone";

import {
    getPOFileFromLocale,
    initVueGettext,
} from "../../../../src/scripts/tuleap/gettext/vue-gettext-init";

import { setupDocumentShortcuts } from "./keyboard-navigation/keyboard-navigation";
import { isFeatureFlagNewSearchEnabled } from "./helpers/feature-flag";

document.addEventListener("DOMContentLoaded", async () => {
    Vue.use(VueDOMPurifyHTML);
    Vue.use(VueCompositionAPI);

    let user_locale = document.body.dataset.userLocale;
    Vue.config.language = user_locale;
    user_locale = user_locale.replace(/_/g, "-");

    const vue_mount_point = document.getElementById("document-tree-view");

    if (!vue_mount_point) {
        return;
    }

    const project_id = Number.parseInt(vue_mount_point.dataset.projectId, 10);
    const root_id = Number.parseInt(vue_mount_point.dataset.rootId, 10);
    const project_name = vue_mount_point.dataset.projectName;
    const project_public_name = vue_mount_point.dataset.projectPublicName;
    const project_url = vue_mount_point.dataset.projectUrl;
    const user_is_admin = Boolean(vue_mount_point.dataset.userIsAdmin);
    const user_can_create_wiki = Boolean(vue_mount_point.dataset.userCanCreateWiki);
    const user_timezone = document.body.dataset.userTimezone;
    const date_time_format = document.body.dataset.dateTimeFormat;
    const user_id = Number.parseInt(document.body.dataset.userId, 10);
    const max_files_dragndrop = Number.parseInt(vue_mount_point.dataset.maxFilesDragndrop, 10);
    const max_size_upload = Number.parseInt(vue_mount_point.dataset.maxSizeUpload, 10);
    const warning_threshold = Number.parseInt(vue_mount_point.dataset.warningThreshold, 10);
    const max_archive_size = Number.parseInt(vue_mount_point.dataset.maxArchiveSize, 10);
    const embedded_are_allowed = Boolean(vue_mount_point.dataset.embeddedAreAllowed);
    const is_deletion_allowed = Boolean(vue_mount_point.dataset.userCanDeleteItem);
    const is_status_property_used = Boolean(vue_mount_point.dataset.isItemStatusPropertyUsed);
    const is_obsolescence_date_property_used = Boolean(
        vue_mount_point.dataset.isObsolescenceDatePropertyUsed
    );
    const is_changelog_proposed_after_dnd = Boolean(
        vue_mount_point.dataset.isChangelogDisplayedAfterDnd
    );
    const csrf_token_name = vue_mount_point.dataset.csrfTokenName;
    const csrf_token = vue_mount_point.dataset.csrfToken;
    const relative_dates_display = vue_mount_point.dataset.relativeDatesDisplay;
    const privacy = JSON.parse(vue_mount_point.dataset.privacy);
    const project_flags = JSON.parse(vue_mount_point.dataset.projectFlags);
    const project_icon = vue_mount_point.dataset.projectIcon;
    const search_for_document_with_criteria = isFeatureFlagNewSearchEnabled(document);

    const consider_string_criteria_as_text = (criterion) => ({
        ...criterion,
        type: criterion.type === "string" ? "text" : criterion.type,
    });
    const criteria = JSON.parse(vue_mount_point.dataset.criteria).map(
        consider_string_criteria_as_text
    );
    const columns = JSON.parse(vue_mount_point.dataset.columns);

    moment.tz(user_timezone);
    moment.locale(user_locale);

    await initVueGettext(Vue, (locale) =>
        import(/* webpackChunkName: "document-po-" */ "./po/" + getPOFileFromLocale(locale))
    );

    const configuration_state = {
        user_id,
        project_id,
        root_id,
        project_name,
        project_public_name,
        user_is_admin,
        user_can_create_wiki,
        embedded_are_allowed,
        is_status_property_used,
        is_obsolescence_date_property_used,
        project_url,
        date_time_format,
        max_files_dragndrop,
        max_size_upload,
        warning_threshold,
        max_archive_size,
        is_deletion_allowed,
        is_changelog_proposed_after_dnd,
        privacy,
        project_flags,
        relative_dates_display,
        project_icon,
        search_for_document_with_criteria,
        user_locale,
        criteria,
        columns,
    };

    const AppComponent = Vue.extend(App);
    const store = createStore(user_id, project_id, configuration_state);
    const router = createRouter(store, project_name);

    new AppComponent({
        store,
        router,
        propsData: {
            csrf_token_name,
            csrf_token,
        },
    }).$mount(vue_mount_point);

    const gettext_provider = {
        $gettext: Vue.prototype.$gettext,
        $pgettext: Vue.prototype.$pgettext,
    };
    setupDocumentShortcuts(gettext_provider);
});
