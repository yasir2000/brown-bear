<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\Git;

use CSRFSynchronizerToken;
use Git_AdminPresenter;

class GeneralSettingsPresenter extends Git_AdminPresenter
{
    public $general_settings_active = 'tlp-tab-active';
    public $manage_general_settings = true;
    public $access_control_title;
    public $allow_regexp_info;
    public $is_activated;
    public $csrf_token;
    public $warning_admin_activation;

    public function __construct($title, CSRFSynchronizerToken $csrf, $is_activated)
    {
        parent::__construct($title, $csrf);

        $this->csrf_token   = $csrf;
        $this->is_activated = $is_activated;

        $this->access_control_title     = dgettext('tuleap-git', 'Access control settings');
        $this->allow_regexp_info        = dgettext('tuleap-git', 'Allow usage of regular expressions in branches and tags during repository permission definition');
        $this->warning_admin_activation = dgettext('tuleap-git', 'Enabling this option can have security implications, with this feature a git administrator can do a denial of service attack. Please also note that this option might end up in non working state. Invalid regular expressions will be ignored silently.');
    }
}
