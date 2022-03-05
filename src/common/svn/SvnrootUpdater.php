<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\Svn;

use Backend;
use ForgeConfig;
use Psr\Log\LoggerInterface;
use Tuleap\DB\DBConnection;
use Tuleap\Queue\QueueFactory;
use Tuleap\System\ApacheServiceControl;
use Tuleap\System\ServiceControl;
use TuleapCfg\Command\ProcessFactory;
use WrapperLogger;
use Exception;

class SvnrootUpdater
{
    public const QUEUE_PREFIX = 'tuleap_svnroot_update';
    public const TOPIC        = 'tuleap.svn.svnroot.update';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var \Tuleap\Queue\PersistentQueue
     */
    private $queue;
    /**
     * @var DBConnection
     */
    private $db_connection;

    public function __construct(LoggerInterface $logger, DBConnection $db_connection)
    {
        $this->logger        = new WrapperLogger($logger, 'svnroot_updater');
        $this->queue         = (new QueueFactory($this->logger))->getPersistentQueue(self::QUEUE_PREFIX);
        $this->db_connection = $db_connection;
    }

    public function push()
    {
        $this->logger->info('Send message to ' . self::TOPIC);
        $this->queue->pushSinglePersistentMessage(self::TOPIC, 'Update');
        $this->logger->debug('Done');
    }

    /**
     * @param string $server_id
     */
    public function listen($server_id)
    {
        $this->logger->info("Wait for messages on " . get_class($this->queue));

        $generate = function (): void {
            $this->db_connection->reconnectAfterALongRunningProcess();
            ForgeConfig::set('svn_root_file', '/etc/httpd/conf.d/svnroot.conf');

            $apache_conf_generator = new ApacheConfGenerator(
                new ApacheServiceControl(
                    new ServiceControl(),
                    new ProcessFactory()
                ),
                Backend::instanceSVN()
            );
            $apache_conf_generator->generate();
        };

        $this->logger->debug('Re-generate conf at start');
        $generate();

        $logger = $this->logger;

        $this->logger->debug('Waiting for new events');
        $this->queue->listen(self::QUEUE_PREFIX . $server_id, self::TOPIC, static function (string $msg) use ($logger, $generate): void {
            try {
                $logger->info("Received " . $msg);
                $generate();
                $logger->info("Update completed");
            } catch (Exception $e) {
                $logger->error("Caught exception " . $e::class . ": " . $e->getMessage());
            }
        });
    }
}
