<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\Git\DefaultSettings\Pane;

use CSRFSynchronizerToken;
use Git;
use Project;

class AccessControlPresenter
{
    public $project_id;
    public $manage_default_settings;
    public $read_options;
    public $write_options;
    public $rewrite_options;
    public $csrf_token;
    public $are_fine_grained_permissions_defined;
    public $can_use_fine_grained_permissions;
    public $cannot_define_per_repo_permissions;
    public $are_regexp_permission_activated_at_site_level;
    public $is_regexp_enabled;
    public $are_regexp_permission_conflicting_at_site_level;
    public $branches_permissions;
    public $tags_permissions;
    public $default_permissions_title;
    public $fine_grained_permissions_title;
    public $branches_title;
    public $tags_title;
    public $add_branch_permission;
    public $add_tag_permission;
    public $pattern_column;
    public $actions_column;
    public $empty;
    public $new_fine_grained_ugroups;
    public $remove_form_action;
    public $delete_challenge;
    public $remove_fine_grained_permission_delete;
    public $remove_fine_grained_permission_desc;
    public $remove_fine_grained_permission_confirm;
    public $regexp_permission_label;
    public $regexp_incoherence_label;
    public $title_warning_regexp_uncheck;
    public $btn_cancel;
    public $save_permissions;
    public $is_fork;
    public $disabled;
    public $warnings;
    public $is_control_limited;
    public $template_git_access_rights;
    public $default_access_rights_form_action;
    public $label_read;
    public $label_write;
    public $label_rw;
    public $read_select_box_id;
    public $write_select_box_id;
    public $rewrite_select_box_id;
    public $submit_default_access_rights;
    public $fine_grained_permissions_checkbox_label;
    public $has_branches_permissions;
    public $has_tags_permissions;

    public function __construct(
        Project $project,
        CSRFSynchronizerToken $csrf_token,
        array $read_options,
        array $write_options,
        array $rewrite_options,
        $are_fine_grained_permissions_defined,
        $can_use_fine_grained_permissions,
        array $branches_permissions_representation,
        array $tags_permissions_representation,
        array $new_fine_grained_ugroups,
        $delete_url,
        CSRFSynchronizerToken $csrf_delete,
        $are_regexp_permission_activated_at_site_level,
        $is_regexp_enabled_at_global_level,
        $are_regexp_permission_conflicting_at_site_level,
    ) {
        $this->project_id                                      = $project->getID();
        $this->manage_default_settings                         = true;
        $this->read_options                                    = $read_options;
        $this->write_options                                   = $write_options;
        $this->rewrite_options                                 = $rewrite_options;
        $this->csrf_token                                      = $csrf_token;
        $this->are_fine_grained_permissions_defined            = $are_fine_grained_permissions_defined;
        $this->can_use_fine_grained_permissions                = $can_use_fine_grained_permissions;
        $this->cannot_define_per_repo_permissions              = $are_fine_grained_permissions_defined;
        $this->are_regexp_permission_activated_at_site_level   = $are_regexp_permission_activated_at_site_level;
        $this->is_regexp_enabled                               = $is_regexp_enabled_at_global_level;
        $this->are_regexp_permission_conflicting_at_site_level = $are_regexp_permission_conflicting_at_site_level;

        $this->branches_permissions = $branches_permissions_representation;
        $this->tags_permissions     = $tags_permissions_representation;

        $this->default_permissions_title = dgettext('tuleap-git', 'Default permissions');

        $this->fine_grained_permissions_title = dgettext('tuleap-git', 'Fine-grained permissions');

        $this->branches_title = dgettext('tuleap-git', 'Branches');

        $this->tags_title = dgettext('tuleap-git', 'Tags');

        $this->add_branch_permission = dgettext('tuleap-git', 'Add permission on a new branch');

        $this->add_tag_permission = dgettext('tuleap-git', 'Add permission on a new tag');

        $this->pattern_column = dgettext('tuleap-git', 'Pattern');

        $this->actions_column = dgettext('tuleap-git', 'Actions');

        $this->empty = dgettext('tuleap-git', 'No permission defined yet.');

        $this->new_fine_grained_ugroups = $new_fine_grained_ugroups;
        $this->remove_form_action       = $delete_url;
        $this->delete_challenge         = $csrf_delete->getToken();

        $this->remove_fine_grained_permission_delete = dgettext('tuleap-git', 'Delete');

        $this->remove_fine_grained_permission_desc = dgettext('tuleap-git', 'You are about to remove this permission. Please confirm your action.');

        $this->remove_fine_grained_permission_confirm = dgettext('tuleap-git', 'Confirm deletion');

        $this->regexp_permission_label  = dgettext('tuleap-git', 'Enable regular expressions in branches and tags pattern');
        $this->regexp_incoherence_label = dgettext('tuleap-git', 'Enabling this option might end up in non working state. Invalid regular expressions will be ignored without errors.');

        $this->title_warning_regexp_uncheck = dgettext('tuleap-git', 'Some rules may be deleted');

        $this->btn_cancel       = $GLOBALS['Language']->getText('global', 'btn_cancel');
        $this->save_permissions = dgettext('tuleap-git', 'Save permissions');
        $this->is_fork          = false;
        $this->disabled         = '';
        $this->warnings         = $this->getWarningContentForRegexpDisableModal();

        $this->is_control_limited         = false;
        $this->template_git_access_rights = dgettext('tuleap-git', 'Git access control template');

        $this->default_access_rights_form_action = '/plugins/git/?group_id=' . $this->project_id . '&action=admin-default-access-rights';

        $this->label_read  = dgettext('tuleap-git', 'Read');
        $this->label_write = dgettext('tuleap-git', 'Write');
        $this->label_rw    = dgettext('tuleap-git', 'Rewind');

        $this->read_select_box_id    = 'default_access_rights[' . Git::DEFAULT_PERM_READ . ']';
        $this->write_select_box_id   = 'default_access_rights[' . Git::DEFAULT_PERM_WRITE . ']';
        $this->rewrite_select_box_id = 'default_access_rights[' . Git::DEFAULT_PERM_WPLUS . ']';

        $this->submit_default_access_rights            = dgettext('tuleap-git', 'Save');
        $this->fine_grained_permissions_checkbox_label = dgettext('tuleap-git', 'Enable fine-grained permissions');

        $this->has_branches_permissions = count($this->branches_permissions) > 0;
        $this->has_tags_permissions     = count($this->tags_permissions) > 0;
    }


    private function getWarningContentForRegexpDisableModal()
    {
        if ($this->are_regexp_permission_conflicting_at_site_level) {
            $warning[]['message'] = dgettext('tuleap-git', 'The regular expressions option has been disabled at plateform level.');
            $warning[]['message'] = dgettext('tuleap-git', 'All rules containing regular expressions will be deleted and you won\'t be able to activate the option again. If you don\'t save your modifications, the current state will still work.');
            $warning[]['message'] = dgettext('tuleap-git', 'Please confirm your action.');
        } else {
            $warning[]['message'] = dgettext('tuleap-git', 'All rules containing regular expressions will be deleted. Please confirm the desactivation of regular expressions.');
        }

        return $warning;
    }
}
