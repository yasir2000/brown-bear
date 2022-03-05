<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

use Tuleap\User\Account\RegistrationGuardEvent;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class User_LoginPresenterBuilder
{
    /** @return User_LoginPresenter */
    public function build($return_to, $printer_version, $form_loginname, CSRFSynchronizerToken $login_csrf, string $prompt_param)
    {
        $additional_connectors = '';
        EventManager::instance()->processEvent(
            Event::LOGIN_ADDITIONAL_CONNECTOR,
            [
                'return_to'            => $return_to,
                'additional_connector' => &$additional_connectors,
            ]
        );

        $registration_guard = EventManager::instance()->dispatch(new RegistrationGuardEvent());

        $presenter = new User_LoginPresenter(
            $return_to,
            $printer_version,
            $form_loginname,
            $additional_connectors,
            $login_csrf,
            $prompt_param,
            $registration_guard->isRegistrationPossible(),
        );

        $authoritative = false;

        EventManager::instance()->processEvent(
            'login_presenter',
            [
                'presenter'     => &$presenter,
                'authoritative' => &$authoritative,
            ]
        );

        return $presenter;
    }

    /** @return User_LoginPresenter */
    public function buildForHomepage(CSRFSynchronizerToken $login_csrf)
    {
        $return_to       = '';
        $printer_version = 0;
        $form_loginname  = '';

        return $this->build($return_to, $printer_version, $form_loginname, $login_csrf, '');
    }
}
