<?php
/**
 * Copyright BrownBear (c) 2020 - Present. All rights reserved.
 *
 * Tuleap and BrownBear names and logos are registered trademarks owned by
 * BrownBear SAS. All other trademarks or names are properties of their respective
 * owners.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\HudsonGit\REST;

use Tuleap\Project\REST\ProjectRepresentation;
use Tuleap\HudsonGit\REST\v1\GitJenkinsServersResource;

/**
 * Inject resource into restler
 */
class ResourcesInjector
{
    public function populate(\Luracast\Restler\Restler $restler): void
    {
        $restler->addAPIClass(
            GitJenkinsServersResource::class,
            ProjectRepresentation::ROUTE
        );
    }
}
