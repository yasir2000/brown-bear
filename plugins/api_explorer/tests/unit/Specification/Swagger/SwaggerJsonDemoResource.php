<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\APIExplorer\Specification\Swagger;

use Luracast\Restler\RestException;
use Tuleap\REST\AuthenticatedResource;

final class SwaggerJsonDemoResource extends AuthenticatedResource
{
    /**
     * Get demo representation
     *
     * @url GET {id}/demo
     *
     * @param int $id ID of demo
     */
    public function get(int $id, string $some = 'default value'): SwaggerJsonDemoRepresentation
    {
        return new SwaggerJsonDemoRepresentation();
    }

    /**
     * Put demo representation
     *
     * @url PUT {id}/demo
     *
     * @param int $id ID of demo
     * @param SwaggerJsonDemoRepresentation $representation {@from body}
     *
     * @oauth2-scope write:demo
     *
     * @throws RestException 400
     */
    protected function put(int $id, SwaggerJsonDemoRepresentation $representation): void
    {
    }
}
