<?php
/**
* Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Workflow;

use Tuleap\Tracker\Artifact\Renderer\ListPickerIncluder;

class WorkflowMenuTabPresenter
{
    public $tabs_menu;
    public $tracker_id;
    /** @var string */
    public $used_services_names;
    /** @var string */
    public $is_list_picker_enabled;

    public function __construct(array $tabs_menu, $tracker_id, array $used_services_names)
    {
        $this->tabs_menu              = $tabs_menu;
        $this->tracker_id             = $tracker_id;
        $this->used_services_names    = json_encode($used_services_names);
        $this->is_list_picker_enabled = json_encode(ListPickerIncluder::isListPickerEnabledAndBrowserCompatible(
            $this->tracker_id
        ));
    }
}
