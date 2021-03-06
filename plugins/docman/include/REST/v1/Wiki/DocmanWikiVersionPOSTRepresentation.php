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

/**
 * @psalm-immutable
 */
class DocmanWikiVersionPOSTRepresentation
{
    /**
     * @var string Title of version {@from body} {@required false}
     */
    public $version_title = '';

    /**
     * @var string Description of changes {@from body} {@required false}
     */
    public $change_log = '';

    /**
     * @var WikiPropertiesPOSTPATCHRepresentation Link properties must be set when creating a new file {@from body} {@type \Tuleap\Docman\REST\v1\Wiki\WikiPropertiesPOSTPATCHRepresentation} {@required true}
     */
    public $wiki_properties;

    /**
     * @var bool Lock file while updating {@from body} {@required true} {@type bool}
     */
    public $should_lock_file;
}
