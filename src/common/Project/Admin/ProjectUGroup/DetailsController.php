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

namespace Tuleap\Project\Admin\ProjectUGroup;

use Codendi_Request;
use ProjectUGroup;

class DetailsController
{
    /**
     * @var Codendi_Request
     */
    private $request;

    public function __construct(Codendi_Request $request)
    {
        $this->request = $request;
    }

    /**
     * @throws CannotCreateUGroupException
     */
    public function updateDetails(ProjectUGroup $ugroup)
    {
        $name = $this->request->getValidated('ugroup_name', 'String', '');
        $desc = $this->request->getValidated('ugroup_description', 'String', '');
        ugroup_update($ugroup->getProjectId(), $ugroup->getId(), $name, $desc);
    }
}
