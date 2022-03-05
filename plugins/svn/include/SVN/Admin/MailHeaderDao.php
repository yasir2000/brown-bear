<?php
/**
  * Copyright (c) Enalean, 2016 - Present. All rights reserved
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
  * along with Tuleap. If not, see <http://www.gnu.org/licenses/
  */

namespace Tuleap\SVN\Admin;

use DataAccessObject;

class MailHeaderDao extends DataAccessObject
{
    public function searchByRepositoryId($repository_id)
    {
        $repository_id = $this->da->escapeInt($repository_id);
        $sql           = "SELECT *
                FROM plugin_svn_mailing_header
                WHERE repository_id=$repository_id";

        return $this->retrieveFirstRow($sql);
    }

    public function create(MailHeader $mail_header)
    {
        $header        = $this->da->quoteSmart($mail_header->getHeader());
        $repository_id = $this->da->escapeInt($mail_header->getRepository()->getId());

        $query = "REPLACE INTO plugin_svn_mailing_header
                    (repository_id, header)
                  VALUES
                    ($repository_id, $header)";

        return $this->update($query);
    }
}
