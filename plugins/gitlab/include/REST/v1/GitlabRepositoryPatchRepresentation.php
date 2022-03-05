<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Gitlab\REST\v1;

/**
 * @psalm-immutable
 */
final class GitlabRepositoryPatchRepresentation
{
    /**
     * @var GitlabRepositoryBotApiTokenPatchRepresentation | null {@type \Tuleap\Gitlab\REST\v1\GitlabRepositoryBotApiTokenPatchRepresentation} {@required false}
     */
    public $update_bot_api_token;

    /**
     * @var bool | null {@required false}
     */
    public $generate_new_secret;

    /**
     * @var bool | null {@required false}
     */
    public $allow_artifact_closure;

    /**
     * @var string | null {@required false}
     */
    public $create_branch_prefix;
}
