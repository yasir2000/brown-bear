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

namespace Tuleap\Docman\REST\v1\Wiki;

use Tuleap\Docman\REST\v1\ItemRepresentation;
use Tuleap\Docman\REST\v1\Metadata\ItemStatusMapper;

/**
 * @psalm-immutable
 */
class DocmanWikiPATCHRepresentation extends DocmanWikiVersionPOSTRepresentation
{
    /**
     * @var string Title of the wiki {@from body} {@required true} {@type string}
     */
    public $title = '';
    /**
     * @var string Description of the wiki {@from body} {@required false} {@type string}
     */
    public $description;
    /**
     * @var string | null Item status {@from body} {@required false} {@choice none,draft,approved,rejected}
     */
    public $status = ItemStatusMapper::ITEM_STATUS_NONE;
    /**
     * @var string | null Obsolescence date {@from body} {@required false}
     */
    public $obsolescence_date = ItemRepresentation::OBSOLESCENCE_DATE_NONE;
}
