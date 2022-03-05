<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

/*
 * PluginsAdministrationViews
 */

use Tuleap\Admin\AdminPageRenderer;
use Tuleap\PluginsAdministration\AvailablePluginsPresenter;
use Tuleap\PluginsAdministration\PluginDisablerVerifier;
use Tuleap\PluginsAdministration\PluginPropertiesPresenter;

class PluginsAdministrationViews extends Views
{
    /** @var PluginManager */
    private $plugin_manager;

    /** @var PluginDependencySolver */
    private $dependency_solver;

    /**
     * @var PluginDisablerVerifier
     */
    private $plugin_disabler_verifier;

    public function __construct(&$controler, $view = null)
    {
        $this->View($controler, $view);
        $this->plugin_manager           = PluginManager::instance();
        $this->dependency_solver        = new PluginDependencySolver($this->plugin_manager);
        $plugin_administration          = $this->plugin_manager->getPluginByName('pluginsadministration');
        $this->plugin_disabler_verifier = new PluginDisablerVerifier(
            $plugin_administration,
            ForgeConfig::get('sys_plugins_that_can_not_be_disabled_from_the_web_ui')
        );
    }

    public function header()
    {
        $title = dgettext('tuleap-pluginsadministration', 'Plugins');
        $GLOBALS['HTML']->header(['title' => $title, 'main_classes' => ['tlp-framed']]);
    }

    public function footer()
    {
        $GLOBALS['HTML']->footer([]);
    }

    public function display($view = '')
    {
        $renderer       = new AdminPageRenderer();
        $request        = HTTPRequest::instance();
        $plugin_factory = PluginFactory::instance();

        switch ($view) {
            case 'restrict':
                $plugin_resource_restrictor = $this->getPluginResourceRestrictor();
                $plugin                     = $plugin_factory->getPluginById($request->get('plugin_id'));

                if ($plugin->getScope() !== Plugin::SCOPE_PROJECT) {
                    $GLOBALS['Response']->addFeedback(Feedback::ERROR, 'This project cannot be restricted');
                    $GLOBALS['Response']->redirect('/plugins/pluginsadministration/');
                }

                $presenter = $this->getPluginResourceRestrictorPresenter($plugin, $plugin_resource_restrictor);

                $renderer->renderAPresenter(
                    dgettext('tuleap-pluginsadministration', 'Plugins'),
                    ForgeConfig::get('codendi_dir') . '/src/templates/resource_restrictor',
                    PluginsAdministration_ManageAllowedProjectsPresenter::TEMPLATE,
                    $presenter
                );

                break;

            case 'properties':
                if ($request->exist('plugin_id')) {
                    $plugin = $plugin_factory->getPluginById($request->get('plugin_id'));

                    if (! $plugin) {
                        $GLOBALS['HTML']->redirect('/plugins/pluginsadministration/');
                        return;
                    } else {
                        $presenter = $this->getPluginPropertiesPresenter(
                            $plugin
                        );

                        $renderer->renderAPresenter(
                            dgettext('tuleap-pluginsadministration', 'Plugins'),
                            PLUGINSADMINISTRATION_TEMPLATE_DIR,
                            'plugin-properties',
                            $presenter
                        );
                    }
                }

                break;

            case 'available':
                $this->_searchPlugins();
                $renderer->renderANoFramedPresenter(
                    dgettext('tuleap-pluginsadministration', 'Plugins'),
                    PLUGINSADMINISTRATION_TEMPLATE_DIR,
                    'available-plugins',
                    $this->getAvailablePluginsPresenter()
                );
                break;

            case 'installed':
                $this->_searchPlugins();
                $renderer = new AdminPageRenderer();
                $renderer->renderANoFramedPresenter(
                    dgettext('tuleap-pluginsadministration', 'Plugins'),
                    PLUGINSADMINISTRATION_TEMPLATE_DIR,
                    'installed-plugins',
                    $this->getInstalledPluginsPresenter()
                );
        }
    }

    // {{{ Views
    public function browse()
    {
        $output  = '';
        $output .= $this->getInstalledPluginsPresenter();
        $output .= $this->getAvailablePluginsPresenter();
        echo $output;
    }

    private function getFormattedReadme($name)
    {
        $readme_file    = $this->plugin_manager->getInstallReadme($name);
        $readme_content = $this->plugin_manager->fetchFormattedReadme($readme_file);
        return $readme_content;
    }

    private function getPluginPropertiesPresenter(Plugin $plugin)
    {
        $plugin_info = $plugin->getPluginInfo();
        $descriptor  = $plugin_info->getPluginDescriptor();

        $name = $descriptor->getFullName();
        if (strlen(trim($name)) === 0) {
            $name = $plugin::class;
        }

        $readme          = $this->getFormattedReadme($plugin->getName());
        $is_there_readme = ! empty($readme);

        $dependencies           = implode(', ', $plugin->getDependencies());
        $are_there_dependencies = ! empty($dependencies);

        $additional_options           = $plugin->getAdministrationOptions();
        $are_there_additional_options = ! empty($additional_options);

        $is_enabled             = (bool) $this->plugin_manager->isPluginAvailable($plugin);
        $is_there_enable_switch = (strcasecmp($plugin::class, 'PluginsAdministrationPlugin') !== 0);

        $csrf_token = new CSRFSynchronizerToken('/plugins/pluginsadministration/');

        return new PluginPropertiesPresenter(
            $plugin->getId(),
            $name,
            $descriptor->getVersion(),
            $descriptor->getDescription(),
            $plugin->getScope(),
            $is_there_enable_switch,
            $this->getEnableUrl((int) $plugin->getId(), $is_enabled, 'properties'),
            $are_there_dependencies,
            $dependencies,
            $is_there_readme,
            $readme,
            $are_there_additional_options,
            $additional_options,
            $csrf_token,
            $is_enabled
        );
    }

    private function getEnableUrl(int $id, bool $is_enabled, string $view): string
    {
        $action = $is_enabled ? 'unavailable' : 'available';

        return '?' .
            http_build_query(
                [
                    'action'    => $action,
                    'plugin_id' => $id,
                    'view'      => $view,
                ]
            );
    }

    private function getPluginResourceRestrictorPresenter(
        Plugin $plugin,
        PluginResourceRestrictor $plugin_resource_restrictor,
    ) {
        return new PluginsAdministration_ManageAllowedProjectsPresenter(
            $plugin,
            $plugin_resource_restrictor->searchAllowedProjectsOnPlugin($plugin),
            $plugin_resource_restrictor->isPluginRestricted($plugin)
        );
    }

    private function getPluginResourceRestrictor()
    {
        return new PluginResourceRestrictor(
            new RestrictedPluginDao()
        );
    }

    public $_plugins;

    public function _searchPlugins()
    {
        if (! $this->_plugins) {
            $this->_plugins = [];

            $plugins = $this->plugin_manager->getAllPlugins();
            foreach ($plugins as $plugin) {
                $plug_info  = $plugin->getPluginInfo();
                $descriptor = $plug_info->getPluginDescriptor();
                $available  = $this->plugin_manager->isPluginAvailable($plugin);
                $name       = $descriptor->getFullName();
                if (strlen(trim($name)) === 0) {
                    $name = $plugin::class;
                }
                $dont_touch    = ! $this->plugin_disabler_verifier->canPluginBeDisabled($plugin);
                $dont_restrict = $plugin->getScope() !== Plugin::SCOPE_PROJECT;

                $this->_plugins[] = [
                    'id'            => $plugin->getId(),
                    'name'          => $name,
                    'description'   => $descriptor->getDescription(),
                    'version'       => $descriptor->getVersion(),
                    'available'     => $available,
                    'scope'         => $plugin->getScope(),
                    'dont_touch'    => $dont_touch,
                    'dont_restrict' => $dont_restrict];
            }
        }
    }

    private function getInstalledPluginsPresenter()
    {
        usort(
            $this->_plugins,
            function ($a, $b) {
                return strcasecmp($a['name'], $b['name']);
            }
        );

        $plugins = [];
        foreach ($this->_plugins as $plugin) {
            $is_there_unmet_dependencies = false;
            $unmet_dependencies          = $this->dependency_solver->getInstalledDependencies(
                $this->plugin_manager->getPluginById($plugin['id'])
            );

            if ($unmet_dependencies) {
                $is_there_unmet_dependencies = true;
            }
            $plugins[] = [
                'id'                          => $plugin['id'],
                'name'                        => $plugin['name'],
                'version'                     => $plugin['version'],
                'description'                 => $plugin['description'],
                'enable_url'                  => $this->getEnableUrl($plugin['id'], $plugin['available'], 'installed'),
                'scope'                       => $this->getScopeLabel((int) $plugin['scope']),
                'dont_touch'                  => $plugin['dont_touch'],
                'dont_restrict'               => $plugin['dont_restrict'],
                'is_there_unmet_dependencies' => $is_there_unmet_dependencies,
                'unmet_dependencies'          => $unmet_dependencies,
                'csrf_token'                  => new CSRFSynchronizerToken('/plugins/pluginsadministration/'),
                'is_enabled'                  => $plugin['available'],
            ];
        }

        return new PluginsAdministration_Presenter_InstalledPluginsPresenter($plugins);
    }

    private function getScopeLabel(int $scope): string
    {
        if ($scope === Plugin::SCOPE_PROJECT) {
            return dgettext('tuleap-pluginsadministration', 'Projects');
        }

        return dgettext('tuleap-pluginsadministration', 'System');
    }

    private function getAvailablePluginsPresenter()
    {
        $plugins = [];

        foreach ($this->plugin_manager->getNotYetInstalledPlugins() as $key => $plugin) {
            $descriptor       = $plugin->getPluginInfo()->getPluginDescriptor();
            $plugin_presenter = [
                'name'                        => $plugin->getName(),
                'full_name'                   => $descriptor->getFullName(),
                'description'                 => $descriptor->getDescription(),
                'version'                     => $descriptor->getVersion(),
                'is_there_readme'             => false,
                'is_there_unmet_dependencies' => false,
            ];

            $readme = $this->getFormattedReadme($plugin->getName());
            if (! empty($readme)) {
                $plugin_presenter['is_there_readme'] = true;
                $plugin_presenter['readme']          = $readme;
            }

            $dependencies = $this->dependency_solver->getUnmetInstalledDependencies($plugin->getName());
            if (! empty($dependencies)) {
                $plugin_presenter['is_there_unmet_dependencies'] = true;
                $plugin_presenter['unmet_dependencies']          = $dependencies;
            }

            $plugins[] = $plugin_presenter;
        }

        return new AvailablePluginsPresenter($plugins, new CSRFSynchronizerToken('/plugins/pluginsadministration/'));
    }
}
