<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Tuleap\ConcurrentVersionsSystem\ServiceCVS;

require_once __DIR__ . '/commit_utils.php';

$request  = HTTPRequest::instance();
$group_id = $request->get('group_id');

if (! $group_id) {
    exit_no_group(); // need a group_id !!!
}

$pm      = ProjectManager::instance();
$project = $pm->getProject($group_id);
$service = $project->getService(\Service::CVS);
if (! ($service instanceof ServiceCVS)) {
    exit_error(
        $GLOBALS['Language']->getText('global', 'error'),
        _('This Project Has Turned CVS Off')
    );
}

$service->displayCVSRepositoryHeader(
    $request->getCurrentUser(),
    _('CVS Information'),
    'info',
);

// Table for summary info
echo '<div class="cvs-intro">';
echo '<TABLE width="100%"><TR valign="top"><TD width="65%">' . "\n";

// Get group properties
$res_grp = db_query("SELECT * FROM `groups` WHERE group_id=" . db_ei($group_id));
$row_grp = db_fetch_array($res_grp);

$purifier = Codendi_HTMLPurifier::instance();

// Show CVS access information
if ($row_grp['cvs_preamble'] != '') {
    echo $purifier->purify(util_unconvert_htmlspecialchars($row_grp['cvs_preamble']));
} else {
    include($GLOBALS['Language']->getContent('cvs/intro'));
}

// Summary info
echo '</TD><TD width="25%">';
echo $HTML->box1_top(_('Repository History'));
echo format_cvs_history($group_id);


// CVS Browsing Box
$uri = session_make_url('/cvs/viewvc.php/?root=' . $purifier->purify(urlencode($row_grp['unix_group_name'])) . '&roottype=cvs');
echo '<HR><B>' . _('Browse the CVS Tree') . '</B>
<P>' . _('Browsing the CVS tree gives you a great view into the current status of this project\'s code. You may also view the complete histories of any file in the repository.') . '
<UL>
<LI><A href="' . $uri . '"><B>' . _('Tree') . '</B></A></LI>';

echo $HTML->box1_bottom();

echo '</TD></TR></TABLE>';
echo '</div>';

commits_footer([]);
