<?php
/**
 * Copyright (c) BrownBear, 2022-Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class b202202171556_create_oauth2_authorization_code_scope_table extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Add oauth2_authorization_code_scope table';
    }

    public function up(): void
    {
        $sql = 'CREATE TABLE oauth2_authorization_code_scope (
                    auth_code_id INT(11) NOT NULL,
                    scope_key VARCHAR(255) NOT NULL,
                    PRIMARY KEY (auth_code_id, scope_key)
                ) ENGINE=InnoDB;';

        $this->api->createTable('oauth2_authorization_code_scope', $sql);
    }
}
