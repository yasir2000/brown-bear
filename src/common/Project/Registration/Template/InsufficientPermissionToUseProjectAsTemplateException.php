<?php
/**
 * Copyright (c) BrownBear, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Project\Registration\Template;

use PFUser;
use Project;
use Project_Creation_Exception;

final class InsufficientPermissionToUseProjectAsTemplateException extends Project_Creation_Exception implements InvalidTemplateException
{
    private Project $project;
    private PFUser $user;

    public function __construct(Project $project, PFUser $user)
    {
        parent::__construct(sprintf('User #%d is not administrator of project #%d', $user->getId(), $project->getID()));
        $this->project = $project;
        $this->user    = $user;
    }

    public function getI18NMessage(): string
    {
        return _(sprintf(_('User #%d is not administrator of project #%d'), $this->user->getId(), $this->project->getID()));
    }
}
