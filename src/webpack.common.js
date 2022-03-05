/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

const fs = require("fs");
// eslint-disable-next-line import/no-extraneous-dependencies
const WebpackAssetsManifest = require("webpack-assets-manifest");
const path = require("path");
const webpack_configurator = require("../tools/utils/scripts/webpack-configurator.js");
const { esbuild_target } = require("../tools/utils/scripts/browserslist_config");
// Dependency is defined at the root of the repo
// eslint-disable-next-line import/no-extraneous-dependencies
const { ESBuildMinifyPlugin } = require("esbuild-loader");
const context = __dirname;
const assets_dir_path = path.resolve(__dirname, "./www/assets/core");
const output = webpack_configurator.configureOutput(assets_dir_path, "/assets/core/");

const pkg = JSON.parse(
    fs.readFileSync(path.resolve(__dirname, "./node_modules/ckeditor4/package.json"))
);
const ckeditor_version = pkg.version;

// Prototype doesn't like to have its "$super" argument mangled due to the fact
// that it checks for its presence during class initialization
const optimization_target_with_prototypejs_support = {
    minimizer: [
        new ESBuildMinifyPlugin({
            target: esbuild_target,
            minifySyntax: true,
            minifyWhitespace: true,
            minifyIdentifiers: false,
            include: [/including-prototypejs/],
        }),
    ],
};

const manifest_plugin = new WebpackAssetsManifest({
    output: "manifest.json",
    merge: true,
    writeToDisk: true,
    apply(manifest) {
        manifest.set("ckeditor.js", `ckeditor-${ckeditor_version}/ckeditor.js`);
    },
});

const webpack_config_for_ckeditor = {
    entry: {},
    context,
    output,
    plugins: [
        manifest_plugin,
        webpack_configurator.getCopyPlugin([
            {
                from: path.resolve(__dirname, "./node_modules/ckeditor4"),
                to: path.resolve(__dirname, `./www/assets/core/ckeditor-${ckeditor_version}/`),
                toType: "dir",
                globOptions: {
                    ignore: [
                        "**/samples/**",
                        "**/.github/**",
                        "**/*.!(js|css|png)",
                        "**/assets/ckeditor4.png",
                        "**/adapters/**",
                    ],
                },
            },
        ]),
    ],
    stats: {
        excludeAssets: [/\/plugins\//, /\/lang\//, /\/skins\//],
    },
};

let entry_points = {
    tlp: "./themes/tlp/src/scss/tlp.scss",
    // DO NOT add new entrypoints unless it's for a new TLP locale. TLP is exported as a "library". If you add another
    // entrypoint, all scripts that depend on TLP will try to access "select2" or "createModal" from your file
    // (and they will fail).
    "tlp-en_US": "./themes/tlp/src/index.en_US.ts",
    "tlp-fr_FR": "./themes/tlp/src/index.fr_FR.ts",
};

const tlp_colors = ["orange", "blue", "green", "red", "grey", "purple"];
for (const color of tlp_colors) {
    entry_points[`tlp-vars-${color}`] = `./themes/tlp/src/scss/tlp-vars-${color}.scss`;
    entry_points[
        `tlp-vars-${color}-condensed`
    ] = `./themes/tlp/src/scss/tlp-vars-${color}-condensed.scss`;
}

const webpack_config_for_tlp = {
    entry: entry_points,
    context,
    output: {
        path: assets_dir_path,
        filename: "tlp-[chunkhash].[name].js",
        library: "tlp",
    },
    resolve: {
        extensions: [".js", ".ts"],
    },
    module: {
        rules: [
            ...webpack_configurator.configureTypescriptRules(),
            webpack_configurator.rule_scss_loader,
            webpack_configurator.rule_css_assets,
        ],
    },
    plugins: [
        manifest_plugin,
        webpack_configurator.getTypescriptCheckerPlugin(false),
        ...webpack_configurator.getCSSExtractionPlugins(),
    ],
};

const webpack_config_for_tlp_doc = {
    entry: {
        style: "./scripts/tlp-doc/css/main.scss",
        script: "./scripts/tlp-doc/src/index.js",
    },
    context,
    // This one does NOT go in www/assets/core because we do not deliver it in production, only in dev environment
    output: webpack_configurator.configureOutput(
        path.resolve(__dirname, "./www/tlp-doc/dist/"),
        "/tlp-doc/dist/"
    ),
    externals: {
        tlp: "tlp",
    },
    resolve: {
        extensions: [".js", ".ts"],
    },
    module: {
        rules: [
            ...webpack_configurator.configureTypescriptRules(),
            webpack_configurator.rule_scss_loader,
            webpack_configurator.rule_po_files,
        ],
    },
    plugins: [
        // Since it has a different output, it gets its own CleanWebpackPlugin and ManifestPlugin
        webpack_configurator.getCleanWebpackPlugin(),
        webpack_configurator.getManifestPlugin(),
        webpack_configurator.getTypescriptCheckerPlugin(false),
        ...webpack_configurator.getCSSExtractionPlugins(),
    ],
};

const webpack_config_for_flaming_parrot_code = {
    entry: {
        "flamingparrot-with-polyfills": "./scripts/FlamingParrot/index.ts",
        mermaid: "./scripts/mermaid/index.ts",
        "syntax-highlight": "./scripts/syntax-highlight/index.ts",
    },
    context,
    output,
    externals: {
        jquery: "jQuery",
        tuleap: "tuleap",
    },
    resolve: {
        extensions: [".ts", ".js"],
    },
    module: {
        rules: [
            ...webpack_configurator.configureTypescriptRules(),
            webpack_configurator.rule_po_files,
        ],
    },
    plugins: [manifest_plugin, webpack_configurator.getTypescriptCheckerPlugin(false)],
};

const webpack_config_for_rich_text_editor = {
    entry: {
        "rich-text-editor-including-prototypejs": "./scripts/tuleap/textarea_rte.js",
    },
    context,
    output,
    externals: {
        ckeditor4: "CKEDITOR",
        tuleap: "tuleap",
        jquery: "jQuery",
    },
    module: {
        rules: [
            ...webpack_configurator.configureTypescriptRules(),
            webpack_configurator.rule_po_files,
        ],
    },
    plugins: [manifest_plugin],
    optimization: optimization_target_with_prototypejs_support,
};

const webpack_config_for_burning_parrot_code = {
    entry: {
        "access-denied-error": "./scripts/BurningParrot/src/access-denied-error.ts",
        "account/appearance": "./scripts/account/appearance.ts",
        "account/avatar": "./scripts/account/avatar.ts",
        "account/check-pw": "./scripts/account/check-pw.ts",
        "account/generate-pw": "./scripts/account/generate-pw.ts",
        "account/keys-tokens": "./scripts/account/keys-tokens.ts",
        "account/security": "./scripts/account/security.ts",
        "account/timezone": "./scripts/account/timezone.ts",
        "burning-parrot": "./scripts/BurningParrot/src/index.ts",
        "dashboards/dashboard": "./scripts/dashboards/dashboard.js",
        "dashboards/widget-contact-modal": "./scripts/dashboards/widgets/contact-modal.ts",
        "frs-admin-license-agreement": "./scripts/frs/admin/license-agreement.ts",
        "manage-allowed-projects-on-resource":
            "./scripts/tuleap/manage-allowed-projects-on-resource.js",
        "project-admin": "./scripts/project/admin/src/index.ts",
        "project-admin-ugroups": "./scripts/project/admin/src/project-admin-ugroups.ts",
        "project/project-banner-bp": "./scripts/project/banner/index-bp.ts",
        "project/project-banner-fp": "./scripts/project/banner/index-fp.ts",
        "platform/platform-banner-bp": "./scripts/platform/banner/index-bp.ts",
        "platform/platform-banner-fp": "./scripts/platform/banner/index-fp.ts",
        "project/project-registration-creation":
            "./scripts/project/registration/index-for-modal.ts",
        "site-admin-generate-pie-charts": "./scripts/site-admin/generate-pie-charts.ts",
        "site-admin-mass-emailing": "./scripts/site-admin/massmail.ts",
        "site-admin-most-recent-logins": "./scripts/site-admin/most-recent-logins.ts",
        "site-admin-pending-users": "./scripts/site-admin/pending-users.ts",
        "site-admin-permission-delegation": "./scripts/site-admin/permission-delegation.ts",
        "site-admin-project-configuration": "./scripts/site-admin/project-configuration.ts",
        "site-admin-project-history": "./scripts/site-admin/project-history.ts",
        "site-admin-project-list": "./scripts/site-admin/project-list.ts",
        "site-admin-project-widgets": "./scripts/site-admin/project-widgets-configuration/index.ts",
        "site-admin-system-events": "./scripts/site-admin/system-events.ts",
        "site-admin-system-events-admin-homepage":
            "./scripts/site-admin/system-events-admin-homepage.ts",
        "site-admin-system-events-notifications":
            "./scripts/site-admin/system-events-notifications.ts",
        "site-admin-trackers-pending-removal": "./scripts/site-admin/trackers-pending-removal.ts",
        "site-admin-user-details": "./scripts/site-admin/userdetails.ts",
        "site-admin/dates-display": "./scripts/site-admin/dates-display.ts",
        "site-admin/description-fields": "./scripts/site-admin/description-fields.ts",
        "site-admin/password-policy": "./scripts/site-admin/password-policy.ts",
        "tlp-relative-date": "./scripts/tuleap/custom-elements/relative-date/index.ts",
        "trovecat-admin": "./scripts/tuleap/trovecat.ts",
        "widget-project-heartbeat": "./scripts/dashboards/widgets/project-heartbeat/index.ts",
        "browser-deprecation-bp": "./scripts/browser-deprecation/browser-deprecation-modal-bp.ts",
        "browser-deprecation-fp": "./scripts/browser-deprecation/browser-deprecation-modal-fp.ts",
        "project/header-background-admin":
            "./scripts/project/admin/header-background/admin-index.ts",
        "mailing-lists-administration": "./scripts/project/admin/mailing-list/administration.ts",
        "mailing-lists-homepage": "./scripts/project/admin/mailing-list/homepage.ts",
    },
    context,
    output,
    externals: {
        tlp: "tlp",
        tuleap: "tuleap",
        ckeditor4: "CKEDITOR",
        jquery: "jQuery",
    },
    module: {
        rules: [
            ...webpack_configurator.configureTypescriptRules(),
            webpack_configurator.rule_po_files,
            webpack_configurator.rule_mustache_files,
        ],
    },
    plugins: [
        manifest_plugin,
        webpack_configurator.getTypescriptCheckerPlugin(false),
        webpack_configurator.getMomentLocalePlugin(),
    ],
    resolve: {
        extensions: [".ts", ".js"],
    },
};

const webpack_config_for_vue = {
    entry: {
        "frs-permissions": "./scripts/frs/permissions-per-group/index.ts",
        "news-permissions": "./scripts/news/permissions-per-group/index.js",
        "project/project-admin-banner":
            "./scripts/project/admin/banner/index-banner-project-admin.ts",
        "site-admin/platform-banner":
            "./scripts/platform/banner/admin/index-platform-banner-admin.ts",
        "project-admin-services": "./scripts/project/admin/services/src/index-project-admin.js",
        "project/project-registration": "./scripts/project/registration/index.ts",
        "site-admin-services": "./scripts/project/admin/services/src/index-site-admin.js",
        "switch-to-bp": "./scripts/switch-to/index-bp.ts",
        "switch-to-fp": "./scripts/switch-to/index-fp.ts",
    },
    context,
    output,
    externals: {
        tlp: "tlp",
        ckeditor4: "CKEDITOR",
        jquery: "jQuery",
    },
    module: {
        rules: [
            ...webpack_configurator.configureTypescriptRules(),
            webpack_configurator.rule_easygettext_loader,
            webpack_configurator.rule_vue_loader,
        ],
    },
    plugins: [
        manifest_plugin,
        webpack_configurator.getVueLoaderPlugin(),
        webpack_configurator.getTypescriptCheckerPlugin(true),
    ],
    resolveLoader: {
        alias: webpack_configurator.easygettext_loader_alias,
    },
    resolve: {
        extensions: [".js", ".ts", ".vue"],
    },
};

const fat_combined_files = [
        "./www/scripts/prototype/prototype.js",
        "./www/scripts/protocheck/protocheck.js",
        "./www/scripts/scriptaculous/scriptaculous.js",
        "./www/scripts/scriptaculous/builder.js",
        "./www/scripts/scriptaculous/effects.js",
        "./www/scripts/scriptaculous/dragdrop.js",
        "./www/scripts/scriptaculous/controls.js",
        "./www/scripts/scriptaculous/slider.js",
        "./www/scripts/jquery/jquery-1.9.1.min.js",
        "./www/scripts/jquery/jquery-ui.min.js",
        "./www/scripts/jquery/jquery-noconflict.js",
        "./www/scripts/tuleap/project-history.js",
        "./www/scripts/bootstrap/bootstrap-dropdown.js",
        "./www/scripts/bootstrap/bootstrap-button.js",
        "./www/scripts/bootstrap/bootstrap-modal.js",
        "./www/scripts/bootstrap/bootstrap-collapse.js",
        "./www/scripts/bootstrap/bootstrap-tooltip.js",
        "./www/scripts/bootstrap/bootstrap-tooltip-fix-prototypejs-conflict.js",
        "./www/scripts/bootstrap/bootstrap-popover.js",
        "./www/scripts/bootstrap/bootstrap-select/bootstrap-select.js",
        "./www/scripts/bootstrap/bootstrap-datetimepicker/js/bootstrap-datetimepicker.min.js",
        "./www/scripts/bootstrap/bootstrap-datetimepicker/js/bootstrap-datetimepicker.fr.js",
        "./www/scripts/bootstrap/bootstrap-datetimepicker/js/bootstrap-datetimepicker-fix-prototypejs-conflict.js",
        "./www/scripts/select2/select2.min.js",
        "./www/scripts/codendi/common.js",
        "./www/scripts/codendi/feedback.js",
        "./www/scripts/codendi/cross_references.js",
        "./node_modules/@tuleap/tooltip/dist/tooltip.umd.js",
        "./www/scripts/codendi/Tooltip-loader.js",
        "./www/scripts/codendi/Toggler.js",
        "./www/scripts/codendi/DropDownPanel.js",
        "./www/scripts/autocomplete.js",
        "./www/scripts/textboxlist/multiselect.js",
        "./www/scripts/tablekit/tablekit.js",
        "./www/scripts/lytebox/lytebox.js",
        "./www/scripts/lightwindow/lightwindow.js",
        "./node_modules/@tuleap/html-escaper/dist/html-escaper.umd.js",
        "./www/scripts/codendi/Tracker.js",
        "./www/scripts/codendi/TreeNode.js",
        "./www/scripts/tuleap/tuleap-modal.js",
        "./www/scripts/tuleap/datetimepicker.js",
        "./www/scripts/tuleap/svn.js",
        "./www/scripts/tuleap/search.js",
        "./www/scripts/tuleap/tuleap-ckeditor-toolbar.js",
    ],
    subset_combined_files = [
        "./www/scripts/jquery/jquery-2.1.1.min.js",
        "./www/scripts/bootstrap/bootstrap-tooltip.js",
        "./www/scripts/bootstrap/bootstrap-popover.js",
        "./www/scripts/bootstrap/bootstrap-button.js",
    ],
    subset_combined_flamingparrot_files = [
        "./www/scripts/bootstrap/bootstrap-dropdown.js",
        "./www/scripts/bootstrap/bootstrap-modal.js",
        "./node_modules/@tuleap/tooltip/dist/tooltip.umd.js",
    ];

const webpack_config_legacy_combined = {
    entry: {},
    context,
    output,
    plugins: [
        ...webpack_configurator.getLegacyConcatenatedScriptsPlugins({
            "tuleap-including-prototypejs.js": fat_combined_files,
            "tuleap_subset.js": subset_combined_files,
            "tuleap_subset_flamingparrot.js": subset_combined_files.concat(
                subset_combined_flamingparrot_files
            ),
        }),
        manifest_plugin,
    ],
    optimization: optimization_target_with_prototypejs_support,
};

const theme_entry_points = {
    "common-theme/style": "./themes/common/css/style.scss",
    "common-theme/print": "./themes/common/css/print.scss",
    "account-style": "./themes/BurningParrot/css/account/account.scss",
    "dashboards-style": "./themes/BurningParrot/css/dashboards/dashboards.scss",
    "project-registration-style":
        "./themes/BurningParrot/css/project-registration/project-registration.scss",
    "project-registration-creation-style":
        "./themes/BurningParrot/css/project-registration-creation/project-creation-modal.scss",
    "BurningParrot/burning-parrot": "./themes/BurningParrot/css/burning-parrot.scss",
};

const project_background_themes = [
    "aerial-water",
    "asphalt-rock",
    "beach-daytime",
    "blue-rain",
    "blue-sand",
    "brown-alpaca",
    "brown-desert",
    "brown-grass",
    "brown-textile",
    "brush-daytime",
    "green-grass",
    "green-leaf",
    "green-trees",
    "led-light",
    "ocean-waves",
    "octopus-black",
    "orange-tulip",
    "purple-building",
    "purple-droplet",
    "purple-textile",
    "snow-mountain",
    "tree-water",
    "white-sheep",
    "wooden-surface",
];
for (const background of project_background_themes) {
    theme_entry_points[
        `project-background/${background}`
    ] = `./themes/common/css/project-background/${background}.scss`;
}

const webpack_config_for_burning_parrot_css = {
    entry: theme_entry_points,
    context,
    output,
    module: {
        rules: [webpack_configurator.rule_scss_loader, webpack_configurator.rule_css_assets],
    },
    plugins: [manifest_plugin, ...webpack_configurator.getCSSExtractionPlugins()],
};

const webpack_config_for_flaming_parrot_css = {
    entry: {
        "FlamingParrot/style": "./themes/FlamingParrot/css/style.scss",
        "FlamingParrot/print": "./themes/FlamingParrot/css/print.scss",
        "syntax-highlight": "./themes/common/css/syntax-highlight.scss",
    },
    context,
    output,
    module: {
        rules: [webpack_configurator.rule_scss_loader, webpack_configurator.rule_css_assets],
    },
    plugins: [manifest_plugin, ...webpack_configurator.getCSSExtractionPlugins()],
};

module.exports = [
    webpack_config_for_ckeditor,
    webpack_config_legacy_combined,
    webpack_config_for_rich_text_editor,
    webpack_config_for_tlp,
    webpack_config_for_tlp_doc,
    webpack_config_for_flaming_parrot_code,
    webpack_config_for_burning_parrot_code,
    webpack_config_for_vue,
    webpack_config_for_burning_parrot_css,
    webpack_config_for_flaming_parrot_css,
];
