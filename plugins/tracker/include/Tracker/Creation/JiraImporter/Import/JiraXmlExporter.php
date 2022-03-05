<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Creation\JiraImporter\Import;

use EventManager;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Creation\JiraImporter\Configuration\PlatformConfiguration;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\ArtifactsXMLExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Attachment\AttachmentCollectionBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Attachment\AttachmentDownloader;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Attachment\AttachmentXMLExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\CreationStateListValueFormatter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\JiraCloudChangelogEntriesBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\JiraServerChangelogEntriesBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\ListFieldChangeInitialValueRetriever;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Comment\CommentXMLExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Comment\CommentXMLValueEnhancer;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Comment\JiraCloudCommentValuesBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Comment\JiraServerCommentValuesBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\IssueAPIRepresentationCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\LinkedIssuesCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Snapshot\ChangelogSnapshotBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Snapshot\CurrentSnapshotBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Snapshot\InitialSnapshotBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Snapshot\IssueSnapshotCollectionBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\DataChangesetXMLExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\FieldChangeXMLExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Reports\XmlReportAllIssuesExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Reports\XmlReportCreatedRecentlyExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Reports\XmlReportDefaultCriteriaExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Reports\XmlReportDoneIssuesExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Reports\XmlReportExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Reports\XmlReportOpenIssuesExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Reports\XmlReportTableExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Reports\XmlReportUpdatedRecentlyExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Reports\XmlTQLReportExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Semantic\SemanticsXMLExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\StoryPointFieldExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\JiraUserInfoQuerier;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\JiraUserOnTuleapCache;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\JiraUserRetriever;
use Tuleap\Tracker\Creation\JiraImporter\IssueType;
use Tuleap\Tracker\Creation\JiraImporter\JiraClient;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\XML\IDGenerator;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\JiraFieldRetriever;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\JiraToTuleapFieldTypeMapper;
use Tuleap\Tracker\Creation\JiraImporter\Import\Values\StatusValuesCollection;
use Tuleap\Tracker\Creation\JiraImporter\JiraConnectionException;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeArtifactLinksBuilder;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeDateBuilder;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeFileBuilder;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeFloatBuilder;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeListBuilder;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeStringBuilder;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeTextBuilder;
use Tuleap\Tracker\XML\Importer\TrackerImporterUser;
use Tuleap\Tracker\XML\XMLTracker;
use UserManager;
use UserXMLExporter;
use XML_SimpleXMLCDATAFactory;

class JiraXmlExporter
{
    public function __construct(
        private LoggerInterface $logger,
        private JiraClient $wrapper,
        private ErrorCollector $error_collector,
        private JiraFieldRetriever $jira_field_retriever,
        private JiraToTuleapFieldTypeMapper $field_type_mapper,
        private JiraUserRetriever $jira_user_retriever,
        private XmlReportExporter $report_exporter,
        private ArtifactsXMLExporter $artifacts_xml_exporter,
        private SemanticsXMLExporter $semantics_xml_exporter,
        private AlwaysThereFieldsExporter $always_there_fields_exporter,
        private StoryPointFieldExporter $story_point_field_exporter,
        private XmlReportAllIssuesExporter $xml_report_all_issues_exporter,
        private XmlReportOpenIssuesExporter $xml_report_open_issues_exporter,
        private XmlReportDoneIssuesExporter $xml_report_done_issues_exporter,
        private XmlReportCreatedRecentlyExporter $xml_report_created_recently_exporter,
        private XmlReportUpdatedRecentlyExporter $xml_report_updated_recently_exporter,
        private EventDispatcherInterface $event_manager,
    ) {
    }

    /**
     * @throws \RuntimeException
     */
    public static function build(
        JiraClient $wrapper,
        LoggerInterface $logger,
        JiraUserOnTuleapCache $jira_user_on_tuleap_cache,
    ): self {
        $error_collector = new ErrorCollector();

        $cdata_factory = new XML_SimpleXMLCDATAFactory();

        $jira_field_mapper         = new JiraToTuleapFieldTypeMapper($error_collector, $logger);
        $report_table_exporter     = new XmlReportTableExporter();
        $default_criteria_exporter = new XmlReportDefaultCriteriaExporter();

        $tql_report_exporter = new XmlTQLReportExporter(
            $default_criteria_exporter,
            $cdata_factory,
            $report_table_exporter
        );

        $creation_state_list_value_formatter = new CreationStateListValueFormatter();
        $user_manager                        = UserManager::instance();
        $forge_user                          = $user_manager->getUserById(TrackerImporterUser::ID);

        if ($forge_user === null) {
            throw new \RuntimeException("Unable to find TrackerImporterUser");
        }

        $jira_user_retriever = new JiraUserRetriever(
            $logger,
            $user_manager,
            $jira_user_on_tuleap_cache,
            new JiraUserInfoQuerier(
                $wrapper,
                $logger
            ),
            $forge_user
        );

        if ($wrapper->isJiraCloud()) {
            $changelog_entries_builder = new JiraCloudChangelogEntriesBuilder($wrapper, $logger);
            $comment_value_builder     = new JiraCloudCommentValuesBuilder($wrapper, $logger);
        } else {
            $changelog_entries_builder = new JiraServerChangelogEntriesBuilder($wrapper, $logger);
            $comment_value_builder     = new JiraServerCommentValuesBuilder($wrapper, $logger);
        }

        return new self(
            $logger,
            $wrapper,
            $error_collector,
            new JiraFieldRetriever($wrapper, $logger),
            $jira_field_mapper,
            $jira_user_retriever,
            new XmlReportExporter(),
            new ArtifactsXMLExporter(
                $wrapper,
                $user_manager,
                new DataChangesetXMLExporter(
                    new XML_SimpleXMLCDATAFactory(),
                    new FieldChangeXMLExporter(
                        $logger,
                        new FieldChangeDateBuilder(
                            new XML_SimpleXMLCDATAFactory()
                        ),
                        new FieldChangeStringBuilder(
                            new XML_SimpleXMLCDATAFactory()
                        ),
                        new FieldChangeTextBuilder(
                            new XML_SimpleXMLCDATAFactory()
                        ),
                        new FieldChangeFloatBuilder(
                            new XML_SimpleXMLCDATAFactory()
                        ),
                        new FieldChangeListBuilder(
                            new XML_SimpleXMLCDATAFactory(),
                            UserXMLExporter::build()
                        ),
                        new FieldChangeFileBuilder(),
                        new FieldChangeArtifactLinksBuilder(
                            new XML_SimpleXMLCDATAFactory(),
                        ),
                        new TypePresenterFactory(
                            new TypeDao(),
                            new ArtifactLinksUsageDao()
                        ),
                    ),
                    new IssueSnapshotCollectionBuilder(
                        $changelog_entries_builder,
                        new CurrentSnapshotBuilder(
                            $logger,
                            $creation_state_list_value_formatter,
                            $jira_user_retriever
                        ),
                        new InitialSnapshotBuilder(
                            $logger,
                            new ListFieldChangeInitialValueRetriever(
                                $creation_state_list_value_formatter,
                                $jira_user_retriever
                            )
                        ),
                        new ChangelogSnapshotBuilder(
                            $creation_state_list_value_formatter,
                            $logger,
                            $jira_user_retriever
                        ),
                        $comment_value_builder,
                        $logger,
                        $jira_user_retriever
                    ),
                    new CommentXMLExporter(
                        new XML_SimpleXMLCDATAFactory(),
                        new CommentXMLValueEnhancer()
                    ),
                    $logger
                ),
                new AttachmentCollectionBuilder(),
                new AttachmentXMLExporter(
                    AttachmentDownloader::build($wrapper, $logger),
                    new XML_SimpleXMLCDATAFactory()
                ),
                $logger
            ),
            new SemanticsXMLExporter(),
            new AlwaysThereFieldsExporter(),
            new StoryPointFieldExporter(
                $logger,
            ),
            new XmlReportAllIssuesExporter(
                $default_criteria_exporter,
                $report_table_exporter
            ),
            new XmlReportOpenIssuesExporter(
                $default_criteria_exporter,
                $report_table_exporter,
            ),
            new XmlReportDoneIssuesExporter(
                $default_criteria_exporter,
                $report_table_exporter,
            ),
            new XmlReportCreatedRecentlyExporter($tql_report_exporter),
            new XmlReportUpdatedRecentlyExporter($tql_report_exporter),
            EventManager::instance()
        );
    }

    /**
     * @throws JiraConnectionException
     */
    public function exportJiraToXml(
        PlatformConfiguration $jira_platform_configuration,
        XMLTracker $xml_tracker,
        string $jira_base_url,
        string $jira_project_key,
        IssueType $issue_type,
        IDGenerator $field_id_generator,
        LinkedIssuesCollection $linked_issues_collection,
    ): \SimpleXMLElement {
        $this->logger->debug("Start export Jira to XML: " . $issue_type->getId());

        $jira_field_mapping_collection = new FieldMappingCollection();
        $status_values_collection      = new StatusValuesCollection(
            $this->wrapper,
            $this->logger
        );

        $this->logger->debug("Handle status");
        $status_values_collection->initCollectionForProjectAndIssueType(
            $jira_project_key,
            $issue_type->getId(),
            $field_id_generator,
        );

        $xml_tracker = $this->always_there_fields_exporter->exportFields(
            $xml_tracker,
            $status_values_collection,
            $jira_field_mapping_collection,
        );

        $this->logger->debug("Export Story Points field");
        $xml_tracker = $this->story_point_field_exporter->exportFields(
            $jira_platform_configuration,
            $xml_tracker,
            $jira_field_mapping_collection,
            $issue_type,
            $field_id_generator,
        );

        $this->logger->debug("Export custom jira fields");
        $xml_tracker = $this->exportJiraField(
            $xml_tracker,
            $field_id_generator,
            $jira_project_key,
            $issue_type,
            $jira_platform_configuration,
            $jira_field_mapping_collection,
        );

        $this->logger->debug("Export semantics");
        $tracker_for_semantic_xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $this->semantics_xml_exporter->exportSemantics(
            $tracker_for_semantic_xml,
            $jira_field_mapping_collection,
            $status_values_collection,
        );


        $this->logger->debug("Export reports");
        $tracker_for_reports_xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $this->report_exporter->exportReports(
            $tracker_for_reports_xml,
            $jira_field_mapping_collection,
            $status_values_collection,
            $this->xml_report_all_issues_exporter,
            $this->xml_report_open_issues_exporter,
            $this->xml_report_done_issues_exporter,
            $this->xml_report_created_recently_exporter,
            $this->xml_report_updated_recently_exporter
        );

        $node_tracker = $this->getXMLNode($xml_tracker, $tracker_for_semantic_xml, $tracker_for_reports_xml);

        $this->logger->debug("Export artifact");
        $issue_representation_collection = new IssueAPIRepresentationCollection();
        $this->artifacts_xml_exporter->exportArtifacts(
            $node_tracker,
            $jira_field_mapping_collection,
            $issue_representation_collection,
            $linked_issues_collection,
            $jira_base_url,
            $jira_project_key,
            $issue_type->getId(),
        );

        $this->event_manager->dispatch(
            new JiraImporterExternalPluginsEvent(
                $node_tracker,
                $jira_platform_configuration,
                $issue_representation_collection,
                $this->jira_user_retriever,
                $this->wrapper,
                $this->logger,
                $jira_field_mapping_collection
            )
        );

        if ($this->error_collector->hasError()) {
            foreach ($this->error_collector->getErrors() as $error) {
                $this->logger->error($error);
            }
        }

        return $node_tracker;
    }

    private function getXMLNode(XMLTracker $xml_tracker, \SimpleXMLElement $tracker_for_semantic_xml, \SimpleXMLElement $tracker_for_reports_xml): \SimpleXMLElement
    {
        $xml          = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><project />');
        $trackers_xml = $xml->addChild('trackers');
        $node_tracker = $xml_tracker->export($trackers_xml);

        $dom_tracker = dom_import_simplexml($node_tracker);
        if (! $dom_tracker) {
            throw new \RuntimeException('Impossible to create DOMElement from tracker');
        }
        $permissions_tags = $dom_tracker->getElementsByTagName('permissions');
        if (count($permissions_tags) !== 1) {
            throw new \LogicException('There must be only one permission node');
        }
        $dom_tracker->insertBefore($this->getNodeToInsert($tracker_for_semantic_xml, '/tracker/semantics', $dom_tracker), $permissions_tags[0]);
        $dom_tracker->insertBefore($this->getNodeToInsert($tracker_for_reports_xml, '/tracker/reports', $dom_tracker), $permissions_tags[0]);

        return $node_tracker;
    }

    private function getNodeToInsert(\SimpleXMLElement $xml_tracker, string $path, \DOMElement $dom_tracker): \DOMNode
    {
        $requested_node = $xml_tracker->xpath($path);
        if (count($requested_node) !== 1) {
            throw new \LogicException('there should be only one ' . $path . ' in tracker XML');
        }
        $dom_node = dom_import_simplexml($requested_node[0]);
        if (! $dom_node) {
            throw new \RuntimeException('Impossible to convert ' . $path . ' node to DOM');
        }
        if (! $dom_tracker->ownerDocument) {
            throw new \RuntimeException('No ownerDocument on DOM tracker');
        }
        return $dom_tracker->ownerDocument->importNode($dom_node, true);
    }

    public function getProjectSimpleXmlElement(\SimpleXMLElement $tracker_xml): \SimpleXMLElement
    {
        $project_xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><project><trackers></trackers></project>');

        $this->appendTrackerXML($project_xml->trackers, $tracker_xml);

        return $project_xml;
    }

    public function appendTrackerXML(\SimpleXMLElement $all_trackers_node, \SimpleXMLElement $one_tracker_node): void
    {
        $dom_trackers = dom_import_simplexml($all_trackers_node);
        if (! $dom_trackers) {
            throw new \Exception('Cannot get DOM from trackers XML');
        }
        $dom_tracker = dom_import_simplexml($one_tracker_node);
        if (! $dom_tracker) {
            throw new \Exception('Cannot get DOM from tracker XML');
        }
        if (! $dom_trackers->ownerDocument) {
            throw new \Exception('No ownerDocument node in trackers');
        }

        $dom_tracker = $dom_trackers->ownerDocument->importNode($dom_tracker, true);
        $dom_trackers->appendChild($dom_tracker);
    }

    private function exportJiraField(
        XMLTracker $xml_tracker,
        IDGenerator $id_generator,
        string $jira_project_id,
        IssueType $issue_type,
        PlatformConfiguration $platform_configuration,
        FieldMappingCollection $field_mapping_collection,
    ): XMLTracker {
        $this->logger->debug("Start exporting jira field structure (custom fields) ...");
        $fields = $this->jira_field_retriever->getAllJiraFields($jira_project_id, $issue_type->getId(), $id_generator);
        foreach ($fields as $key => $field) {
            $xml_tracker = $this->field_type_mapper->exportFieldToXml(
                $field,
                $xml_tracker,
                $id_generator,
                $platform_configuration,
                $field_mapping_collection,
            );
        }
        $this->logger->debug("Field structure exported successfully");
        return $xml_tracker;
    }
}
