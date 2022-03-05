<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\XML;

class MappingsRegistry
{
    /**
     * @var array
     */
    private $mappings = [];

    /**
     * @var array
     */
    private $widget_reference = [];

    public function add(array $mapping, $mapping_name)
    {
        $this->mappings[$mapping_name] = $mapping;
    }

    public function get($mapping_name)
    {
        if (isset($this->mappings[$mapping_name])) {
            return $this->mappings[$mapping_name];
        }

        return false;
    }

    public function addReference($reference, $id)
    {
        $this->widget_reference[$reference] = $id;
    }

    public function getReference($reference)
    {
        if (! isset($this->widget_reference[$reference])) {
            return null;
        }
        return $this->widget_reference[$reference];
    }
}
