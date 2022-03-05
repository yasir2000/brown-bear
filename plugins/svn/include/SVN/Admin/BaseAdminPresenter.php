<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\SVN\Admin;

class BaseAdminPresenter
{
    public $notification_active;
    public $access_control_active;
    public $immutable_tag_url_active;
    public $commit_rule;
    public $repository_delete_active;

    public function __construct()
    {
        $this->notification_active      = false;
        $this->access_control_active    = false;
        $this->immutable_tag_url_active = false;
        $this->commit_rule_active       = false;
        $this->repository_delete_active = false;
    }
}
