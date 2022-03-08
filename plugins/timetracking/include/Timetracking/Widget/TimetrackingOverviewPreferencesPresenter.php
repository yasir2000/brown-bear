<?php
/**
 * Copyright BrownBear (c) 2019 - present. All rights reserved.
 *
 *  Tuleap and BrownBear names and logos are registrated trademarks owned by
 *  BrownBear SAS. All other trademarks or names are properties of their respective
 *  owners.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);

namespace Tuleap\Timetracking\Widget;

class TimetrackingOverviewPreferencesPresenter
{
    public $widget_id;
    public $title;

    public function __construct(int $widget_id, string $title)
    {
        $this->widget_id = $widget_id;
        $this->title     = $title;
    }
}
