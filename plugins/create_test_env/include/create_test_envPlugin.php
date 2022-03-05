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

require_once __DIR__ . '/../../tracker/include/trackerPlugin.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Tuleap\CreateTestEnv\ActivitiesAnalytics\DisplayUserActivities;
use Tuleap\CreateTestEnv\ActivitiesAnalytics\WeeklySummaryController;
use Tuleap\CreateTestEnv\ActivityLogger\ActivityLoggerDao;
use Tuleap\CreateTestEnv\ActivitiesAnalytics\ListActivitiesController;
use Tuleap\CreateTestEnv\REST\ResourcesInjector as CreateTestEnvResourcesInjector;
use Tuleap\CreateTestEnv\Plugin\PluginInfo;
use Tuleap\Project\ServiceAccessEvent;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Tracker\Artifact\Event\ArtifactCreated;
use Tuleap\Tracker\Artifact\Event\ArtifactUpdated;
use Tuleap\User\User_ForgeUserGroupPermissionsFactory;
use Tuleap\User\UserAuthenticationSucceeded;
use Tuleap\User\UserConnectionUpdateEvent;

// @codingStandardsIgnoreLine
class create_test_envPlugin extends Plugin
{
    public const NAME = 'create_test_env';

    public function __construct($id)
    {
        parent::__construct($id);
        bindtextdomain('tuleap-create_test_env', __DIR__ . '/../site-content');
    }

    /**
     * @return Tuleap\CreateTestEnv\Plugin\PluginInfo
     */
    public function getPluginInfo()
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new PluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function getDependencies()
    {
        return ['tracker'];
    }

    public function getHooksAndCallbacks()
    {
        $this->addHook(Event::REST_RESOURCES);
        $this->addHook(CollectRoutesEvent::NAME);

        $this->addHook(UserAuthenticationSucceeded::NAME);
        $this->addHook(UserConnectionUpdateEvent::NAME);
        $this->addHook(Event::SERVICE_IS_USED);
        $this->addHook(ArtifactCreated::NAME);
        $this->addHook(ArtifactUpdated::NAME);
        $this->addHook(ServiceAccessEvent::NAME);

        $this->addHook(User_ForgeUserGroupPermissionsFactory::GET_PERMISSION_DELEGATION);

        $this->addHook('codendi_daily_start');

        return parent::getHooksAndCallbacks();
    }

    public function restResources(array $params): void
    {
        $create_test_env_injector = new CreateTestEnvResourcesInjector();
        $create_test_env_injector->populate($params['restler']);
    }

    public function routeGetActivities(): DispatchableWithRequest
    {
        return new ListActivitiesController(
            TemplateRendererFactory::build(),
            new ActivityLoggerDao(),
            new User_ForgeUserGroupPermissionsManager(
                new User_ForgeUserGroupPermissionsDao()
            ),
        );
    }

    public function routeGetWeeklySummary(): DispatchableWithRequest
    {
        return new WeeklySummaryController(
            TemplateRendererFactory::build(),
            new ActivityLoggerDao(),
            new User_ForgeUserGroupPermissionsManager(
                new User_ForgeUserGroupPermissionsDao()
            ),
        );
    }

    public function collectRoutesEvent(CollectRoutesEvent $event): void
    {
        $event->getRouteCollector()->addGroup($this->getPluginPath(), function (FastRoute\RouteCollector $r) {
            $r->get('/daily-activities', $this->getRouteHandler('routeGetActivities'));
            $r->get('/weekly-summary', $this->getRouteHandler('routeGetWeeklySummary'));
        });
    }

    public function trackerArtifactCreated(ArtifactCreated $event): void
    {
        $request      = HTTPRequest::instance();
        $current_user = $request->getCurrentUser();
        if ($current_user->isSuperUser()) {
            return;
        }
        $artifact = $event->getArtifact();
        $project  = $artifact->getTracker()->getProject();
        (new ActivityLoggerDao())->insert($current_user->getId(), $project->getID(), 'tracker', "Created artifact #" . $artifact->getId());
    }

    public function trackerArtifactUpdated(ArtifactUpdated $event): void
    {
        $request      = HTTPRequest::instance();
        $current_user = $request->getCurrentUser();
        if ($current_user->isSuperUser()) {
            return;
        }
        $artifact = $event->getArtifact();
        $project  = $artifact->getTracker()->getProject();
        (new ActivityLoggerDao())->insert($current_user->getId(), $project->getID(), 'tracker', "Updated artifact #" . $artifact->getId());
    }

    public function userAuthenticationSucceeded(UserAuthenticationSucceeded $event): void
    {
        $current_user = $event->user;
        if ($current_user->isSuperUser()) {
            return;
        }
        (new ActivityLoggerDao())->insert($current_user->getId(), 0, 'platform', 'Login');
    }

    public function userConnectionUpdateEvent(UserConnectionUpdateEvent $event): void
    {
        $current_user = $event->getUser();
        if ($current_user->isSuperUser()) {
            return;
        }
        (new ActivityLoggerDao())->insert($current_user->getId(), 0, 'platform', 'Connexion');
    }

    public function service_is_used(array $params): void //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $request      = HTTPRequest::instance();
        $current_user = $request->getCurrentUser();
        if ($current_user->isSuperUser()) {
            return;
        }
        $project = ProjectManager::instance()->getProject($params['group_id']);
        $verb    = $params['is_used'] ? 'activated' : 'desactivated';
        (new ActivityLoggerDao())->insert($current_user->getId(), $project->getID(), 'project_admin', "$verb service {$params['shortname']}");
    }

    public function serviceAccessEvent(ServiceAccessEvent $event)
    {
        $request      = HTTPRequest::instance();
        $current_user = $request->getCurrentUser();
        if ($current_user->isSuperUser()) {
            return;
        }
        $project    = $request->getProject();
        $project_id = 0;
        if ($project && ! $project->isError()) {
            $project_id = $project->getID();
        }
        (new ActivityLoggerDao())->insert($current_user->getId(), $project_id, $event->getServiceName(), "Access");
    }

    public function codendi_daily_start(): void //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $one_year_ago = (new DateTimeImmutable())->sub(new DateInterval('P1Y'));
        $dao          = new ActivityLoggerDao();
        $dao->purgeOldData($one_year_ago->getTimestamp());
    }

    public function get_permission_delegation(array &$params): void //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $params['plugins_permission'][DisplayUserActivities::ID] = new DisplayUserActivities();
    }
}
