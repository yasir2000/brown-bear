<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Project\Admin\Categories;

use Project;
use ProjectHistoryDao;
use TroveCat;
use TroveCatFactory;

class ProjectCategoriesUpdater
{
    private TroveCatFactory $factory;
    private ProjectHistoryDao $history_dao;
    private TroveSetNodeFacade $set_node_facade;

    public function __construct(
        TroveCatFactory $factory,
        ProjectHistoryDao $history_dao,
        TroveSetNodeFacade $set_node_facade,
    ) {
        $this->factory         = $factory;
        $this->history_dao     = $history_dao;
        $this->set_node_facade = $set_node_facade;
    }

    /**
     * @throws NotRootCategoryException
     * @throws NbMaxValuesException
     * @throws MissingMandatoryCategoriesException
     */
    public function update(Project $project, CategoryCollection $submitted_categories): void
    {
        foreach ($submitted_categories->getRootCategories() as $category) {
            $this->doUpdate($project, $category);
        }
    }

    private function doUpdate(Project $project, TroveCat $root_category): void
    {
        $this->history_dao->groupAddHistory('changed_trove', '', $project->getID());

        $this->factory->removeProjectTopCategoryValue($project, $root_category->getId());
        foreach ($root_category->getChildren() as $selected_category) {
            $this->set_node_facade->setNode($project, $selected_category->getId(), $root_category->getId());
        }
    }
}
