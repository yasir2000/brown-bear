<?php
/**
 * Copyright (c) BrownBear, 2012-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2007.
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

class Rule_Date_Time extends \Rule // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    public const DAYTIME_REGEX = '/^(\d{1,4})-(\d{1,2})-(\d{1,2}?) (\d{2}):(\d{2})(?::\d{2})?$/';
    public function isValid($val)
    {
        if (! \preg_match(self::DAYTIME_REGEX, $val, $m)) {
            return \false;
        }
        return (bool) \strtotime($val);
    }
}
