<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

class PluginFactory // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    /** @var PluginDao */
    private $plugin_dao;

    /**
     * @var array
     *
     * @psalm-var array{by_name: array, by_id: array, available: array, unavailable: array}
     */
    private $retrieved_plugins;

    /** @var array */
    private $custom_plugins;

    /** @var array */
    private $name_by_id;

    /** @var PluginResourceRestrictor */
    private $plugin_restrictor;

    /** @var array */
    private $plugin_class_path = [];

    private static $instance;

    public function __construct(
        PluginDao $plugin_dao,
        PluginResourceRestrictor $plugin_restrictor,
    ) {
        $this->plugin_dao        = $plugin_dao;
        $this->plugin_restrictor = $plugin_restrictor;

        $this->retrieved_plugins = [
            'by_name'     => [],
            'by_id'       => [],
            'available'   => [],
            'unavailable' => [],
        ];
        $this->name_by_id        = [];
        $this->custom_plugins    = [];
    }

    /**
      * @return PluginFactory
      */
    public static function instance()
    {
        if (! self::$instance) {
            $plugin_dao            = new PluginDao(CodendiDataAccess::instance());
            $restricted_plugin_dao = new RestrictedPluginDao();
            $restrictor            = new PluginResourceRestrictor($restricted_plugin_dao);

            self::$instance = new PluginFactory($plugin_dao, $restrictor);
        }
        return self::$instance;
    }

    public static function clearInstance()
    {
        self::$instance = null;
    }

    public function getPluginById($id)
    {
        if (! isset($this->retrieved_plugins['by_id'][$id])) {
            $dar = $this->plugin_dao->searchById($id);
            if ($row = $dar->getRow()) {
                $p = $this->getInstancePlugin($id, $row);
            } else {
                $this->retrieved_plugins['by_id'][$id] = false;
            }
        }
        return $this->retrieved_plugins['by_id'][$id];
    }

    public function getPluginByName($name)
    {
        if (! isset($this->retrieved_plugins['by_name'][$name])) {
            $dar = $this->plugin_dao->searchByName($name);
            if ($row = $dar->getRow()) {
                $p = $this->getInstancePlugin($row['id'], $row);
            } else {
                $this->retrieved_plugins['by_name'][$name] = false;
            }
        }
        return $this->retrieved_plugins['by_name'][$name];
    }

    /**
     * create a plugin in the db
     * @return Plugin|false the new plugin or false if there is already the plugin (same name)
     */
    public function createPlugin($name)
    {
        $p   = false;
        $dar = $this->plugin_dao->searchByName($name);
        if (! $dar->getRow()) {
            $id = $this->plugin_dao->create($name, 0);
            if (is_int($id)) {
                $p = $this->getInstancePlugin($id, ['name' => $name, 'available' => 0]);
                if ($p && $p->getScope() === Plugin::SCOPE_PROJECT && $p->isRestrictedByDefault) {
                    $this->plugin_dao->restrictProjectPluginUse($id, true);
                }
            }
        }
        return $p;
    }

    /**
     * @return Plugin|false
     */
    private function getInstancePlugin($id, $row)
    {
        if (! isset($this->retrieved_plugins['by_id'][$id])) {
            $this->retrieved_plugins['by_id'][$id] = false;
            $p                                     = $this->instantiatePlugin($id, $row['name']);
            if ($p) {
                $this->retrieved_plugins['by_id'][$id]                                           = $p;
                $this->retrieved_plugins['by_name'][$row['name']]                                = $p;
                $this->retrieved_plugins[($row['available'] ? 'available' : 'unavailable')][$id] = $p;
                $this->name_by_id[$id]                                                           = $row['name'];
                if ($p->isCustom()) {
                    $this->custom_plugins[$id] = $p;
                }
            }
        }
        return $this->retrieved_plugins['by_id'][$id];
    }

    public function instantiatePlugin(?int $id, $name): ?Plugin
    {
        $plugin_class_info = $this->getClassNameForPluginName($name);
        $plugin_class      = $plugin_class_info['class'];
        if (! $plugin_class) {
            return null;
        }

        $plugin = new $plugin_class($id);
        if (! $plugin instanceof Plugin) {
            return null;
        }
        if ($plugin_class_info['custom']) {
            $plugin->setIsCustom(true);
        }
        return $plugin;
    }

    /**
     * @return array{class: class-string|false, custom: bool}
     */
    protected function getClassNameForPluginName($name): array
    {
        $name       = self::verifyPluginName($name);
        $class_name = $name . "Plugin";
        $custom     = false;
        $class_path = '';
        $file_name  = '/' . $name . '/include/' . $class_name . '.php';
        if (! class_exists($class_name)) {
            $this->loadClass($this->getCustomPluginsRoot() . $file_name);
        }
        if (empty($this->plugin_class_path[$name])) {
            $class_path = $this->getPluginClassPath($file_name);
            $custom     = $this->classIsCustom($file_name);
        }
        if (! class_exists($class_name)) {
            $class_name = false;
        } else {
            if ($class_path) {
                $this->plugin_class_path[$name] = [
                    'class' => $class_name,
                    'path'  => $class_path,
                ];
            }
        }
        return ['class' => $class_name, 'custom' => $custom];
    }

    /**
     * Check for directory separator to prevent potential LFI
     *
     * @psalm-taint-escape include
     * @psalm-taint-escape shell
     * @psalm-pure
     */
    private static function verifyPluginName(string $name): string
    {
        if (strpos($name, DIRECTORY_SEPARATOR) !== false) {
            throw new RuntimeException('$name is not expected to contain a directory separator, got ' . $name);
        }
        return $name;
    }

    private function getPluginClassPath(string $file_name): string
    {
        if (file_exists($this->getCustomPluginsRoot() . $file_name)) {
            return $this->getCustomPluginsRoot() . $file_name;
        } else {
            return $this->tryPluginPaths($this->getOfficialPluginPaths(), $file_name);
        }
    }

    private function classIsCustom($file_name): bool
    {
        return file_exists($this->getCustomPluginsRoot() . $file_name);
    }

    /**
     * @return list<string>
     */
    private function getOfficialPluginPaths(): array
    {
        return array_merge(
            array_filter(
                array_map('trim', explode(',', ForgeConfig::get('sys_extra_plugin_path')))
            ),
            [$this->getOfficialPluginsRoot()]
        );
    }

    public function getAllPossiblePluginsDir()
    {
        return array_merge($this->getOfficialPluginPaths(), [ForgeConfig::get('sys_custompluginsroot')]);
    }

    private function tryPluginPaths(array $potential_paths, $file_name)
    {
        foreach ($potential_paths as $path) {
            $full_path = $path . '/' . $file_name;
            if ($this->loadClass($full_path)) {
                return $full_path;
            }
        }
        return false;
    }

    private function loadClass($class_path)
    {
        if ($this->includeIfExists($class_path)) {
            $autoload_path = dirname($class_path) . DIRECTORY_SEPARATOR . 'autoload.php';
            $this->includeIfExists($autoload_path);
            return true;
        }
        return false;
    }

    private function includeIfExists($file_path)
    {
        if (file_exists($file_path)) {
            require_once($file_path);
            return true;
        }
        return false;
    }

    private function getOfficialPluginsRoot(): string
    {
        return ForgeConfig::get('sys_pluginsroot', __DIR__ . '/../../../plugins');
    }

    private function getCustomPluginsRoot(): ?string
    {
        return ForgeConfig::get('sys_custompluginsroot', null);
    }

    /**
     * @return array of enabled or disabled plugins depends on parameters
     */
    private function getAvailableOrUnavailablePlugins($map, $criteria)
    {
        $dar = $this->plugin_dao->searchByAvailable($criteria);
        while ($row = $dar->getRow()) {
            $p = $this->getInstancePlugin($row['id'], $row);
        }
         return $this->retrieved_plugins[$map];
    }
    /**
     * @return array of unavailable plugins
     */
    public function getUnavailablePlugins()
    {
         return $this->getAvailableOrUnavailablePlugins('unavailable', 0);
    }
    /**
     * @return Plugin[]
     */
    public function getAvailablePlugins()
    {
         return $this->getAvailableOrUnavailablePlugins('available', 1);
    }

    public function getAvailablePluginsWithoutOrder()
    {
        return $this->plugin_dao->getAvailablePluginsWithoutOrder();
    }

    /**
     * @return Plugin[]
     */
    public function getAllPlugins(): array
    {
        $all_plugins = [];
        $dar         = $this->plugin_dao->searchAll();
        while ($row = $dar->getRow()) {
            if ($p = $this->getInstancePlugin($row['id'], $row)) {
                $all_plugins[] = $p;
            }
        }
        return $all_plugins;
    }

    public function isPluginAvailable(Plugin $plugin): bool
    {
        return isset($this->retrieved_plugins['available'][$plugin->getId()]);
    }

    /**
     * available plugin
     */
    public function availablePlugin(Plugin $plugin): void
    {
        if (! $this->isPluginAvailable($plugin)) {
            $this->plugin_dao->updateAvailableByPluginId('1', $plugin->getId());
            $this->retrieved_plugins['available'][$plugin->getId()] = $plugin;
            unset($this->retrieved_plugins['unavailable'][$plugin->getId()]);
        }
    }
    /**
     * unavailable plugin
     */
    public function unavailablePlugin(Plugin $plugin): void
    {
        if ($this->isPluginAvailable($plugin)) {
            $this->plugin_dao->updateAvailableByPluginId('0', $plugin->getId());
            $this->retrieved_plugins['unavailable'][$plugin->getId()] = $plugin;
            unset($this->retrieved_plugins['available'][$plugin->getId()]);
        }
    }

    /**
     * @return Plugin[]
     */
    public function getNotYetInstalledPlugins(): array
    {
        $plugins = [];
        $paths   = $this->getOfficialPluginPaths();
        $exclude = ['.', '..', 'CVS', '.svn'];
        foreach ($paths as $path) {
            if (! is_dir($path)) {
                continue;
            }
            $dir = opendir($path);
            while ($file = readdir($dir)) {
                if (! in_array($file, $exclude) && is_dir($path . '/' . $file)) {
                    if (! $this->isPluginInstalled($file) && ! in_array($file, $plugins)) {
                        $plugin = $this->instantiatePlugin(null, $file);
                        if ($plugin) {
                            $plugin->setName($file);
                            $plugins[] = $plugin;
                        }
                    }
                }
            }
            closedir($dir);
        }
        return $plugins;
    }

    public function isPluginInstalled($name)
    {
        $dar = $this->plugin_dao->searchByName($name);
        return ($dar->rowCount() > 0);
    }

    public function removePlugin($plugin)
    {
        $id =  $plugin->getId();
        unset($this->retrieved_plugins['by_id'][$id]);
        unset($this->retrieved_plugins['by_name'][$this->name_by_id[$id]]);
        unset($this->retrieved_plugins['available'][$id]);
        unset($this->retrieved_plugins['unavailable'][$id]);
        unset($this->name_by_id[$id]);
        return $this->plugin_dao->removeById($plugin->getId());
    }

    public function getNameForPlugin(Plugin $plugin): string
    {
        $name = '';
        $id   = $plugin->getId();
        if (isset($this->name_by_id[$id])) {
            $name = $this->name_by_id[$id];
        } else {
            if ($dar = $this->plugin_dao->searchById($id)) {
                if ($row = $dar->getRow()) {
                    $name = $row['name'];
                }
            }
        }
        return self::verifyPluginName($name);
    }

    public function pluginIsCustom($plugin)
    {
        return isset($this->custom_plugins[$plugin->getId()]);
    }

    public function getProjectsByPluginId($plugin)
    {
        $project_ids = [];
        $dar         = $this->plugin_restrictor->searchAllowedProjectsOnPlugin($plugin);

        if ($dar && ! $dar->isError()) {
            while ($row = $dar->getRow()) {
                $project_ids[] = $row['project_id'];
            }
        }

        return $project_ids;
    }

    public function addProjectForPlugin($plugin, $project_id)
    {
        return $this->plugin_restrictor->allowProjectOnPlugin(
            $plugin,
            $this->getProject($project_id)
        );
    }

    public function delProjectForPlugin($plugin, $project_id)
    {
        return $this->plugin_restrictor->revokeProjectsFromPlugin(
            $plugin,
            $this->getProject($project_id)
        );
    }

    public function restrictProjectPluginUse($plugin, $usage)
    {
        return $this->plugin_dao->restrictProjectPluginUse($plugin->getId(), $usage);
    }

    public function truncateProjectPlugin($plugin)
    {
        return $this->plugin_restrictor->revokeAllProjectsFromPlugin($plugin);
    }

    public function isProjectPluginRestricted($plugin)
    {
        $restricted = false;
        $dar        = $this->plugin_dao->searchProjectPluginRestrictionStatus($plugin->getId());
        if ($dar && ! $dar->isError()) {
            $row        = $dar->getRow();
            $restricted = $row['prj_restricted'];
        }
        return $restricted;
    }

    public function isPluginAllowedForProject($plugin, $project_id)
    {
        return $this->plugin_restrictor->isPluginAllowedForProject(
            $plugin,
            $project_id
        );
    }

    /** @return Project */
    private function getProject($project_id)
    {
        return ProjectManager::instance()->getProject($project_id);
    }

    public function getClassPath($name)
    {
        return $this->plugin_class_path[$name]['path'];
    }

    public function getClassName($name)
    {
        return $this->plugin_class_path[$name]['class'];
    }
}
