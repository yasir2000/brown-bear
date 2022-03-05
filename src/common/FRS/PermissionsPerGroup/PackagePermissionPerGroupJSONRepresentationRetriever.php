<?php
/**
 * Copyright Enalean (c) 2018 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\FRS\PermissionsPerGroup;

use Project;
use Service;

class PackagePermissionPerGroupJSONRepresentationRetriever
{
    /**
     * @var PackagePermissionPerGroupRepresentationBuilder
     */
    private $package_representation_builder;

    public function __construct(
        PackagePermissionPerGroupRepresentationBuilder $package_representation_builder,
    ) {
        $this->package_representation_builder = $package_representation_builder;
    }

    public function retrieve(Project $project, $selected_ugroup_id)
    {
        if (! $project->usesService(Service::FILE)) {
            $GLOBALS['Response']->send400JSONErrors(
                [
                    'error' => _(
                        "Files service is disabled for this project."
                    ),
                ]
            );
        }

        $representations = $this->package_representation_builder->build($project, $selected_ugroup_id);

        $GLOBALS['Response']->sendJSON($representations);
    }
}
