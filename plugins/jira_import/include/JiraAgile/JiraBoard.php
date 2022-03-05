<?php
/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\JiraImport\JiraAgile;

/**
 * @psalm-immutable
 */
final class JiraBoard
{
    /**
     * @var int
     */
    public $id;
    /**
     * @var string
     */
    public $url;
    /**
     * @var int
     */
    public $project_id;
    /**
     * @var string
     */
    public $project_key;

    public function __construct(int $id, string $url, int $project_id, string $project_key)
    {
        $this->id          = $id;
        $this->url         = $url;
        $this->project_id  = $project_id;
        $this->project_key = $project_key;
    }

    public static function buildFakeBoard(): self
    {
        return new self(1, 'https://example.com/rest/agile/latest/board/1', 10000, 'FOO');
    }
}
