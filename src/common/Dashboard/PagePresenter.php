<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All rights reserved
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

namespace Tuleap\Dashboard;

use CSRFSynchronizerToken;

class PagePresenter
{
    public $can_update_dashboards;

    public $add_dashboard_label;
    public $dashboard_name_label;
    public $no_dashboard_label;
    public $no_widget_label;
    public string $no_widget_action_label;
    public $rss_label;
    public $edit_label;
    public $delete_label;

    public $cancel;
    public $close;
    /**
     * @var CSRFSynchronizerToken
     */
    public $csrf_token;
    public $url;

    public $delete_dashboard_label;
    public $edit_dashboard_label;
    public $delete_dashboard_title;
    public $edit_dashboard_title;
    public $add_widget_title;
    public $add_widget_list_title;
    public $add_widget_search_title;
    public $add_widget_table_title;
    public $add_widget_empty_search;
    public $add_widget_empty_state;

    public function __construct(CSRFSynchronizerToken $csrf, $url)
    {
        $this->csrf_token = $csrf;
        $this->url        = $url;

        $this->can_update_dashboards = true;

        $this->add_dashboard_label     = _('Add dashboard');
        $this->delete_dashboard_title  = _('Delete dashboard');
        $this->delete_dashboard_label  = _('Delete dashboard');
        $this->edit_dashboard_title    = _('Edit dashboard');
        $this->add_widget_title        = _('Add widget');
        $this->add_widget_list_title   = _('Widgets');
        $this->add_widget_search_title = _('Name, type...');
        $this->add_widget_table_title  = _('Name');
        $this->add_widget_empty_search = _("There isn't any matching widgets");
        $this->add_widget_empty_state  = _('Start by choosing a widget');
        $this->edit_dashboard_label    = _('Edit dashboard');
        $this->dashboard_name_label    = _('Dashboard name');
        $this->no_dashboard_label      = _("You don't have any dashboards");
        $this->no_widget_label         = _('There are no widgets here');
        $this->no_widget_action_label  = _("Why don't you start by editing your dashboard by adding some widgets?");

        $this->rss_label    = _('RSS');
        $this->edit_label   = _('Edit');
        $this->delete_label = _('Delete');

        $this->cancel = _('Cancel');
        $this->close  = _('Close');
    }
}
