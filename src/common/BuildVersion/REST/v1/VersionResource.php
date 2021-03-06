<?php
/**
 * Copyright (c) BrownBear, 2021 - present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\BuildVersion\REST\v1;

use Tuleap\BuildVersion\FlavorFinderFromFilePresence;
use Tuleap\BuildVersion\VersionPresenter;
use Tuleap\REST\Header;

class VersionResource
{
    public const ROUTE = 'version';

    /**
     * @url    OPTIONS
     * @access public
     */
    public function options(): void
    {
        Header::allowOptionsGet();
    }

    /**
     * Get the Tuleap build version
     *
     * @url    GET
     *
     * @access public
     *
     *
     */
    public function get(): VersionRepresentation
    {
        $version = VersionPresenter::fromFlavorFinder(new FlavorFinderFromFilePresence());
        return new VersionRepresentation($version->flavor_name, $version->version_number);
    }
}
