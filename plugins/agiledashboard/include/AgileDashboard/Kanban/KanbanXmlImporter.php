<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Kanban;

use AgileDashboard_ConfigurationManager;
use AgileDashboard_KanbanColumnFactory;
use AgileDashboard_KanbanColumnManager;
use AgileDashboard_KanbanFactory;
use AgileDashboard_KanbanManager;
use Psr\Log\LoggerInterface;
use PFUser;
use Project;
use SimpleXMLElement;
use TrackerXmlFieldsMapping;
use Tuleap\XML\MappingsRegistry;

class KanbanXmlImporter
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var AgileDashboard_KanbanManager
     */
    private $kanban_manager;
    /**
     * @var AgileDashboard_ConfigurationManager
     */
    private $agile_dashboard_configuration_manager;
    /**
     * @var AgileDashboard_KanbanColumnManager
     */
    private $dashboard_kanban_column_manager;
    /**
     * @var AgileDashboard_KanbanFactory
     */
    private $dashboard_kanban_factory;
    /**
     * @var AgileDashboard_KanbanColumnFactory
     */
    private $dashboard_kanban_column_factory;

    public function __construct(
        LoggerInterface $logger,
        AgileDashboard_KanbanManager $kanban_manager,
        AgileDashboard_ConfigurationManager $agile_dashboard_configuration_manager,
        AgileDashboard_KanbanColumnManager $dashboard_kanban_column_manager,
        AgileDashboard_KanbanFactory $dashboard_kanban_factory,
        AgileDashboard_KanbanColumnFactory $dashboard_kanban_column_factory,
    ) {
        $this->logger                                = $logger;
        $this->kanban_manager                        = $kanban_manager;
        $this->agile_dashboard_configuration_manager = $agile_dashboard_configuration_manager;
        $this->dashboard_kanban_column_manager       = $dashboard_kanban_column_manager;
        $this->dashboard_kanban_factory              = $dashboard_kanban_factory;
        $this->dashboard_kanban_column_factory       = $dashboard_kanban_column_factory;
    }

    public function import(
        SimpleXMLElement $xml,
        array $tracker_mapping,
        Project $project,
        TrackerXmlFieldsMapping $field_mapping,
        PFUser $user,
        MappingsRegistry $mappings_registry,
    ) {
        if (! $xml->agiledashboard->kanban_list) {
            $this->logger->info("0 Kanban found");

            return;
        }

        $this->activateKanban($xml, $project);

        foreach ($xml->agiledashboard->kanban_list->kanban as $xml_configuration) {
            $attrs = $xml_configuration->attributes();
            $this->logger->info("Creating kanban " . $attrs['name']);

            $kanban_id = $this->kanban_manager->createKanban(
                (string) $attrs["name"],
                $tracker_mapping[(string) $attrs["tracker_id"]]
            );

            $kanban = $this->dashboard_kanban_factory->getKanbanForXmlImport(
                $kanban_id
            );
            $mappings_registry->addReference((string) $attrs['ID'], $kanban);

            foreach ($xml_configuration as $xml_columns) {
                $columns_attrs = $xml_columns->attributes();
                $column        = $this->dashboard_kanban_column_factory->getColumnForAKanban(
                    $kanban,
                    $field_mapping->getNewOpenValueId((string) $columns_attrs['REF']),
                    $user
                );

                $this->logger->info("Updating WIP to " . $attrs['REF']);
                $this->dashboard_kanban_column_manager->updateWipLimit(
                    $user,
                    $kanban,
                    $column,
                    (string) $columns_attrs['wip']
                );
            }
        }
    }

    private function activateKanban(SimpleXMLElement $xml, Project $project)
    {
        $kanban_attrs = $xml->agiledashboard->kanban_list->attributes();
        $kanban_name  = $kanban_attrs['title'];

        $is_scrum_activated  = count($xml->agiledashboard->plannings->children()) > 0;
        $is_kanban_activated = 1;
        $this->agile_dashboard_configuration_manager->updateConfiguration(
            $project->getID(),
            $is_scrum_activated,
            $is_kanban_activated,
            $this->agile_dashboard_configuration_manager->getScrumTitle($project->getID()),
            $kanban_name
        );
    }
}
