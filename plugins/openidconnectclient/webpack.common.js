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

const path = require("path");
const webpack_configurator = require("../../tools/utils/scripts/webpack-configurator.js");

const entry_points = {
    default: "./themes/default/css/style.scss",
    "fp-style": "./themes/FlamingParrot/css/style.scss",
    "open-id-connect-client": "./scripts/open-id-connect-client.js",
    "user-account-style": "./themes/Account/style.scss",
    "bp-style": "./themes/BurningParrot/css/style.scss",
};

module.exports = [
    {
        entry: entry_points,
        context: path.resolve(__dirname),
        output: webpack_configurator.configureOutput(
            path.resolve(__dirname, "../../src/www/assets/openidconnectclient/")
        ),
        externals: {
            tlp: "tlp",
            jquery: "jQuery",
        },
        module: {
            rules: [webpack_configurator.rule_scss_loader, webpack_configurator.rule_css_assets],
        },
        plugins: [
            webpack_configurator.getCleanWebpackPlugin(),
            webpack_configurator.getManifestPlugin(),
            ...webpack_configurator.getCSSExtractionPlugins(),
        ],
    },
];
