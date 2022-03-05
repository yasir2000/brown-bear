<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class AgileDashboard_KanbanColumnFactory
{
    /**
     * @var AgileDashboard_KanbanUserPreferences
     */
    private $user_preferences;

    /**
     * @var KanbanColumnDao
     */
    private $column_dao;

    public function __construct(
        AgileDashboard_KanbanColumnDao $column_dao,
        AgileDashboard_KanbanUserPreferences $user_preferences,
    ) {
        $this->column_dao       = $column_dao;
        $this->user_preferences = $user_preferences;
    }

    /**
     *
     * @return AgileDashboard_KanbanColumn[]
     */
    public function getAllKanbanColumnsForAKanban(AgileDashboard_Kanban $kanban, PFUser $user)
    {
        $columns  = [];
        $semantic = $this->getSemanticStatus($kanban);
        if (! $semantic) {
            return $columns;
        }

        $field_values = $this->getFieldValues($semantic);
        $open_values  = $this->getOpenValues($semantic);

        foreach ($field_values as $field_value) {
            $id = $field_value->getId();
            if (in_array($id, $open_values)) {
                $columns[] = $this->instantiate($kanban, $id, $user, $field_values);
            }
        }

        return $columns;
    }

    /**
     * @param int                   $column_id
     *
     * @return AgileDashboard_KanbanColumn
     */
    public function getColumnForAKanban(AgileDashboard_Kanban $kanban, $column_id, PFUser $user)
    {
        $semantic = $this->getSemanticStatus($kanban);
        if (! $semantic) {
            throw new AgileDashboard_SemanticStatusNotFoundException();
        }

        $open_values = $this->getOpenValues($semantic);

        foreach ($open_values as $id) {
            if ($id == $column_id) {
                $field_values = $this->getFieldValues($semantic);

                return $this->instantiate($kanban, $id, $user, $field_values);
            }
        }

        throw new AgileDashboard_KanbanColumnNotFoundException($kanban, $column_id);
    }

    private function instantiate(AgileDashboard_Kanban $kanban, $id, PFUser $user, $field_values)
    {
        return new AgileDashboard_KanbanColumn(
            $id,
            $kanban->getId(),
            $field_values[$id]->getLabel(),
            $this->user_preferences->isColumnOpen($kanban, $id, $user),
            $this->getColorForColumn($id),
            $this->getWIPLimitForColumn($kanban, $id),
            $this->isColumnRemovable($kanban, $field_values[$id])
        );
    }

    private function getColorForColumn($column_id)
    {
        return null;
    }

    private function getWIPLimitForColumn(AgileDashboard_Kanban $kanban, $column_id)
    {
        $row = $this->column_dao->getColumnWipLimit($kanban->getId(), $column_id)->getRow();

        if (! $row) {
            return null;
        }

        return $row['wip_limit'];
    }

    private function isColumnRemovable(AgileDashboard_Kanban $kanban, Tracker_FormElement_Field_List_Bind_StaticValue $value)
    {
        $semantic = $this->getSemanticStatus($kanban);

        if (! $semantic) {
            return;
        }

        return $semantic->getField()->getBind()->canValueBeHiddenWithoutCheckingSemanticStatus($value) && ! $semantic->isBasedOnASharedField();
    }

    private function getOpenValues(Tracker_Semantic_Status $semantic)
    {
        return $semantic->getOpenValues();
    }

    private function getFieldValues(Tracker_Semantic_Status $semantic)
    {
        return $semantic->getField()->getAllValues();
    }

    private function getSemanticStatus(AgileDashboard_Kanban $kanban)
    {
        $tracker = TrackerFactory::instance()->getTrackerById($kanban->getTrackerId());
        if (! $tracker) {
            return;
        }

        $semantic = Tracker_Semantic_Status::forceLoad($tracker);
        if (! $semantic->getFieldId()) {
            return;
        }

        return $semantic;
    }
}
