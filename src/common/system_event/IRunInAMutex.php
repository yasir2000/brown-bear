<?php
/**
 * Copyright BrownBear (c) 2011, 2012, 2013 - Present. All rights reserved.
 *
 * Tuleap and BrownBear names and logos are registrated trademarks owned by
 * BrownBear SAS. All other trademarks or names are properties of their respective
 * owners.
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

/**
 * Control process to be run by SystemEventProcessorMutex
 */
interface IRunInAMutex
{
    /**
     * The method to be executed by the mutex
     *
     * @return void
     */
    public function execute($queue);

    /**
     * The unix user who should run the code
     *
     * @return String
     */
    public function getProcessOwner();

    /**
     * The process
     *
     * @return SystemEventProcess
     */
    public function getProcess();
}
