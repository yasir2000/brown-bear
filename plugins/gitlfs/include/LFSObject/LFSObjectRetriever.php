<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\GitLFS\LFSObject;

class LFSObjectRetriever
{
    /**
     * @var LFSObjectDAO
     */
    private $dao;

    public function __construct(LFSObjectDAO $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @return LFSObject[]
     */
    public function getExistingLFSObjectsFromTheSetForRepository(\GitRepository $repository, LFSObject ...$lfs_objects)
    {
        $objects_by_oid_value = [];
        foreach ($lfs_objects as $lfs_object) {
            $objects_by_oid_value[$lfs_object->getOID()->getValue()] = $lfs_object;
        }

        $existing_lfs_object_rows = $this->dao->searchByRepositoryIDAndOIDs(
            $repository->getId(),
            array_keys($objects_by_oid_value)
        );

        $existing_lfs_objects = [];
        foreach ($existing_lfs_object_rows as $existing_lfs_object_row) {
            $existing_lfs_objects[] = $objects_by_oid_value[$existing_lfs_object_row['object_oid']];
        }

        return $existing_lfs_objects;
    }

    /**
     * @return LFSObject|null
     */
    public function getLFSObjectForRepository(\GitRepository $repository, $lfs_oid)
    {
        $row = $this->dao->searchByRepositoryIDAndOIDs($repository->getId(), [$lfs_oid]);

        if (isset($row[0])) {
            return new LFSObject(new LFSObjectID($row[0]['object_oid']), $row[0]['object_size']);
        }

        return null;
    }

    /**
     * @return bool
     */
    public function doesLFSObjectExistsForRepository(\GitRepository $repository, LFSObject $lfs_object)
    {
        return count($this->getExistingLFSObjectsFromTheSetForRepository($repository, $lfs_object)) === 1;
    }

    /**
     * @return bool
     */
    public function doesLFSObjectExists(LFSObject $lfs_object)
    {
        return $this->dao->searchByOIDValue($lfs_object->getOID()->getValue()) !== null;
    }
}
