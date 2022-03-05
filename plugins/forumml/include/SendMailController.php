<?php
/**
 * Copyright (c) Enalean, 2011-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2005. All Rights Reserved.
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

namespace Tuleap\ForumML;

use HTTPRequest;
use Tuleap\ForumML\Threads\ThreadsController;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Valid_String;
use Valid_UInt;

class SendMailController implements DispatchableWithRequest
{
    /**
     * @var \ForumMLPlugin
     */
    private $plugin;

    public function __construct(\ForumMLPlugin $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * Is able to process a request routed by FrontRouter
     *
     * @param array       $variables
     * @return void
     * @throws ForbiddenException
     * @throws NotFoundException
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        include_once __DIR__ . '/forumml_utils.php';
        include_once __DIR__ . '/../../../src/www/include/mail_utils.php';

        if ($request->valid(new Valid_UInt('group_id'))) {
            $group_id = $request->get('group_id');
        } else {
            $group_id = "";
        }

        if (! $this->plugin->isAllowed($group_id)) {
            throw new ForbiddenException();
        }

        // Checks 'list' parameter
        if (! $request->valid(new Valid_UInt('list'))) {
            $layout->addFeedback(\Feedback::ERROR, dgettext('tuleap-forumml', 'You must specify the mailing-list id.'));
            $layout->redirect('/mail/?group_id=' . $group_id);
        } else {
            $list_id = $request->get('list');
            if (! user_isloggedin() || (! mail_is_list_public($list_id) && ! user_ismember($group_id))) {
                $layout->addFeedback(
                    \Feedback::ERROR,
                    $GLOBALS["Language"]->getText('include_exit', 'mail_list_no_perm')
                );
                $layout->redirect('/mail/?group_id=' . $group_id);
            }
            if (! mail_is_list_active($list_id)) {
                $layout->addFeedback(\Feedback::ERROR, dgettext('tuleap-forumml', 'The mailing-list does not exist or is inactive.'));
                $layout->redirect('/mail/?group_id=' . $group_id);
            }
        }

        // If message is posted, send a mail
        if ($request->isPost() && $request->exist('post')) {
            // Checks if mail subject is empty
            $vSub = new Valid_String('subject');
            $vSub->required();
            if (! $request->valid($vSub)) {
                $layout->addFeedback(\Feedback::ERROR, dgettext('tuleap-forumml', 'Submit failed. You must specify the mail subject.'));
            } else {
                // process the mail
                $return = plugin_forumml_process_mail();
                if ($return) {
                    $layout->addFeedback(
                        \Feedback::WARN,
                        dgettext('tuleap-forumml', 'There can be some delay before to see the message in the archives. If you don\'t see your mail, please refresh the page in a few moment.')
                    );
                }
            }
        } elseif ($request->exist('send_reply')) {
            $topic = $request->get('topic');
            $ret   = plugin_forumml_process_mail(true);
            if ($ret) {
                $layout->addFeedback(
                    \Feedback::WARN,
                    dgettext('tuleap-forumml', 'There can be some delay before to see the message in the archives. If you don\'t see your mail, please refresh the page in a few moment.')
                );
            }
            $layout->redirect(
                $this->plugin->getPluginPath(
                ) . '/message.php?group_id=' . $group_id . '&list=' . $list_id . '&topic=' . $topic
            );
        } else {
            $layout->addFeedback(
                \Feedback::WARN,
                dgettext('tuleap-forumml', 'Check carefully your post before submitting. The message is sent without confirmation.')
            );
        }
        $layout->redirect(ThreadsController::getUrl((int) $list_id));
    }
}
