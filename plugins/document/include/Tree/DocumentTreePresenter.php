<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Document\Tree;

use CSRFSynchronizerToken;
use DocmanPlugin;
use Tuleap\date\DefaultRelativeDatesDisplayPreferenceRetriever;
use Tuleap\Document\Config\FileDownloadLimits;
use Tuleap\Project\Icons\EmojiCodepointConverter;
use Tuleap\Project\ProjectPrivacyPresenter;

class DocumentTreePresenter
{
    /**
     * @var int
     */
    public $project_id;
    /**
     * @var int
     */
    public $root_id;
    /**
     * @var string
     */
    public $project_name;
    /**
     * @var bool
     */
    public $user_is_admin;
    /**
     * @var bool
     */
    public $user_can_create_wiki;
    /**
     * @var bool
     */
    public $user_can_delete_item;
    /**
     * @var int
     */
    public $max_size_upload;
    /**
     * @var int
     */
    public $max_files_dragndrop;
    /**
     * @var bool
     */
    public $embedded_are_allowed;
    /**
     * @var bool
     */
    public $is_item_status_metadata_used;
    /**
     * @var bool
     */
    public $is_obsolescence_date_metadata_used;
    /**
     * @var string
     */
    public $csrf_token_name;
    /**
     * @var string
     */
    public $csrf_token;
    /**
     * @var int
     */
    public $max_archive_size;
    /**
     * @var int
     */
    public $warning_threshold;

    /**
     * @var string
     */
    public $relative_dates_display;
    /**
     * @var string
     * @psalm-readonly
     */
    public $project_url;
    /**
     * @var mixed
     * @psalm-readonly
     */
    public $project_public_name;
    /**
     * @var false|string
     * @psalm-readonly
     */
    public $privacy;
    /**
     * @var false|string
     * @psalm-readonly
     */
    public $project_flags;
    /**
     * @var false|string
     * @psalm-readonly
     */
    public $criteria;
    /**
     * @var false|string
     * @psalm-readonly
     */
    public $columns;

    public string $project_icon;

    public function __construct(
        \Project $project,
        int $root_id,
        \PFUser $user,
        bool $embedded_are_allowed,
        bool $is_item_status_metadata_used,
        bool $is_obsolescence_date_metadata_used,
        bool $only_siteadmin_can_delete_option,
        CSRFSynchronizerToken $csrf,
        FileDownloadLimits $file_download_limits,
        public bool $is_changelog_displayed_after_dnd,
        array $project_flags,
        array $criteria,
        array $columns,
    ) {
        $this->project_id                         = $project->getID();
        $this->root_id                            = $root_id;
        $this->project_name                       = $project->getUnixNameLowerCase();
        $this->project_public_name                = $project->getPublicName();
        $this->project_url                        = $project->getUrl();
        $this->user_is_admin                      = $user->isAdmin($project->getID());
        $this->user_can_create_wiki               = $project->usesWiki();
        $this->user_can_delete_item               = ! $only_siteadmin_can_delete_option || $user->isSuperUser();
        $this->max_size_upload                    = \ForgeConfig::get(DocmanPlugin::PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING);
        $this->max_files_dragndrop                = \ForgeConfig::get(
            DocmanPlugin::PLUGIN_DOCMAN_MAX_NB_FILE_UPLOADS_SETTING
        );
        $this->embedded_are_allowed               = $embedded_are_allowed;
        $this->is_item_status_metadata_used       = $is_item_status_metadata_used;
        $this->is_obsolescence_date_metadata_used = $is_obsolescence_date_metadata_used;
        $this->csrf_token_name                    = $csrf->getTokenName();
        $this->csrf_token                         = $csrf->getToken();
        $this->max_archive_size                   = $file_download_limits->getMaxArchiveSize();
        $this->warning_threshold                  = $file_download_limits->getWarningThreshold();
        $this->relative_dates_display             = $user->getPreference(\DateHelper::PREFERENCE_NAME) ?: DefaultRelativeDatesDisplayPreferenceRetriever::retrieveDefaultValue();

        $this->privacy       = json_encode(ProjectPrivacyPresenter::fromProject($project), JSON_THROW_ON_ERROR);
        $this->project_flags = json_encode($project_flags, JSON_THROW_ON_ERROR);
        $this->project_icon  = EmojiCodepointConverter::convertStoredEmojiFormatToEmojiFormat($project->getIconUnicodeCodepoint());
        $this->criteria      = json_encode($criteria, JSON_THROW_ON_ERROR);
        $this->columns       = json_encode($columns, JSON_THROW_ON_ERROR);
    }
}
