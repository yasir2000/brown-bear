<?php
/**
 * Copyright (c) BrownBear, 2018 - Present. All Rights Reserved.
 * Copyright (c) 2010 Christopher Han <xiphux@gmail.com>
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

namespace Tuleap\Git\GitPHP;

/**
 * Git Executable class
 *
 */
class GitExe
{
    public const DIFF_TREE = 'diff-tree';
    public const REV_LIST  = 'rev-list';
    public const SHOW_REF  = 'show-ref';
    public const ARCHIVE   = 'archive';
    public const GREP      = 'grep';
    public const BLAME     = 'blame';
    public const NAME_REV  = 'name-rev';
    public const DIFF      = 'diff';

    /**
     * project
     *
     * Stores the project internally
     *
     * @access protected
     */
    protected $project;

    /**
     * bin
     *
     * Stores the binary path internally
     *
     * @access protected
     */
    protected $binary;

    public function __construct($project = null)
    {
        $this->binary = \Git_Exec::getGitCommand();

        $this->SetProject($project);
    }

    /**
     * SetProject
     *
     * Sets the project for this executable
     *
     * @param mixed $project project to set
     */
    public function SetProject($project = null) // @codingStandardsIgnoreLine
    {
        $this->project = $project;
    }

    /**
     * Execute
     *
     * Executes a command
     *
     * @param string $command the command to execute
     * @param array $args arguments
     * @return string result of command
     */
    public function Execute($command, $args) // @codingStandardsIgnoreLine
    {
        $fullCommand = $this->CreateCommand($command, $args);

        return shell_exec($fullCommand);
    }

    /**
     * Open
     *
     * Opens a resource to a command
     *
     * @param string $command the command to execute
     * @param array $args arguments
     * @return resource process handle
     */
    public function Open($command, $args, $mode = 'r') // @codingStandardsIgnoreLine
    {
        $fullCommand = $this->CreateCommand($command, $args);

        return popen($fullCommand, $mode);
    }

    /**
     * BuildCommand
     *
     * Creates a command
     *
     * @access protected
     *
     * @param string $command the command to execute
     * @param array $args arguments
     * @return string result of command
     */
    protected function CreateCommand($command, $args) // @codingStandardsIgnoreLine
    {
        $gitDir = '';
        if ($this->project) {
            $gitDir = '--git-dir=' . $this->project->GetPath();
        }

        return $this->binary . ' ' . $gitDir . ' ' . $command . ' ' . implode(' ', $args);
    }

    /**
     * GetBinary
     *
     * Gets the binary for this executable
     *
     * @return string binary
     * @access public
     */
    public function GetBinary() // @codingStandardsIgnoreLine
    {
        return $this->binary;
    }
}
