<?php
/**
 * Copyright (c) Enalean, 2016 - present. All rights reserved
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

namespace Tuleap\Project;

class DescriptionFieldsFactory
{
    private DescriptionFieldsDao $dao;

    public function __construct(DescriptionFieldsDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @psalm-return list<array{group_desc_id: int, desc_required: int, desc_name: string, desc_description: string, desc_rank: int, desc_type: string}>
     */
    public function getAllDescriptionFields(): array
    {
        return $this->dao->searchAll();
    }

    public function isLegacyLongDescriptionFieldExisting(): bool
    {
        return $this->dao->isFieldExisting(
            101,
            'project_desc_name:full_desc'
        );
    }

    public function getPaginatedDescriptionFields(int $limit, int $offset): array
    {
        return $this->dao->searchFieldsWithPagination($limit, $offset);
    }
}
