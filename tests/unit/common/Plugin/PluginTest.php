<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

final class PluginTest extends \Tuleap\Test\PHPUnit\TestCase // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    use MockeryPHPUnitIntegration;
    use \Tuleap\ForgeConfigSandbox;

    public function testId(): void
    {
        $p = new Plugin();
        $this->assertEquals(-1, $p->getId());
        $p = new Plugin(123);
        $this->assertEquals(123, $p->getId());
    }

    public function testPluginInfo(): void
    {
        $p = new Plugin();
        $this->assertInstanceOf(PluginInfo::class, $p->getPluginInfo());
    }

    public function testDefaultCallbackIsHookNameInCamelCase(): void
    {
        $p   = $this->getFakePluginToTestHooks();
        $col = $p->getHooksAndCallbacks();
        $this->assertTrue($col->isEmpty());

        $hook = 'an_event';
        $p->addHook($hook);
        $col          = $p->getHooksAndCallbacks();
        $it           = $col->iterator();
        $current_hook = $it->current();
        $this->assertEquals($hook, $current_hook['hook']);
        $this->assertEquals('anEvent', $current_hook['callback']);
        $this->assertFalse($current_hook['recallHook']);
    }

    public function testSpecialCallback(): void
    {
        $p = $this->getFakePluginToTestHooks();

        $hook     = 'name_of_hook';
        $callback = 'doSomething';
        $p->addHook($hook, $callback);
        $col          = $p->getHooksAndCallbacks();
        $it           = $col->iterator();
        $current_hook = $it->current();
        $this->assertEquals($hook, $current_hook['hook']);
        $this->assertEquals($callback, $current_hook['callback']);
        $this->assertFalse($current_hook['recallHook']);
    }

    public function testAnotherSpecialCallback(): void
    {
        $p = $this->getFakePluginToTestHooks();

        $hook     = 'name_of_hook';
        $callback = 'doSomething';
        $recall   = true;
        $p->addHook($hook, $callback, $recall);
        $col          = $p->getHooksAndCallbacks();
        $it           = $col->iterator();
        $current_hook = $it->current();
        $this->assertEquals($hook, $current_hook['hook']);
        $this->assertEquals($callback, $current_hook['callback']);
        $this->assertEquals($recall, $current_hook['recallHook']);
    }

    public function testRaisesAnExceptionWhenThereIsAConflictInAvailableCallbacks(): void
    {
        $plugin = $this->getFakePluginToTestHooks();

        $this->expectException(Exception::class);
        $plugin->addHook('conflict_in_callbacks');
    }

    public function testRaisesAnExceptionWhenThereIsNoCallbackForTheHook(): void
    {
        $plugin = $this->getFakePluginToTestHooks();

        $this->expectException(Exception::class);
        $plugin->addHook('no_callback_defined');
    }

    public function testScope(): void
    {
        $p = new Plugin();
        $this->assertEquals(Plugin::SCOPE_SYSTEM, $p->getScope());
        $this->assertNotEquals(Plugin::SCOPE_PROJECT, $p->getScope());
    }

    public function testGetPluginEtcRoot(): void
    {
        $root = \org\bovigo\vfs\vfsStream::setup()->url();

        ForgeConfig::set('sys_custompluginsroot', $root . '/test/custom/');
        $shortname = 'shortname';
        $pm        = \Mockery::spy(\PluginManager::class);
        $pm->shouldReceive('getNameForPlugin')->andReturns($shortname);
        $p = \Mockery::mock(\Plugin::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $p->shouldReceive('_getPluginManager')->andReturns($pm);

        $this->assertEquals(ForgeConfig::get('sys_custompluginsroot') . '/' . $shortname . '/etc', $p->getPluginEtcRoot());
    }

    public function testGetPluginPath(): void
    {
        ForgeConfig::set('sys_pluginspath', '/plugins');
        $shortname = 'shortname';
        $pm        = \Mockery::spy(\PluginManager::class);

        $pm->shouldReceive('pluginIsCustom')->once()->andReturns(false);
        $pm->shouldReceive('getNameForPlugin')->andReturns($shortname);
        $p = \Mockery::mock(\Plugin::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $p->shouldReceive('_getPluginManager')->andReturns($pm);

        $this->assertEquals(ForgeConfig::get('sys_pluginspath') . '/' . $shortname, $p->getPluginPath());
    }

    public function testGetThemePath(): void
    {
        $tmp_dir = \org\bovigo\vfs\vfsStream::setup()->url();
        ForgeConfig::set('sys_user_theme', 'current_theme');
        ForgeConfig::set('sys_pluginspath', '/plugins');
        ForgeConfig::set('sys_custompluginspath', '/customplugins');
        ForgeConfig::set('sys_pluginsroot', $tmp_dir . '/test/plugins/');
        ForgeConfig::set('sys_custompluginsroot', $tmp_dir . '/test/custom/');
        mkdir(dirname(ForgeConfig::get('sys_pluginsroot')));

        $shortname = 'shortname';
        $pm        = \Mockery::spy(\PluginManager::class);
        $pm->shouldReceive('pluginIsCustom')->andReturns(false, true, true);
        $pm->shouldReceive('getNameForPlugin')->andReturns($shortname);
        $p = \Mockery::mock(\Plugin::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $p->shouldReceive('_getPluginManager')->andReturns($pm);

        //Plugin is official
        mkdir(ForgeConfig::get('sys_custompluginsroot'));
        mkdir(ForgeConfig::get('sys_custompluginsroot') . $shortname);
        mkdir(ForgeConfig::get('sys_custompluginsroot') . $shortname . '/www/');
        mkdir(ForgeConfig::get('sys_custompluginsroot') . $shortname . '/www/themes/');
        mkdir(ForgeConfig::get('sys_custompluginsroot') . $shortname . '/www/themes/' . ForgeConfig::get('sys_user_theme'));
        $this->assertEquals(ForgeConfig::get('sys_custompluginspath') . '/' . $shortname . '/themes/' . ForgeConfig::get('sys_user_theme'), $p->getThemePath());
        rmdir(ForgeConfig::get('sys_custompluginsroot') . $shortname . '/www/themes/' . ForgeConfig::get('sys_user_theme'));
        rmdir(ForgeConfig::get('sys_custompluginsroot') . $shortname . '/www/themes/');
        rmdir(ForgeConfig::get('sys_custompluginsroot') . $shortname . '/www/');
        rmdir(ForgeConfig::get('sys_custompluginsroot') . $shortname);
        rmdir(ForgeConfig::get('sys_custompluginsroot'));
        clearstatcache();
        mkdir(ForgeConfig::get('sys_pluginsroot'));
        mkdir(ForgeConfig::get('sys_pluginsroot') . $shortname);
        mkdir(ForgeConfig::get('sys_pluginsroot') . $shortname . '/www/');
        mkdir(ForgeConfig::get('sys_pluginsroot') . $shortname . '/www/themes/');
        mkdir(ForgeConfig::get('sys_pluginsroot') . $shortname . '/www/themes/' . ForgeConfig::get('sys_user_theme'));
        $this->assertEquals(ForgeConfig::get('sys_pluginspath') . '/' . $shortname . '/themes/' . ForgeConfig::get('sys_user_theme'), $p->getThemePath());
        rmdir(ForgeConfig::get('sys_pluginsroot') . $shortname . '/www/themes/' . ForgeConfig::get('sys_user_theme'));
        rmdir(ForgeConfig::get('sys_pluginsroot') . $shortname . '/www/themes/');
        rmdir(ForgeConfig::get('sys_pluginsroot') . $shortname . '/www/');
        rmdir(ForgeConfig::get('sys_pluginsroot') . $shortname);
        rmdir(ForgeConfig::get('sys_pluginsroot'));
        clearstatcache();
        mkdir(ForgeConfig::get('sys_custompluginsroot'));
        mkdir(ForgeConfig::get('sys_custompluginsroot') . $shortname);
        mkdir(ForgeConfig::get('sys_custompluginsroot') . $shortname . '/www/');
        mkdir(ForgeConfig::get('sys_custompluginsroot') . $shortname . '/www/themes/');
        mkdir(ForgeConfig::get('sys_custompluginsroot') . $shortname . '/www/themes/default');
        $this->assertEquals(ForgeConfig::get('sys_custompluginspath') . '/' . $shortname . '/themes/default', $p->getThemePath());
        rmdir(ForgeConfig::get('sys_custompluginsroot') . $shortname . '/www/themes/default');
        rmdir(ForgeConfig::get('sys_custompluginsroot') . $shortname . '/www/themes/');
        rmdir(ForgeConfig::get('sys_custompluginsroot') . $shortname . '/www/');
        rmdir(ForgeConfig::get('sys_custompluginsroot') . $shortname);
        rmdir(ForgeConfig::get('sys_custompluginsroot'));
        clearstatcache();
        mkdir(ForgeConfig::get('sys_pluginsroot'));
        mkdir(ForgeConfig::get('sys_pluginsroot') . $shortname);
        mkdir(ForgeConfig::get('sys_pluginsroot') . $shortname . '/www/');
        mkdir(ForgeConfig::get('sys_pluginsroot') . $shortname . '/www/themes/');
        mkdir(ForgeConfig::get('sys_pluginsroot') . $shortname . '/www/themes/default');
        $this->assertEquals(ForgeConfig::get('sys_pluginspath') . '/' . $shortname . '/themes/default', $p->getThemePath());
        rmdir(ForgeConfig::get('sys_pluginsroot') . $shortname . '/www/themes/default');
        rmdir(ForgeConfig::get('sys_pluginsroot') . $shortname . '/www/themes/');
        rmdir(ForgeConfig::get('sys_pluginsroot') . $shortname . '/www/');
        rmdir(ForgeConfig::get('sys_pluginsroot') . $shortname);
        rmdir(ForgeConfig::get('sys_pluginsroot'));

        //Now plugin is custom
        mkdir(ForgeConfig::get('sys_custompluginsroot'));
        mkdir(ForgeConfig::get('sys_custompluginsroot') . $shortname);
        mkdir(ForgeConfig::get('sys_custompluginsroot') . $shortname . '/www/');
        mkdir(ForgeConfig::get('sys_custompluginsroot') . $shortname . '/www/themes/');
        mkdir(ForgeConfig::get('sys_custompluginsroot') . $shortname . '/www/themes/' . ForgeConfig::get('sys_user_theme'));
        $this->assertEquals(ForgeConfig::get('sys_custompluginspath') . '/' . $shortname . '/themes/' . ForgeConfig::get('sys_user_theme'), $p->getThemePath());
        rmdir(ForgeConfig::get('sys_custompluginsroot') . $shortname . '/www/themes/' . ForgeConfig::get('sys_user_theme'));
        rmdir(ForgeConfig::get('sys_custompluginsroot') . $shortname . '/www/themes/');
        rmdir(ForgeConfig::get('sys_custompluginsroot') . $shortname . '/www/');
        rmdir(ForgeConfig::get('sys_custompluginsroot') . $shortname);
        rmdir(ForgeConfig::get('sys_custompluginsroot'));
        clearstatcache();
        mkdir(ForgeConfig::get('sys_custompluginsroot'));
        mkdir(ForgeConfig::get('sys_custompluginsroot') . $shortname);
        mkdir(ForgeConfig::get('sys_custompluginsroot') . $shortname . '/www/');
        mkdir(ForgeConfig::get('sys_custompluginsroot') . $shortname . '/www/themes/');
        mkdir(ForgeConfig::get('sys_custompluginsroot') . $shortname . '/www/themes/default');
        $this->assertEquals(ForgeConfig::get('sys_custompluginspath') . '/' . $shortname . '/themes/default', $p->getThemePath());
        rmdir(ForgeConfig::get('sys_custompluginsroot') . $shortname . '/www/themes/default');
        rmdir(ForgeConfig::get('sys_custompluginsroot') . $shortname . '/www/themes/');
        rmdir(ForgeConfig::get('sys_custompluginsroot') . $shortname . '/www/');
        rmdir(ForgeConfig::get('sys_custompluginsroot') . $shortname);
        rmdir(ForgeConfig::get('sys_custompluginsroot'));

        rmdir(dirname(ForgeConfig::get('sys_custompluginsroot')));
    }

    public function testGetThemePathShouldReturnNullIfNoUserTheme(): void
    {
        $tmp_dir = \org\bovigo\vfs\vfsStream::setup()->url();
        ForgeConfig::set('sys_pluginspath', '/plugins');
        ForgeConfig::set('sys_custompluginspath', '/customplugins');
        ForgeConfig::set('sys_pluginsroot', $tmp_dir . '/test/plugins/');
        ForgeConfig::set('sys_custompluginsroot', $tmp_dir . '/test/custom/');

        $shortname = 'shortname';
        $pm        = \Mockery::spy(\PluginManager::class);
        $pm->shouldReceive('getNameForPlugin')->andReturns($shortname);

        $p = \Mockery::mock(\Plugin::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $p->shouldReceive('_getPluginManager')->andReturns($pm);

        $this->assertEquals('', $p->getThemePath());
    }

    public function testGetFilesystemPath(): void
    {
        ForgeConfig::set('sys_pluginsroot', '/my/application');

        $pm = \Mockery::spy(\PluginManager::class);
        $pm->shouldReceive('getNameForPlugin')->andReturns('zataz');
        $pm->shouldReceive('pluginIsCustom')->andReturns(false);

        $p = \Mockery::mock(\Plugin::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $p->shouldReceive('_getPluginManager')->andReturns($pm);

        $this->assertEquals('/my/application/zataz', $p->getFilesystemPath());
    }

    public function testGetFilesystemPathCustom(): void
    {
        ForgeConfig::set('sys_custompluginsroot', '/my/custom/application');

        $pm = \Mockery::spy(\PluginManager::class);
        $pm->shouldReceive('getNameForPlugin')->andReturns('zataz');
        $pm->shouldReceive('pluginIsCustom')->andReturns(true);

        $p = \Mockery::mock(\Plugin::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $p->shouldReceive('_getPluginManager')->andReturns($pm);

        $this->assertEquals('/my/custom/application/zataz', $p->getFilesystemPath());
    }

    public function testGetFilesystemPathWithSlashAtTheEnd(): void
    {
        ForgeConfig::set('sys_pluginsroot', '/my/application');

        $pm = \Mockery::spy(\PluginManager::class);
        $pm->shouldReceive('getNameForPlugin')->andReturns('zataz');
        $pm->shouldReceive('pluginIsCustom')->andReturns(false);

        $p = \Mockery::mock(\Plugin::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $p->shouldReceive('_getPluginManager')->andReturns($pm);

        $this->assertEquals('/my/application/zataz', $p->getFilesystemPath());
    }

    public function testHasNoDependenciesByDefault(): void
    {
        $plugin = new Plugin();
        $this->assertEmpty($plugin->getDependencies());
    }

    public function testNotAllowedToListenSameHookSeveralTimes(): void
    {
        $plugin = new class extends \Plugin
        {
            public function bla(): void
            {
            }
        };
        $this->expectException(Exception::class);
        $plugin->addHook('bla');
        $plugin->addHook('bla');
    }

    public function testSettingIsRestrictedByPassDatabaseCall(): void
    {
        $plugin = Mockery::mock(Plugin::class)->makePartial();
        $plugin->shouldReceive('getServiceShortname')->andReturn('fooservice');
        $plugin->setIsRestricted(false);
        $plugin->shouldReceive('isAllowed')->never();
        $services = [];
        $params   = [
            'project'  => Mockery::mock(Project::class),
            'services' => &$services,
        ];
        $plugin->services_allowed_for_project($params);

        $this->assertEquals(['fooservice'], $services);
    }

    public function testNoSettingIsRestrictedFallsbackOnDbCheck(): void
    {
        $plugin = Mockery::mock(Plugin::class)->makePartial();
        $plugin->shouldReceive('getServiceShortname')->andReturn('fooservice');
        $plugin->shouldReceive('isAllowed')->once()->andReturn(true);
        $services = [];
        $params   = [
            'project'  => Mockery::mock(Project::class, ['getID' => 101]),
            'services' => &$services,
        ];
        $plugin->services_allowed_for_project($params);

        $this->assertEquals(['fooservice'], $services);
    }

    public function testSettingIsRestrictedToTrueFallsbackOnDbCheck(): void
    {
        $plugin = Mockery::mock(Plugin::class)->makePartial();
        $plugin->shouldReceive('getServiceShortname')->andReturn('fooservice');
        $plugin->setIsRestricted(true);
        $plugin->shouldReceive('isAllowed')->once()->andReturn(true);
        $services = [];
        $params   = [
            'project'  => Mockery::mock(Project::class, ['getID' => 101]),
            'services' => &$services,
        ];
        $plugin->services_allowed_for_project($params);

        $this->assertEquals(['fooservice'], $services);
    }

    public function testPluginIsNotAllowedToProject(): void
    {
        $plugin = Mockery::mock(Plugin::class)->makePartial();
        $plugin->shouldReceive('getServiceShortname')->andReturn('fooservice');
        $plugin->shouldReceive('isAllowed')->once()->andReturn(false);
        $services = [];
        $params   = [
            'project'  => Mockery::mock(Project::class, ['getID' => 101]),
            'services' => &$services,
        ];
        $plugin->services_allowed_for_project($params);

        $this->assertEquals([], $services);
    }

    private function getFakePluginToTestHooks(): Plugin
    {
        return new class extends Plugin
        {
            public function hook1(): void
            {
            }

            public function hook2(): void
            {
            }

            public function anEvent(): void
            {
            }

            public function doSomething(): void
            {
            }

            public function conflictInCallbacks(): void
            {
            }

            public function conflict_in_callbacks(): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
            {
            }
        };
    }
}
