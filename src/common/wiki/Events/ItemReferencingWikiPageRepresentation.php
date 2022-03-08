<?php
/**
 * Copyright (c) BrownBear, 2019 - present. All Rights Reserved.
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

namespace Tuleap\wiki\Events;

use Tuleap\REST\JsonCast;

class ItemReferencingWikiPageRepresentation
{
    /**
     * @var int
     */
    public $item_id;

    /**
     * @var String
     */
    public $item_name;

    public function __construct(int $item_id, string $item_name)
    {
        $this->item_id   = JsonCast::toInt($item_id);
        $this->item_name = $item_name;
    }
}
