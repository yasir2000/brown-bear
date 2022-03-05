<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\REST\v1;

use Tuleap\User\REST\MinimalUserRepresentation;
use Tuleap\REST\JsonCast;
use Codendi_HTMLPurifier;

/**
 * @psalm-immutable
 */
class TimelineInlineCommentRepresentation
{
    public const TYPE = 'inline-comment';

    /**
     * @var string {@type string}
     */
    public $file_path;

    /**
     * @var int {@type int}
     */
    public $unidiff_offset;

    /**
     * @var MinimalUserRepresentation {@type MinimalUserRepresentation}
     */
    public $user;

    /**
     * @var string {@type string}
     */
    public $post_date;

    /**
     * @var string {@type string}
     */
    public $content;

    /**
     * @var bool {@type bool}
     */
    public $is_outdated;

    /**
     * @var string {@type string}
     */
    public $type;


    public function __construct(string $file_path, int $unidiff_offset, MinimalUserRepresentation $user, int $post_date, string $content, bool $is_outdated, int $project_id)
    {
        $this->file_path      = $file_path;
        $this->unidiff_offset = $unidiff_offset;
        $this->user           = $user;
        $this->post_date      = JsonCast::toDate($post_date);
        $this->content        = self::getPurifiedContent($project_id, $content);
        $this->is_outdated    = $is_outdated;
        $this->type           = self::TYPE;
    }

    private static function getPurifiedContent(int $project_id, string $content): string
    {
        $purifier = Codendi_HTMLPurifier::instance();
        return $purifier->purify($content, CODENDI_PURIFIER_LIGHT, $project_id);
    }
}
