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

namespace Tuleap\Bugzilla;

use CrossReference;
use CrossReferenceDao;
use Tuleap\Bugzilla\Reference\Reference;
use Tuleap\Bugzilla\Reference\RESTReferenceCreator;

class CrossReferenceCreator
{
    /**
     * @var CrossReferenceDao
     */
    private $dao;
    /**
     * @var RESTReferenceCreator
     */
    private $rest_reference_creator;

    public function __construct(CrossReferenceDao $dao, RESTReferenceCreator $rest_reference_creator)
    {
        $this->dao                    = $dao;
        $this->rest_reference_creator = $rest_reference_creator;
    }

    public function create(CrossReference $cross_reference, Reference $bugzilla_reference)
    {
        if (! $this->dao->fullReferenceExistInDb($cross_reference)) {
            $this->dao->createDbCrossRef($cross_reference);
            $this->rest_reference_creator->create($cross_reference, $bugzilla_reference);
        }
    }
}
