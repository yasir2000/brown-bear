<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

require_once __DIR__ . '/../vendor/autoload.php';
require_once 'constants.php';

use Tuleap\Reference\GetReferenceEvent;
use Tuleap\ReferenceAliasCore\Dao;
use Tuleap\ReferenceAliasCore\ReferencesImporter;
use Tuleap\ReferenceAliasCore\ReferencesBuilder;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class referencealias_corePlugin extends Plugin
{
    /**
     * @var Dao
     */
    private $dao;

    /**
     * @var ReferencesBuilder
     */
    private $references_builder;

    public function __construct($id)
    {
        parent::__construct($id);
        $this->dao                = new Dao();
        $this->references_builder = new ReferencesBuilder($this->dao, ProjectManager::instance());
        $this->setScope(self::SCOPE_SYSTEM);
        $this->addHook(Event::IMPORT_COMPAT_REF_XML, 'importCompatRefXML');
        $this->addHook(GetReferenceEvent::NAME);
        $this->addHook(Event::GET_PLUGINS_EXTRA_REFERENCES, 'registerExtraReferences');
    }

    /**
     * @return Tuleap\ReferenceAliasCore\Plugin\PluginInfo
     */
    public function getPluginInfo()
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new Tuleap\ReferenceAliasCore\Plugin\PluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function getServiceShortname()
    {
        return 'plugin_referencealias_core';
    }

    public function importCompatRefXML($params)
    {
        $targeted_service_name = $params['service_name'];
        if ($targeted_service_name == 'frs') {
            $xml          = $params['xml_content'];
            $project      = $params['project'];
            $logger       = new WrapperLogger($params['logger'], 'ReferenceAliasCoreImporter');
            $created_refs = $params['created_refs'];
            $importer     = new ReferencesImporter($this->dao, $logger);
            $importer->importCompatRefXML($params['configuration'], $project, $xml, $created_refs);
        }
    }

    public function getReference(GetReferenceEvent $event): void
    {
        $keyword = $event->getKeyword();
        $value   = $event->getValue();
        $project = $event->getProject();

        $reference = $this->references_builder->getReference($project, $keyword, $value);

        if ($reference !== null) {
            $event->setReference($reference);
        }
    }

    public function registerExtraReferences($params)
    {
        foreach ($this->references_builder->getExtraReferenceSpecs() as $refspec) {
            $params['refs'][] = $refspec;
        }
    }
}
