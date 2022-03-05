<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Password\Configuration;

class PasswordConfigurationRetriever
{
    /**
     * @var PasswordConfigurationDAO
     */
    private $password_configuration_dao;

    public function __construct(PasswordConfigurationDAO $password_configuration_dao)
    {
        $this->password_configuration_dao = $password_configuration_dao;
    }

    /**
     * @return PasswordConfiguration
     */
    public function getPasswordConfiguration()
    {
        $row = $this->password_configuration_dao->getPasswordConfiguration();

        $is_breached_password_configuration_enabled = isset($row['breached_password_enabled']) && $row['breached_password_enabled'];

        return new PasswordConfiguration($is_breached_password_configuration_enabled);
    }
}
