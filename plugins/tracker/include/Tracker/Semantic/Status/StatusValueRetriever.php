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

namespace Tuleap\Tracker\Semantic\Status;

use PFUser;
use Tracker;
use Tracker_FormElement_Field_List_BindValue;
use Tracker_Semantic_StatusFactory;

class StatusValueRetriever
{
    /**
     * @var Tracker_Semantic_StatusFactory
     */
    private $semantic_status_factory;

    public function __construct(Tracker_Semantic_StatusFactory $semantic_status_factory)
    {
        $this->semantic_status_factory = $semantic_status_factory;
    }

    /**
     * @throws SemanticStatusNotDefinedException
     * @throws SemanticStatusClosedValueNotFoundException
     */
    public function getFirstClosedValueUserCanRead(Tracker $tracker, PFUser $user): Tracker_FormElement_Field_List_BindValue
    {
        $status_semantic_defined = $this->getStatusSemanticDefined($tracker, $user);
        $open_values             = $status_semantic_defined->getOpenValues();
        $status_field            = $status_semantic_defined->getField();
        foreach ($status_field->getAllValues() as $value_id => $value) {
            if (! $value->isHidden() && ! in_array($value_id, $open_values)) {
                return $value;
            }
        }

        throw new SemanticStatusClosedValueNotFoundException();
    }

    /**
     * @throws SemanticStatusNotDefinedException
     * @throws SemanticStatusClosedValueNotFoundException
     */
    public function getFirstOpenValueUserCanRead(Tracker $tracker, PFUser $user): Tracker_FormElement_Field_List_BindValue
    {
        $status_semantic_defined = $this->getStatusSemanticDefined($tracker, $user);
        $open_values             = $status_semantic_defined->getOpenValues();
        $status_field            = $status_semantic_defined->getField();
        foreach ($status_field->getAllValues() as $value_id => $value) {
            if (! $value->isHidden() && in_array($value_id, $open_values)) {
                return $value;
            }
        }

        throw new SemanticStatusOpenValueNotFoundException();
    }

    /**
     * @throws SemanticStatusNotDefinedException
     */
    private function getStatusSemanticDefined(Tracker $tracker, PFUser $user): StatusSemanticDefined
    {
        $semantic_status       = $this->semantic_status_factory->getByTracker($tracker);
        $semantic_status_field = $semantic_status->getField();
        if (
            $semantic_status_field === null ||
            ! $semantic_status_field->userCanRead($user)
        ) {
            throw new SemanticStatusNotDefinedException();
        }

        return new StatusSemanticDefined(
            $semantic_status_field,
            $semantic_status->getOpenValues()
        );
    }
}
