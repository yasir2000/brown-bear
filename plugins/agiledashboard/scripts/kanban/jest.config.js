/*
 * Copyright (c) BrownBear, 2019-Present. All Rights Reserved.
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

process.env.DISABLE_TS_TYPECHECK = "true";

const base_config = require("../../../../tests/jest/jest.base.config.js");

module.exports = {
    ...base_config,
    displayName: "kanban",
    setupFiles: ["./tests/jest.setup.js"],
    moduleNameMapper: {
        ...base_config.moduleNameMapper,
        "^.+\\.html$": "identity-obj-proxy",
        // The artifact modal module causes lots of WARNING: Tried to load AngularJS more than once.
        "^angular$": "<rootDir>/node_modules/angular/index.js",
    },
    testRunner: "jest-jasmine2",
};
