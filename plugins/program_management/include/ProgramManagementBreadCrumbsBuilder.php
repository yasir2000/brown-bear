<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement;

use Project;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumb;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLink;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLinkCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLinkWithIcon;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbSubItems;
use Tuleap\Layout\BreadCrumbDropdown\SubItemsSection;

class ProgramManagementBreadCrumbsBuilder
{
    public function build(Project $project, \PFUser $user): BreadCrumbCollection
    {
        $breadcrumb = new BreadCrumb(
            new BreadCrumbLink(
                dgettext('tuleap-program_management', 'Program'),
                '/program_management/' . $project->getUnixNameLowerCase(),
            )
        );

        if ($user->isAdmin((int) $project->getID())) {
            $this->addADropdownWithLinkToGlobalAdministration($project, $breadcrumb);
        }

        $bread_crumb_collection = new BreadCrumbCollection();
        $bread_crumb_collection->addBreadCrumb($breadcrumb);

        return $bread_crumb_collection;
    }

    private function addADropdownWithLinkToGlobalAdministration(Project $project, BreadCrumb $breadcrumb): void
    {
        $global_admin_link = new BreadCrumbLinkWithIcon(
            dgettext('tuleap-program_management', 'Administration'),
            '/program_management/admin/' . $project->getUnixNameLowerCase(),
            'fa-cog'
        );

        $link_collection = new BreadCrumbLinkCollection();
        $link_collection->add($global_admin_link);

        $section = new SubItemsSection(
            '',
            $link_collection
        );

        $sub_items = new BreadCrumbSubItems();
        $sub_items->addSection($section);

        $breadcrumb->setSubItems(
            $sub_items
        );
    }
}
