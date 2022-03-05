<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\unit\Tracker\Creation\JiraImporter\Import\Semantic;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Psr\Log\NullLogger;
use SimpleXMLElement;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Creation\JiraImporter\Import\Semantic\SemanticsXMLExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldAndValueIDGenerator;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\JiraFieldAPIAllowedValueRepresentation;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\ListFieldMapping;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\ScalarFieldMapping;
use Tuleap\Tracker\Creation\JiraImporter\Import\Values\StatusValuesCollection;

class SemanticsXMLExporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    public function testExportsTheSemantics(): void
    {
        $tracker_node = new SimpleXMLElement('<tracker/>');
        $mapping      = new FieldMappingCollection();
        $mapping->addMapping(
            new ScalarFieldMapping(
                'summary',
                'Summary',
                'Fsummary',
                'summary',
                Tracker_FormElementFactory::FIELD_STRING_TYPE,
            )
        );
        $mapping->addMapping(
            new ScalarFieldMapping(
                'description',
                'Description',
                'Fdescription',
                'description',
                Tracker_FormElementFactory::FIELD_TEXT_TYPE,
            )
        );
        $mapping->addMapping(
            new ListFieldMapping(
                'status',
                'status',
                'Fstatus',
                'status',
                Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE,
                \Tracker_FormElement_Field_List_Bind_Static::TYPE,
                [],
            )
        );
        $mapping->addMapping(
            new ListFieldMapping(
                'assignee',
                'assignee',
                'Fassignee',
                'Assignee',
                Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE,
                \Tracker_FormElement_Field_List_Bind_Users::TYPE,
                [],
            )
        );

        $collection = new StatusValuesCollection(
            new class extends \Tuleap\Tracker\Test\Tracker\Creation\JiraImporter\Stub\JiraCloudClientStub {
            },
            new NullLogger()
        );
        $collection->initCollectionWithValues(
            [
                JiraFieldAPIAllowedValueRepresentation::buildWithJiraIdOnly(10001, new FieldAndValueIDGenerator()),
                JiraFieldAPIAllowedValueRepresentation::buildWithJiraIdOnly(3, new FieldAndValueIDGenerator()),
            ],
            [
                JiraFieldAPIAllowedValueRepresentation::buildWithJiraIdOnly(10002, new FieldAndValueIDGenerator()),
                JiraFieldAPIAllowedValueRepresentation::buildWithJiraIdOnly(10004, new FieldAndValueIDGenerator()),
                JiraFieldAPIAllowedValueRepresentation::buildWithJiraIdOnly(2, new FieldAndValueIDGenerator()),
            ]
        );

        $exporter = new SemanticsXMLExporter();
        $exporter->exportSemantics(
            $tracker_node,
            $mapping,
            $collection
        );

        $this->assertNotNull($tracker_node->semantics);
        $this->assertCount(5, $tracker_node->semantics->children());

        $semantic_title_node = $tracker_node->semantics->semantic[0];
        $this->assertSame("title", (string) $semantic_title_node['type']);
        $this->assertSame("Fsummary", (string) $semantic_title_node->field['REF']);

        $semantic_description_node = $tracker_node->semantics->semantic[1];
        $this->assertSame("description", (string) $semantic_description_node['type']);
        $this->assertSame("Fdescription", (string) $semantic_description_node->field['REF']);

        $semantic_status_node = $tracker_node->semantics->semantic[2];
        $this->assertSame("status", (string) $semantic_status_node['type']);
        $this->assertSame("Fstatus", (string) $semantic_status_node->field['REF']);
        $this->assertCount(2, $semantic_status_node->open_values->children());

        $semantic_done_node = $tracker_node->semantics->semantic[3];
        self::assertSame("done", (string) $semantic_done_node['type']);
        self::assertCount(3, $semantic_done_node->closed_values->children());

        $semantic_assignee_node = $tracker_node->semantics->semantic[4];
        $this->assertSame("contributor", (string) $semantic_assignee_node['type']);
        $this->assertSame("Fassignee", (string) $semantic_assignee_node->field['REF']);
    }

    public function testItDoesNotExportSemanticTitleIfSummaryFieldNotfoundInMapping(): void
    {
        $tracker_node = new SimpleXMLElement('<tracker/>');
        $mapping      = new FieldMappingCollection();

        $exporter = new SemanticsXMLExporter();
        $exporter->exportSemantics(
            $tracker_node,
            $mapping,
            Mockery::mock(StatusValuesCollection::class)
        );

        $this->assertNotNull($tracker_node->semantics);
        $this->assertNotNull($tracker_node->semantics->semantic);
    }
}
