<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\LesserThanOrEqualComparison;

use CodendiDataAccess;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\DateTimeConditionBuilder;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\DateTimeValueRounder;

class ForDateTime implements DateTimeConditionBuilder
{
    /**
     * @var DateTimeValueRounder
     */
    private $date_time_value_rounder;

    public function __construct(DateTimeValueRounder $date_time_value_rounder)
    {
        $this->date_time_value_rounder = $date_time_value_rounder;
    }

    public function getCondition($value, $changeset_value_date_alias)
    {
        $ceiled_timestamp = $this->date_time_value_rounder->getCeiledTimestampFromDateTime($value);
        $ceiled_timestamp = CodendiDataAccess::instance()->escapeInt($ceiled_timestamp);

        return "$changeset_value_date_alias.value <= $ceiled_timestamp";
    }
}
