<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Tools\Xml2Php;

use PhpParser\PrettyPrinter;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Tuleap\Tools\Xml2Php\Tracker\TrackerConvertor;

class ConvertTrackerCommand extends Command
{
    protected static $defaultName = 'convert-tracker';

    private const SRC = 'src';
    private const ID  = 'tracker-id';

    protected function configure(): void
    {
        $this
            ->setDescription('Convert tracker XML structure to PHP code')
            ->setDefinition(
                new InputDefinition([
                    new InputOption(
                        self::SRC,
                        null,
                        InputOption::VALUE_REQUIRED,
                        'Path to XML file to parse.' . PHP_EOL
                    ),
                    new InputOption(
                        self::ID,
                        null,
                        InputOption::VALUE_REQUIRED,
                        'Id of the tracker to retrieve in the XML.' . PHP_EOL
                    ),
                ])
            );
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $src = $input->getOption(self::SRC);
        if (! is_string($src) || ! is_file($src)) {
            throw new InvalidArgumentException(sprintf('%s must be a valid XML file', self::SRC));
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $xml_file   = $input->getOption(self::SRC);
        $tracker_id = $input->getOption(self::ID);

        $contents = file_get_contents($xml_file);
        if (! $contents) {
            $output->writeln('<error>File appears to be empty</error>');
            return 1;
        }
        $project = simplexml_load_string($contents);
        if (! $project) {
            $output->writeln('<error>Unable to parse XML file</error>');
            return 1;
        }

        if (! isset($project->trackers)) {
            $output->writeln('<error>No trackers in XML file</error>');
            return 1;
        }

        foreach ($project->trackers->tracker as $xml_tracker) {
            if ((string) $xml_tracker['id'] === $tracker_id) {
                $this->convert($xml_tracker, $output);
                return 0;
            }
        }

        $output->writeln('<error>Requested tracker not found in XML file</error>');
        return 1;
    }

    private function convert(\SimpleXMLElement $xml_tracker, OutputInterface $output): void
    {
        $logger = new ConsoleLogger($output, [
            LogLevel::INFO => OutputInterface::VERBOSITY_NORMAL,
        ]);

        $printer = new PrettyPrinter\Standard();
        echo $printer->prettyPrintFile(
            TrackerConvertor::buildFromXML($xml_tracker)->get($logger)
        );
    }
}
