<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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

class TuleapRegisterMail
{
    /** @var MailPresenterFactory */
    private $mail_presenter_factory;

    /** @var TemplateRenderer */
    private $renderer;

    /** @var string */
    private $template;

    public function __construct(MailPresenterFactory $mail_presenter_factory, TemplateRenderer $renderer, $template)
    {
        $this->mail_presenter_factory = $mail_presenter_factory;
        $this->renderer               = $renderer;
        $this->template               = $template;
    }

    /**
     * Returns a new Codendi_Mail.
     *
     * @return Codendi_Mail
     */
    public function getMail($login, $confirm_hash, $base_url, $from, $to, $presenter_role)
    {
        if ($presenter_role === "user") {
            $subject = $GLOBALS['Language']->getText('include_proj_email', 'account_register', ForgeConfig::get('sys_name'));
            include($GLOBALS['Language']->getContent('include/new_user_email'));
        } elseif ($presenter_role === "admin") {
            $subject = sprintf(_('Welcome to %1$s!'), ForgeConfig::get('sys_name'));
            include($GLOBALS['Language']->getContent('account/new_account_email'));
        } elseif ($presenter_role === "admin-notification") {
            $redirect_url = $base_url . "/admin/approve_pending_users.php?page=pending";
            $subject      = sprintf(_('New User Registered: %1$s'), $login);
            $message      = $this->createNotificationMessageText($login, $redirect_url);
        } else {
            $subject = sprintf(_('Your account has been created on %s'), ForgeConfig::get('sys_name'));
            include($GLOBALS['Language']->getContent('admin/new_account_email'));
        }

        $mail     = new Codendi_Mail();
        $cid_logo = $this->addLogoInAttachment($mail);
        $mail->setSubject($subject);
        $mail->setTo($to);
        $mail->setBodyHtml(
            $this->renderer->renderToString(
                $this->template,
                $this->mail_presenter_factory->createMailAccountPresenter(
                    $login,
                    $confirm_hash,
                    $presenter_role,
                    $cid_logo
                )
            ),
            Codendi_Mail::DISCARD_COMMON_LOOK_AND_FEEL
        );
        $mail->setBodyText($message);
        $mail->setFrom($from);

        return $mail;
    }

    /**
     * Returns a new Codendi_Mail.
     *
     * @return Codendi_Mail
     */
    public function getMailProject($subject, $from, $to, $project)
    {
        $mail     = new Codendi_Mail();
        $cid_logo = $this->addLogoInAttachment($mail);
        $mail->setSubject($subject);
        $mail->setTo($to);

        $presenter = $this->mail_presenter_factory->createMailProjectPresenter($project, $cid_logo);

        $mail->setBodyHtml($this->renderer->renderToString($this->template, $presenter));
        $mail->setBodyText($presenter->getMessageText());
        $mail->setFrom($from);

        return $mail;
    }

    /**
     * Returns a new Codendi_Mail.
     *
     * @return Codendi_Mail
     */
    public function getMailNotificationProject($subject, $from, $to, $project)
    {
        $mail     = new Codendi_Mail();
        $cid_logo = $this->addLogoInAttachment($mail);
        $mail->setSubject($subject);
        $mail->setTo($to);

        $presenter = $this->mail_presenter_factory->createMailProjectNotificationPresenter($project, $cid_logo);

        $mail->setBodyHtml($this->renderer->renderToString($this->template, $presenter));
        $mail->setBodyText($presenter->getMessageText());
        $mail->setFrom($from);

        return $mail;
    }

    /**
     * Create a message without html.
     *
     * @return string
     */
    private function createNotificationMessageText($login, $redirect_url)
    {
        $message = _('Account creation!') . "\n\n"
           . sprintf(_('A new user has just registered on %1$s.

User Name:'), ForgeConfig::get('sys_name')) . " "
           . $login . _('.') . "\n\n"
           . _('Please click on the following URL to approve the registration:') . "\n\n"
           . "<" . $redirect_url . ">\n\n"
           . _('Thanks!') . "\n\n"
           . sprintf(_('- The team at %1$s.'), ForgeConfig::get('sys_name')) . "\n\n";

        return $message;
    }

    private function addLogoInAttachment(Codendi_Mail $mail): string
    {
        $logo_retriever = new LogoRetriever();
        $cid_logo       = '';
        $path_logo      = $logo_retriever->getLegacyPath();
        if ($path_logo) {
            $id_attachment = 'logo';
            $mail->addInlineAttachment(file_get_contents($path_logo), $logo_retriever->getMimetype(), $id_attachment);
            $cid_logo = 'cid:' . $id_attachment;
        }

        return $cid_logo;
    }
}
