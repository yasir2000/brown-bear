<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Config;

use ConfigValueDatabaseProvider;
use ConfigValueProvider;
use ForgeAccess;
use ForgeConfig;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use Tuleap\DB\DBConfig;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;

/**
 * @covers \Tuleap\Config\ConfigValueEnvironmentProvider
 */
class ForgeConfigTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;
    use GlobalLanguageMock;

    protected function tearDown(): void
    {
        putenv('TULEAP_LOCAL_INC');
        putenv('TULEAP_SYS_DBHOST');
        parent::tearDown();
    }

    public function testDefaultLoadSequenceGetValueFromLocalInc(): void
    {
        putenv('TULEAP_LOCAL_INC=' . __DIR__ . '/_fixtures/sequence/local.inc');
        $dao = $this->createMock(ConfigDao::class);
        $dao->method('searchAll')->willReturn([]);
        ForgeConfig::setDatabaseConfigDao($dao);

        ForgeConfig::loadInSequence();
        self::assertEquals('Matchete', ForgeConfig::get('sys_fullname'));
    }

    public function testDefaultLoadSequenceGetValueFromDatabase(): void
    {
        putenv('TULEAP_LOCAL_INC=' . __DIR__ . '/_fixtures/sequence/local.inc');
        $dao = $this->createMock(ConfigDao::class);
        $dao->method('searchAll')->willReturn(
            [
                [
                    'name'  => \ProjectManager::CONFIG_PROJECT_APPROVAL,
                    'value' => '1',
                ],
            ]
        );
        ForgeConfig::setDatabaseConfigDao($dao);

        ForgeConfig::loadInSequence();
        self::assertEquals('1', ForgeConfig::get(\ProjectManager::CONFIG_PROJECT_APPROVAL));
    }

    public function testDatabaseParametersFallbackOnFiles(): void
    {
        putenv('TULEAP_LOCAL_INC=' . __DIR__ . '/_fixtures/sequence/local.inc');
        $dao = $this->createMock(ConfigDao::class);
        $dao->method('searchAll')->willReturn([]);
        ForgeConfig::setDatabaseConfigDao($dao);

        ForgeConfig::loadInSequence();
        self::assertEquals('foo', ForgeConfig::get('sys_dbhost'));
    }

    public function testEnvironmentTakesPrecedenceForDatabaseParameters(): void
    {
        putenv('TULEAP_LOCAL_INC=' . __DIR__ . '/_fixtures/sequence/local.inc');
        putenv('TULEAP_SYS_DBHOST=db.example.com');
        $dao = $this->createMock(ConfigDao::class);
        $dao->method('searchAll')->willReturn([]);
        ForgeConfig::setDatabaseConfigDao($dao);

        ForgeConfig::loadInSequence();
        self::assertEquals('db.example.com', ForgeConfig::get('sys_dbhost'));
    }

    public function testLoadDatabaseConfigFromEnvironmentWithEnv(): void
    {
        putenv('TULEAP_SYS_DBHOST=db.example.com');
        ForgeConfig::loadDatabaseConfig();
        self::assertEquals('db.example.com', ForgeConfig::get('sys_dbhost'));
    }

    public function testLoadDatabaseConfigDefaultValues(): void
    {
        ForgeConfig::loadDatabaseConfig();
        self::assertEquals('localhost', ForgeConfig::get('sys_dbhost'));
        self::assertEquals('tuleap', ForgeConfig::get(DBConfig::CONF_DBNAME));
        self::assertSame(3306, ForgeConfig::get(DBConfig::CONF_PORT));
        self::assertSame('0', ForgeConfig::get(DBConfig::CONF_ENABLE_SSL));
    }

    public function testUsage(): void
    {
        $this->assertFalse(ForgeConfig::get('toto'));
        ForgeConfig::loadFromFile(__DIR__ . '/_fixtures/config/local.inc');
        $this->assertEquals(ForgeConfig::get('toto'), 66);
        $this->assertFalse(ForgeConfig::get('titi')); //not defined should return false
    }

    public function testDefault(): void
    {
        $this->assertEquals(
            ForgeConfig::get('toto', 99),
            99
        ); //not defined should return default value given in parameter
        ForgeConfig::loadFromFile(__DIR__ . '/_fixtures/config/local.inc');
        $this->assertEquals(
            ForgeConfig::get('toto', 99),
            66
        ); //now it is defined. Should NOT return default value given in parameter
    }

    public function testMultipleFiles(): void
    {
        // Unitialized
        $this->assertSame(ForgeConfig::get('toto'), false);
        $this->assertSame(ForgeConfig::get('tutu'), false);
        $this->assertSame(ForgeConfig::get('tata'), false);

        // Load the first file
        ForgeConfig::loadFromFile(__DIR__ . '/_fixtures/config/local.inc');
        $this->assertSame(ForgeConfig::get('toto'), 66);
        $this->assertSame(ForgeConfig::get('tutu'), 123);
        $this->assertSame(ForgeConfig::get('tata'), false);

        // Load the second one. Merge of the conf
        ForgeConfig::loadFromFile(__DIR__ . '/_fixtures/config/other_file.inc.dist');
        $this->assertSame(ForgeConfig::get('toto'), 66);
        $this->assertSame(ForgeConfig::get('tutu'), 421);
        $this->assertSame(ForgeConfig::get('tata'), 456);
    }

    public function testDump(): void
    {
        ForgeConfig::loadFromFile(__DIR__ . '/_fixtures/config/local.inc');
        ob_start();
        ForgeConfig::dump();
        $dump = ob_get_clean();
        $this->assertEquals($dump, var_export(['toto' => 66, 'tutu' => 123], true));
    }

    public function testItDoesntEmitAnyNoticesOrWarningsWhenThereAreTwoRestoresAndOneLoad(): void
    {
        ForgeConfig::restore();
        ForgeConfig::restore();
        ForgeConfig::loadFromFile(__DIR__ . '/_fixtures/config/local.inc');
        $this->assertTrue(true);
    }

    public function testItLoadsFromDatabase(): void
    {
        $dao = \Mockery::mock(\Tuleap\Config\ConfigDao::class);
        $dao->shouldReceive('searchAll')->andReturns([['name' => 'a_var', 'value' => 'its_value']]);
        (new class extends ForgeConfig {
            public static function load(ConfigValueProvider $value_provider): void
            {
                parent::load($value_provider);
            }
        })::load(new ConfigValueDatabaseProvider($dao));

        $this->assertEquals('its_value', ForgeConfig::get('a_var'));
    }

    public function testItReturnsTrueIfAccessModeIsAnonymous(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);

        $this->assertTrue(ForgeConfig::areAnonymousAllowed());
    }

    public function testItReturnsFalseIfAccessModeIsRegular(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);

        $this->assertFalse(ForgeConfig::areAnonymousAllowed());
    }

    public function testItReturnsFalseIfAccessModeIsRestricted(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);

        $this->assertFalse(ForgeConfig::areAnonymousAllowed());
    }

    public function testFeatureFlag(): void
    {
        ForgeConfig::set('feature_flag_list_picker', true);

        $this->assertTrue(ForgeConfig::getFeatureFlag('list_picker'));
        $this->assertFalse(ForgeConfig::getFeatureFlag('another_flag'));
    }

    public function testItReturnsEmptyArrayIfRestrictedUserFileIsTheDefaultOne(): void
    {
        $default_file = __DIR__ . '/../../../../site-content/en_US/include/restricted_user_permissions.txt';

        $GLOBALS['Language']
            ->method('getContent')
            ->with('include/restricted_user_permissions', 'en_US')
            ->willReturn($default_file);

        $this->assertEquals(ForgeConfig::getSuperPublicProjectsFromRestrictedFile(), []);
    }

    public function testItReturnsArrayOfProjectIdsDefinedInRestrictedUserFile(): void
    {
        $customised_file = __DIR__ . '/_fixtures/restricted_user_permissions.txt';

        $GLOBALS['Language']
            ->method('getContent')
            ->with('include/restricted_user_permissions', 'en_US')
            ->willReturn($customised_file);

        $this->assertEquals(ForgeConfig::getSuperPublicProjectsFromRestrictedFile(), [123, 456]);
    }

    public function testItDoesNotStorePublicProjectsInTheStorage(): void
    {
        $customised_file = __DIR__ . '/_fixtures/restricted_user_permissions.txt';

        $GLOBALS['Language']
            ->method('getContent')
            ->with('include/restricted_user_permissions', 'en_US')
            ->willReturn($customised_file);

        ForgeConfig::getSuperPublicProjectsFromRestrictedFile();

        $this->assertFalse(ForgeConfig::get('public_projects'));
    }

    public function testEncryptedSecretIsRevealed(): void
    {
        ForgeConfig::set('sys_custom_dir', vfsStream::setup('root', null, ['conf' => []])->url());
        ForgeConfig::set(
            \Tuleap\DB\DBAuthUserConfig::PASSWORD,
            ForgeConfig::encryptValue(new \Tuleap\Cryptography\ConcealedString('a very good secret')),
        );

        self::assertEquals('a very good secret', ForgeConfig::getSecretAsClearText(\Tuleap\DB\DBAuthUserConfig::PASSWORD));
    }
}
