<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\Label\Widget;

class ProjectLabelSelectorPresenter
{
    public $project_id;
    /**
     * @var array
     */
    public $selected_labels;
    public $label_title;
    public $label_placeholder;

    public function __construct($project_id, array $selected_labels)
    {
        $this->label_title       = dgettext('tuleap-label', 'Label');
        $this->label_placeholder = dgettext('tuleap-label', 'Select labels');
        $this->project_id        = $project_id;
        $this->selected_labels   = $selected_labels;
    }
}
