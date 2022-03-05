<?php
/**
  * Copyright (c) Enalean, 2017 - Present. All rights reserved
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
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  * GNU General Public License for more details.
  *
  * You should have received a copy of the GNU General Public License
  * along with Tuleap. If not, see <http://www.gnu.org/licenses/>
  */

namespace Tuleap\SVN\Notifications;

use Exception;
use Throwable;

class CannotAddUsersNotificationException extends Exception
{
    private $users_not_added;

    public function __construct($users_not_added, $message = "", $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->users_not_added = $users_not_added;
    }

    public function getUsersNotAdded()
    {
        return $this->users_not_added;
    }
}
