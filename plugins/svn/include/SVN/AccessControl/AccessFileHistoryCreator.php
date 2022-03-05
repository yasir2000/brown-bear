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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\SVN\AccessControl;

use SVN_AccessFile_Writer;
use SVNAccessFile;
use Tuleap\SVN\Repository\ProjectHistoryFormatter;
use Tuleap\SVN\Repository\Repository;

class AccessFileHistoryCreator
{
    /**
     * @var AccessFileHistoryFactory
     */
    private $access_file_factory;

    /**
     * @var AccessFileHistoryDao
     */
    private $dao;
    /**
     * @var \ProjectHistoryDao
     */
    private $project_history_dao;
    /**
     * @var ProjectHistoryFormatter
     */
    private $project_history_formatter;
    /**
     * @var \BackendSVN
     */
    private $backend_SVN;

    public function __construct(
        AccessFileHistoryDao $dao,
        AccessFileHistoryFactory $access_file_factory,
        \ProjectHistoryDao $project_history_dao,
        ProjectHistoryFormatter $project_history_formatter,
        \BackendSVN $backend_SVN,
    ) {
        $this->dao                       = $dao;
        $this->access_file_factory       = $access_file_factory;
        $this->project_history_dao       = $project_history_dao;
        $this->project_history_formatter = $project_history_formatter;
        $this->backend_SVN               = $backend_SVN;
    }

    /**
     * @return AccessFileHistory
     * @throws CannotCreateAccessFileHistoryException
     */
    public function create(Repository $repository, $content, $timestamp, SVN_AccessFile_Writer $access_file_writer)
    {
        $file_history = $this->storeInDB($repository, $content, $timestamp);
        $this->logHistory($repository, $content);

        $this->saveAccessFile($repository, $file_history, $access_file_writer);

        return $file_history;
    }

    public function useAVersion(Repository $repository, $version_id)
    {
        $this->useVersion($repository, $version_id, false);
    }

    public function useAVersionWithHistory(Repository $repository, $version_id)
    {
        $version = $this->access_file_factory->getById($version_id, $repository);
        $this->useVersion($repository, $version->getId(), true);
    }

    public function useAVersionWithHistoryWithoutUpdateSVNAccessFile(Repository $repository, $version_id)
    {
        try {
            $version = $this->access_file_factory->getByVersionNumber($version_id, $repository);
            $this->saveUsedVersion($repository, $version->getId());
            $this->logUseAVersionHistory($repository, $version);
        } catch (AccessFileHistoryNotFoundException $e) {
        }
    }

    private function useVersion(Repository $repository, $version_id, $log_history)
    {
        $this->saveUsedVersion($repository, $version_id);

        $current_version = $this->access_file_factory->getCurrentVersion($repository);

        $accessfile = new SVN_AccessFile_Writer($repository->getSystemPath());
        $this->saveAccessFile($repository, $current_version, $accessfile);

        if ($log_history) {
            $this->logUseAVersionHistory($repository, $current_version);
        }
    }

    private function cleanContent(Repository $repository, $content)
    {
        $access_file = new SVNAccessFile();
        return trim(
            $access_file->parseGroupLinesByRepositories($repository->getSystemPath(), $content, true)
        );
    }

    /**
     * @throws CannotCreateAccessFileHistoryException
     */
    private function saveAccessFile(Repository $repository, AccessFileHistory $history, SVN_AccessFile_Writer $access_file_writer)
    {
        if (! $access_file_writer->write_with_defaults($history->getContent())) {
            $this->checkAccessFileWriteError($repository, $access_file_writer);
        }
    }

    /**
     * @throws CannotCreateAccessFileHistoryException
     */
    public function saveAccessFileAndForceDefaultGeneration(Repository $repository, AccessFileHistory $history)
    {
        $accessfile          = new SVN_AccessFile_Writer($repository->getSystemPath());
        $access_file_content = $this->backend_SVN->exportSVNAccessFileDefaultBloc($repository->getProject()) .
            $history->getContent();
        if (! $accessfile->write($access_file_content)) {
            $this->checkAccessFileWriteError($repository, $accessfile);
        }
    }

    /**
     * @return AccessFileHistory
     * @throws CannotCreateAccessFileHistoryException
     */
    public function storeInDB(Repository $repository, $content, $timestamp)
    {
        $content = $this->cleanContent($repository, $content);
        return $this->storeInDBWithoutCleaningContent($repository, $content, $timestamp);
    }

    /**
     * @return AccessFileHistory
     * @throws CannotCreateAccessFileHistoryException
     */
    public function storeInDBWithoutCleaningContent(Repository $repository, $content, $timestamp)
    {
        $last_version_number = $this->access_file_factory->getLastVersion($repository)->getVersionNumber();
        $new_version_number  = $last_version_number + 1;

        $file_history_id = $this->dao->create($new_version_number, $repository->getId(), $content, $timestamp);
        if ($file_history_id === false) {
            throw new CannotCreateAccessFileHistoryException(
                dgettext('tuleap-svn', 'Unable to update Access Control File.')
            );
        }

        return new AccessFileHistory(
            $repository,
            $file_history_id,
            $new_version_number,
            $content,
            $timestamp
        );
    }

    private function logHistory(Repository $repository, $content)
    {
        $access_file = $this->project_history_formatter->getAccessFileHistory($content);
        $this->project_history_dao->groupAddHistory(
            'svn_multi_repository_access_file_update',
            "Repository: " . $repository->getName() . PHP_EOL . $access_file,
            $repository->getProject()->getID()
        );
    }

    private function logUseAVersionHistory(Repository $repository, AccessFileHistory $version)
    {
        if ($version->getVersionNumber() === 0) {
            return;
        }

        $old_version =
            "Repository: " . $repository->getName() . PHP_EOL .
            "version #" . $version->getVersionNumber();

        $this->project_history_dao->groupAddHistory(
            'svn_multi_repository_access_file_use_version',
            $old_version,
            $repository->getProject()->getID()
        );
    }

    private function saveUsedVersion(Repository $repository, $version_id)
    {
        if (! $this->dao->useAVersion($repository->getId(), $version_id)) {
            throw new CannotCreateAccessFileHistoryException(
                dgettext('tuleap-svn', 'Unable to update Access Control File.')
            );
        }
    }

    /**
     * @throws CannotCreateAccessFileHistoryException
     */
    private function checkAccessFileWriteError(Repository $repository, SVN_AccessFile_Writer $access_file)
    {
        if ($access_file->isErrorFile()) {
            throw new CannotCreateAccessFileHistoryException(
                sprintf(dgettext('tuleap-svn', 'Unable to read file %1$s'), $repository->getSystemPath())
            );
        }

        throw new CannotCreateAccessFileHistoryException(
            sprintf(dgettext('tuleap-svn', 'Unable to write into file %1$s'), $repository->getSystemPath())
        );
    }
}
