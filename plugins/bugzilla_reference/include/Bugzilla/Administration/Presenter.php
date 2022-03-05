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

namespace Tuleap\Bugzilla\Administration;

use Codendi_HTMLPurifier;
use Tuleap\Reference\ReferenceValidator;

class Presenter
{
    public $bugzilla_title;
    public $references_configuration;
    public $under_construction;
    public $purified_no_bugzilla_reference;
    public $keyword;
    public $server;
    public $username;
    public $api_key;
    public $cancel;
    public $are_followup_private;
    public $has_references;
    public $follow_up;
    public $edit;
    public $change_api_key;
    public $delete;
    public $bugzilla_title_edit;
    public $bugzilla_add;
    public $bugzilla_edit;
    public $reference_pattern;
    public $api_key_stored_plaintext_warning;
    public $allowed_reference_description;
    public $rest_url;
    public $bugzilla_title_delete;
    public $bugzilla_delete;
    public $purified_rest_url_helper;

    /**
     * @var array
     */
    public $presenters;
    /**
     * @var \CSRFSynchronizerToken
     */
    public $csrf_token;

    public function __construct(array $presenters, \CSRFSynchronizerToken $csrf_token)
    {
        $this->presenters        = $presenters;
        $this->has_references    = count($presenters) > 0;
        $this->csrf_token        = $csrf_token;
        $this->reference_pattern = ReferenceValidator::REFERENCE_PATTERN;

        $this->bugzilla_title        = dgettext('tuleap-bugzilla_reference', 'Bugzilla configuration');
        $this->bugzilla_title_edit   = dgettext('tuleap-bugzilla_reference', 'Edit Bugzilla configuration');
        $this->bugzilla_title_delete = dgettext('tuleap-bugzilla_reference', 'Delete Bugzilla configuration');
        $this->bugzilla_add          = dgettext('tuleap-bugzilla_reference', 'Add reference');
        $this->bugzilla_edit         = dgettext('tuleap-bugzilla_reference', 'Edit reference');
        $this->bugzilla_delete       = dgettext('tuleap-bugzilla_reference', 'Delete reference');
        $this->keyword               = dgettext('tuleap-bugzilla_reference', 'Keyword');
        $this->server                = dgettext('tuleap-bugzilla_reference', 'Server');
        $this->username              = dgettext('tuleap-bugzilla_reference', 'Username');
        $this->api_key               = dgettext('tuleap-bugzilla_reference', 'API Key');
        $this->change_api_key        = dgettext('tuleap-bugzilla_reference', 'Change API Key');
        $this->follow_up             = dgettext('tuleap-bugzilla_reference', 'Follow Up');
        $this->cancel                = dgettext('tuleap-bugzilla_reference', 'Cancel');
        $this->delete                = dgettext('tuleap-bugzilla_reference', 'Delete');
        $this->edit                  = dgettext('tuleap-bugzilla_reference', 'Edit');
        $this->rest_url              = dgettext('tuleap-bugzilla_reference', 'REST API URL');

        $this->api_key_stored_plaintext_warning = dgettext(
            'tuleap-bugzilla_reference',
            'This API key has been stored in cleartext in the database, it is recommended to change it'
        );

        $this->allowed_reference_description = dgettext(
            'tuleap-bugzilla_reference',
            'Keyword must contain only alphanumeric and underscore characters'
        );
        $this->are_followup_private          = dgettext(
            'bugzilla_reference',
            'Comments added in Bugzilla will be flaged as private'
        );
        $this->bugzilla_delete_confirmation  = dgettext(
            'tuleap-bugzilla_reference',
            "Wow, wait a minute. You are about to delete the reference. Please confirm your action."
        );

        $purifier = Codendi_HTMLPurifier::instance();

        $this->purified_no_bugzilla_reference = $purifier->purify(
            dgettext(
                'tuleap-bugzilla_reference',
                'There is nothing here, <br> start by adding a Bugzilla reference'
            ),
            CODENDI_PURIFIER_LIGHT
        );

        $this->purified_rest_url_helper = $purifier->purify(
            dgettext(
                'tuleap-bugzilla_reference',
                'Server is the URL that will be used to build links to end users. <br>If defined, REST API URL is the one that will be used to issue REST calls'
            ),
            CODENDI_PURIFIER_LIGHT
        );
    }
}
