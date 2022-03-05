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

namespace Tuleap\PullRequest\Reference;

use pullrequestPlugin;

class Reference extends \Reference
{
    /**
     * @return Reference
     */
    public function __construct($keyword, $html_url, $project_id)
    {
        $base_id     = 0;
        $description = '';
        $visibility  = 'S';
        $is_used     = 1;
        $url         = $html_url;

        parent::__construct(
            $base_id,
            $keyword,
            $description,
            $url,
            $visibility,
            'plugin_pullrequest',
            pullrequestPlugin::REFERENCE_NATURE,
            $is_used,
            $project_id
        );
    }
}
