<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
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

use Tuleap\Project\MappingRegistry;

class ServiceTracker extends Service
{
    public const NAME = 'tracker';

    public function getIconName(): string
    {
        return 'fas fa-list-ol';
    }

    /**
     * Display header for service tracker
     */
    public function displayHeader(string $title, $breadcrumbs, array $toolbar, array $params = []): void
    {
        $GLOBALS['HTML']->includeCalendarScripts();

        $global_admin_permissions_checker = new \Tuleap\Tracker\Admin\GlobalAdmin\GlobalAdminPermissionsChecker(
            new User_ForgeUserGroupPermissionsManager(
                new User_ForgeUserGroupPermissionsDao()
            )
        );
        $user_has_special_access          = $global_admin_permissions_checker
            ->doesUserHaveTrackerGlobalAdminRightsOnProject($this->getProject(), UserManager::instance()->getCurrentUser());

        $params                 = $params + ['user_has_special_access' => $user_has_special_access];
        $params['service_name'] = self::NAME;
        $params['project_id']   = $this->getGroupId();

        parent::displayHeader($title, $breadcrumbs, $toolbar, $params);
    }

    /**
     * Duplicate this service from the current project to another
     *
     * @param int   $to_project_id  The target paroject Id
     * @param array $ugroup_mapping The ugroup mapping
     *
     */
    public function duplicate(int $to_project_id, array $ugroup_mapping): void
    {
        $tracker_manager = $this->getTrackerManager();
        $tracker_manager->duplicate($this->project->getId(), $to_project_id, new MappingRegistry($ugroup_mapping));
    }

    /**
     * @return TrackerManager
     */
    protected function getTrackerManager()
    {
        return new TrackerManager();
    }

    /**
     * Say if the service is allowed for the project
     */
    protected function isAllowed($project): bool
    {
        $plugin_manager = PluginManager::instance();
        $p              = $plugin_manager->getPluginByName('tracker');
        if ($p && $plugin_manager->isPluginAvailable($p) && $p->isAllowed($project->getGroupId())) {
            return true;
        }
        return false;
    }

    /**
     * Say if the service is restricted
     */
    public function isRestricted(): bool
    {
        $plugin_manager = PluginManager::instance();
        $p              = $plugin_manager->getPluginByName('tracker');
        if ($p && $plugin_manager->isProjectPluginRestricted($p)) {
            return true;
        }
        return false;
    }

    /**
     * Trackers are cloned on project creation
     *
     * @see Service::isInheritedOnDuplicate()
     */
    public function isInheritedOnDuplicate(): bool
    {
        return true;
    }

    public static function getDefaultServiceData($project_id)
    {
        return [
            'label'        => 'plugin_tracker:service_lbl_key',
            'description'  => 'plugin_tracker:service_desc_key',
            'link'         => "/plugins/tracker/?group_id=$project_id",
            'short_name'   => trackerPlugin::SERVICE_SHORTNAME,
            'scope'        => 'system',
            'rank'         => 151,
            'location'     => 'master',
            'is_in_iframe' => 0,
            'server_id'    => 0,
        ];
    }
}
