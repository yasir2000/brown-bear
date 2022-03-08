<?php
/**
 * Copyright (c) BrownBear, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\Query;

class ParametrizedAndFromWhere implements IProvideParametrizedFromAndWhereSQLFragments
{
    /**
     * @var IProvideParametrizedFromAndWhereSQLFragments
     */
    private $left;
    /**
     * @var IProvideParametrizedFromAndWhereSQLFragments
     */
    private $right;

    public function __construct(
        IProvideParametrizedFromAndWhereSQLFragments $left,
        IProvideParametrizedFromAndWhereSQLFragments $right,
    ) {
        $this->left  = $left;
        $this->right = $right;
    }

    /**
     * @return string
     */
    public function getWhere()
    {
        return $this->left->getWhere() . ' AND ' . $this->right->getWhere();
    }

    /**
     * @return ParametrizedFrom[]
     */
    public function getAllParametrizedFrom()
    {
        return array_merge($this->left->getAllParametrizedFrom(), $this->right->getAllParametrizedFrom());
    }

    /**
     * @return array
     */
    public function getWhereParameters()
    {
        return array_merge($this->left->getWhereParameters(), $this->right->getWhereParameters());
    }
}
