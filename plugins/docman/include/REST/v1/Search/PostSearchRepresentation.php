<?php
/**
 * Copyright (c) Enalean 2022 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\Docman\REST\v1\Search;

use Tuleap\Docman\REST\v1\SearchResource;

/**
 * @psalm-immutable
 */
final class PostSearchRepresentation
{
    /**
     * @var string search in all string properties {@from body} {@required false}
     */
    public string $global_search = '';
    /**
     * @var array {@type \Tuleap\Docman\REST\v1\Search\SearchPropertyRepresentation} {@from body} {@required false}
     */
    public array $properties = [];

    /**
     * @var int limit {@from body} {@required false} {@min 0} {@max 50}
     */
    public int $limit = SearchResource::MAX_LIMIT;

    /**
     * @var int offset {@from body} {@required false} {@min 0}
     */
    public int $offset = 0;
}
