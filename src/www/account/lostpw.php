<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
 * Copyright 1999-2000 (c) The SourceForge Crew
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

header("Cache-Control: no-cache, no-store, must-revalidate");

require_once __DIR__ . '/../include/pre.php';


$em = EventManager::instance();
$em->processEvent('before_lostpw', []);

$HTML->header(['title' => _('Lost Account Password')]);

?>

<h2><?php echo _('Lost Account Password'); ?></h2>
<P><?php echo _('<B>Lost your password?</B><P>Hey... losing your password is serious business. It compromises the security of your account, your projects, and this site.<P>Clicking on the button below will email a URL to the email address we have on file for you. In this URL is a 128-bit confirmation hash key for your account. Visiting the URL will allow you to change your password online and login.'); ?></P>

<FORM action="lostpw-confirm.php" method="post" class="form-inline">
<P>
Login Name:
<INPUT type="text" name="form_loginname" autocomplete="username">
<INPUT class="btn btn-primary" type="submit" name="Send Lost Password Hash" value="<?php echo _('Send Lost Password Hash'); ?>">
</FORM>

<P><A href="/">[<?php echo $Language->getText('global', 'back_home'); ?>]</A>

<?php
$HTML->footer([]);
