<?php
/**
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

declare(strict_types=1);

namespace Tuleap\Tracker\REST\v1\Workflow\PostAction;

use Tuleap\REST\JsonCast;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsets;

class HiddenFieldsetsRepresentation extends PostActionRepresentation
{
    /**
     * @var int[]
     */
    public $fieldset_ids;

    /**
     * @param int[] $field_ids
     */
    private function __construct(int $id, array $field_ids)
    {
        $this->id           = $id;
        $this->type         = HiddenFieldsets::SHORT_NAME;
        $this->fieldset_ids = $field_ids;
    }

    /**
     * @param int[] $field_ids
     */
    public static function build(int $id, array $field_ids): HiddenFieldsetsRepresentation
    {
        $int_field_ids = JsonCast::toArrayOfInts($field_ids);
        if ($int_field_ids === null) {
            $int_field_ids = [];
        }

        return new self(
            JsonCast::toInt($id),
            $int_field_ids
        );
    }
}
