<?php
/**
 * Copyright (c) BrownBear, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\GitLFS\Transfer\Basic;

final class LFSBasicTransferObjectIntegrityException extends LFSBasicTransferException
{
    public function __construct($expected_oid_value, $real_oid_value)
    {
        parent::__construct(
            "Received object has an incorrect ID, expected $expected_oid_value got $real_oid_value"
        );
    }
}
