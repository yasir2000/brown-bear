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

use Project_Creation_Exception;

final class ProjectTemplateIDInvalidException extends Project_Creation_Exception implements InvalidTemplateException
{
    private int $invalid_template_id;

    public function __construct(int $invalid_template_id)
    {
        parent::__construct('Template #' . $invalid_template_id . ' is not valid');
        $this->invalid_template_id = $invalid_template_id;
    }

    public function getI18NMessage(): string
    {
        return sprintf(_('Template #%d is not valid'), $this->invalid_template_id);
    }
}
