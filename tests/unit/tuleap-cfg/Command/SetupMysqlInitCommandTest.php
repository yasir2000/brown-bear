<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace TuleapCfg\Command;

use org\bovigo\vfs\vfsStream;
use Symfony\Component\Console\Tester\CommandTester;
use Tuleap\DB\DBAuthUserConfig;
use Tuleap\ForgeConfigSandbox;
use TuleapCfg\Command\SetupMysql\DatabaseConfigurator;
use function PHPUnit\Framework\assertStringContainsString;

/**
 * @covers \TuleapCfg\Command\SetupMysql\DatabaseConfigurator
 */
final class SetupMysqlInitCommandTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    private string $base_dir;
    private CommandTester $command_tester;
    private TestDBWrapper $db_wrapper;

    protected function setUp(): void
    {
        $this->base_dir = vfsStream::setup()->url();
        mkdir($this->base_dir . '/etc/tuleap/conf', 0750, true);
        mkdir($this->base_dir . '/etc/pki/ca-trust/extracted/pem/', 0750, true);
        touch($this->base_dir . '/etc/pki/ca-trust/extracted/pem/tls-ca-bundle.pem');
        touch($this->base_dir . '/some_ca.pem');

        $this->db_wrapper = new TestDBWrapper();

        $connection_manager   = new TestConnectionManager($this->db_wrapper);
        $this->command_tester = new CommandTester(
            new SetupMysqlInitCommand(
                $connection_manager,
                new DatabaseConfigurator(\PasswordHandlerFactory::getPasswordHandler(), $connection_manager),
                $this->base_dir
            )
        );
    }

    protected function tearDown(): void
    {
        putenv('TULEAP_DB_SSL_MODE');
        putenv('TULEAP_DB_SSL_CA');
    }

    public function testItWritesConfigurationFileWithGivenValuesNoSSL(): void
    {
        $this->command_tester->execute([
            '--skip-database'  => true,
            '--host'           => '192.0.2.1',
            '--admin-password' => 'welcome0',
            '--app-password'   => 'a complex password',
        ]);

        $this->assertEquals(0, $this->command_tester->getStatusCode());
        $this->assertEmpty($this->command_tester->getDisplay());

        $this->assertFileExists($this->base_dir . '/etc/tuleap/conf/database.inc');
        require($this->base_dir . '/etc/tuleap/conf/database.inc');
        $this->assertEquals('192.0.2.1', $sys_dbhost);
        self::assertSame(3306, $sys_dbport);
        $this->assertEquals('tuleap', $sys_dbname);
        $this->assertEquals('tuleapadm', $sys_dbuser);
        $this->assertEquals('a complex password', $sys_dbpasswd);

        $this->assertEquals('0', $sys_enablessl);
        $this->assertEquals('/etc/pki/ca-trust/extracted/pem/tls-ca-bundle.pem', $sys_db_ssl_ca);
        $this->assertEquals('0', $sys_db_ssl_verify_cert);
    }

    public function testItWritesConfigurationFileWithSpecifiedPort(): void
    {
        $this->command_tester->execute([
            '--skip-database'  => true,
            '--host'           => '192.0.2.1',
            '--port'           => '3307',
            '--admin-password' => 'welcome0',
            '--app-password'   => 'a complex password',
        ]);

        $this->assertEquals(0, $this->command_tester->getStatusCode());
        $this->assertEmpty($this->command_tester->getDisplay());

        $this->assertFileExists($this->base_dir . '/etc/tuleap/conf/database.inc');
        require($this->base_dir . '/etc/tuleap/conf/database.inc');
        $this->assertEquals(3307, $sys_dbport);
    }

    public function testItWritesConfigurationFileWithGivenValuesEnableSSL(): void
    {
        $this->command_tester->execute([
            '--skip-database'  => true,
            '--host'           => '192.0.2.1',
            '--admin-password' => 'welcome0',
            '--app-password'   => 'a complex password',
            '--ssl-mode'       => 'verify-ca',
            '--ssl-ca'         => '/some_ca.pem',
        ]);

        $this->assertEquals(0, $this->command_tester->getStatusCode());
        $this->assertEmpty($this->command_tester->getDisplay());

        $this->assertFileExists($this->base_dir . '/etc/tuleap/conf/database.inc');
        require($this->base_dir . '/etc/tuleap/conf/database.inc');
        $this->assertEquals('192.0.2.1', $sys_dbhost);
        $this->assertEquals('tuleap', $sys_dbname);
        $this->assertEquals('tuleapadm', $sys_dbuser);
        $this->assertEquals('a complex password', $sys_dbpasswd);

        $this->assertEquals('1', $sys_enablessl);
        $this->assertEquals('/some_ca.pem', $sys_db_ssl_ca);
        $this->assertEquals('1', $sys_db_ssl_verify_cert);
    }

    public function testNoConfigurationFileWrittenIfPasswordNotProvided(): void
    {
        $this->command_tester->execute([
            '--skip-database'  => true,
            '--host'           => '192.0.2.1',
            '--app-password'   => 'a complex password',
        ]);

        $this->assertEquals(1, $this->command_tester->getStatusCode());
        $this->assertFileDoesNotExist($this->base_dir . '/etc/tuleap/conf/database.inc');
    }

    public function testUsesSSLModeDefinedWithEnvVariable(): void
    {
        putenv('TULEAP_DB_SSL_MODE=no-verify');

        $this->command_tester->execute([
            '--skip-database'  => true,
            '--host'           => '192.0.2.1',
            '--admin-password' => 'welcome0',
            '--app-password'   => 'a complex password',
        ]);

        $this->assertEquals(0, $this->command_tester->getStatusCode());
        $this->assertEmpty($this->command_tester->getDisplay());

        require($this->base_dir . '/etc/tuleap/conf/database.inc');

        $this->assertEquals('1', $sys_enablessl);
    }

    public function testUsesSSLCAFileDefinedWithEnvVariable(): void
    {
        putenv('TULEAP_DB_SSL_MODE=no-verify');
        putenv('TULEAP_DB_SSL_CA=/some_ca.pem');

        $this->command_tester->execute([
            '--skip-database'  => true,
            '--host'           => '192.0.2.1',
            '--admin-password' => 'welcome0',
            '--app-password'   => 'a complex password',
        ]);

        $this->assertEquals(0, $this->command_tester->getStatusCode());
        $this->assertEmpty($this->command_tester->getDisplay());

        require($this->base_dir . '/etc/tuleap/conf/database.inc');

        $this->assertEquals('/some_ca.pem', $sys_db_ssl_ca);
    }

    public function testGrantTuleapAccessApplicationToUser(): void
    {
        $this->command_tester->execute([
            '--admin-password' => 'welcome0',
            '--app-password'   => 'a complex password',
            '--app-user'       => 'tuleap',
        ]);

        $this->assertEquals(0, $this->command_tester->getStatusCode());

        $this->db_wrapper->assertContains("CREATE USER IF NOT EXISTS 'tuleap'@'%' IDENTIFIED BY 'a complex password'");
        $this->db_wrapper->assertContains("GRANT ALL PRIVILEGES ON 'tuleap'.* TO 'tuleap'@'%'");
    }

    public function testGrantTuleapAccessToNssUser(): void
    {
        $this->command_tester->execute([
            '--admin-password' => 'welcome0',
            '--nss-password'   => 'another complex password',
            '--nss-user'       => 'dbauthuser',
        ]);

        $this->assertEquals(0, $this->command_tester->getStatusCode());

        $this->db_wrapper->assertContains("CREATE USER IF NOT EXISTS 'dbauthuser'@'%' IDENTIFIED BY 'another complex password'");
        $this->db_wrapper->assertContains("GRANT CREATE,SELECT ON 'tuleap'.'user' TO 'dbauthuser'@'%'");

        $first_insert_pos = array_search("REPLACE INTO 'tuleap'.forgeconfig (name, value) VALUES (?, ?)", $this->db_wrapper->statements, true);
        self::assertEquals(DBAuthUserConfig::USER, $this->db_wrapper->statements_params[$first_insert_pos][0]);
        self::assertEquals('dbauthuser', $this->db_wrapper->statements_params[$first_insert_pos][1]);

        self::assertEquals(DBAuthUserConfig::PASSWORD, $this->db_wrapper->statements_params[$first_insert_pos + 1][0]);
        self::assertNotEmpty($this->db_wrapper->statements_params[$first_insert_pos + 1][1]);
    }

    public function testGrantMediawikiPerProjectAccessToApplicationUser(): void
    {
        $this->command_tester->execute([
            '--admin-password' => 'welcome0',
            '--app-password'   => 'a complex password',
            '--app-user'       => 'tuleap',
            '--mediawiki'      => 'per-project',
        ]);

        $this->assertEquals(0, $this->command_tester->getStatusCode());

        $this->db_wrapper->assertContains("GRANT ALL PRIVILEGES ON `plugin_mediawiki_%`.* TO 'tuleap'@'%'");
    }

    public function testGrantMediawikiCentralAccessToApplicationUser(): void
    {
        $this->command_tester->execute([
            '--admin-password' => 'welcome0',
            '--app-password'   => 'a complex password',
            '--app-user'       => 'tuleap',
            '--mediawiki'      => 'central',
        ]);

        $this->assertEquals(0, $this->command_tester->getStatusCode());

        $this->db_wrapper->assertContains("GRANT ALL PRIVILEGES ON 'tuleap_mediawiki'.* TO 'tuleap'@'%'");
    }

    public function testGrantUserWithSpecificHostname(): void
    {
        $this->command_tester->execute([
            '--admin-password' => 'welcome0',
            '--app-password'   => 'a complex password',
            '--app-user'       => 'tuleap',
            '--grant-hostname' => '192.0.2.1',
        ]);

        $this->assertEquals(0, $this->command_tester->getStatusCode());

        $this->db_wrapper->assertContains("GRANT ALL PRIVILEGES ON 'tuleap'.* TO 'tuleap'@'192.0.2.1'");
    }

    public function testItFeedsTheDatabaseWithInitValues(): void
    {
        $this->db_wrapper->setRunReturn('SHOW TABLES', []);

        $this->command_tester->execute([
            '--admin-password' => 'welcome0',
            '--app-password'   => 'a complex password',
            '--app-user'       => 'tuleap',
            '--grant-hostname' => '192.0.2.1',
            '--tuleap-fqdn'    => 'tuleap.example.com',
            '--site-admin-password' => 'welcome1',
        ]);

        assertStringContainsString('CREATE TABLE `groups` (', implode("\n", $this->db_wrapper->statements));
    }
}
