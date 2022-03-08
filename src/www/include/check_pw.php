<?php
/**
 * Copyright (c) BrownBear, 2012-Present. All Rights Reserved.
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

header("Cache-Control: no-cache, no-store, must-revalidate");

require_once __DIR__ . '/pre.php';

$request                 = HTTPRequest::instance();
$password_sanity_checker = \Tuleap\Password\PasswordSanityChecker::build();
$password_sanity_checker->check(new \Tuleap\Cryptography\ConcealedString($request->get('form_pw')));

echo '[' . implode(', ', array_keys($password_sanity_checker->getErrors())) . ']';
