<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

use Tuleap\AgileDashboard\AgileDashboardLegacyController;
use Tuleap\AgileDashboard\Artifact\AdditionalArtifactActionBuilder;
use Tuleap\AgileDashboard\Artifact\EventRedirectAfterArtifactCreationOrUpdateHandler;
use Tuleap\AgileDashboard\Artifact\HomeServiceRedirectionExtractor;
use Tuleap\AgileDashboard\Artifact\PlannedArtifactDao;
use Tuleap\AgileDashboard\Artifact\RedirectParameterInjector;
use Tuleap\AgileDashboard\BreadCrumbDropdown\AgileDashboardCrumbBuilder;
use Tuleap\AgileDashboard\BreadCrumbDropdown\MilestoneCrumbBuilder;
use Tuleap\AgileDashboard\BreadCrumbDropdown\VirtualTopMilestoneCrumbBuilder;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\AgileDashboard\ExplicitBacklog\ConfigurationUpdater;
use Tuleap\AgileDashboard\ExplicitBacklog\CreateTrackerFromXMLChecker;
use Tuleap\AgileDashboard\ExplicitBacklog\DirectArtifactLinkCleaner;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\AgileDashboard\ExplicitBacklog\ProjectNotUsingExplicitBacklogException;
use Tuleap\AgileDashboard\ExplicitBacklog\UnplannedArtifactsAdder;
use Tuleap\AgileDashboard\ExplicitBacklog\UnplannedCriterionOptionsProvider;
use Tuleap\AgileDashboard\ExplicitBacklog\UnplannedReportCriterionChecker;
use Tuleap\AgileDashboard\ExplicitBacklog\UnplannedReportCriterionMatchingIdsRetriever;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsCacheDao;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsCalculator;
use Tuleap\AgileDashboard\FormElement\Burnup\ProjectsCountModeDao;
use Tuleap\AgileDashboard\FormElement\BurnupCacheDao;
use Tuleap\AgileDashboard\FormElement\BurnupCacheDateRetriever;
use Tuleap\AgileDashboard\FormElement\BurnupCalculator;
use Tuleap\AgileDashboard\FormElement\BurnupDao;
use Tuleap\AgileDashboard\FormElement\BurnupFieldRetriever;
use Tuleap\AgileDashboard\FormElement\MessageFetcher;
use Tuleap\AgileDashboard\FormElement\SystemEvent\SystemEvent_BURNUP_DAILY;
use Tuleap\AgileDashboard\FormElement\SystemEvent\SystemEvent_BURNUP_GENERATE;
use Tuleap\AgileDashboard\Kanban\KanbanURL;
use Tuleap\AgileDashboard\Kanban\KanbanXmlImporter;
use Tuleap\AgileDashboard\Kanban\RealTime\KanbanArtifactMessageBuilder;
use Tuleap\AgileDashboard\Kanban\RealTime\KanbanArtifactMessageSender;
use Tuleap\AgileDashboard\Kanban\RecentlyVisited\RecentlyVisitedKanbanDao;
use Tuleap\AgileDashboard\Kanban\RecentlyVisited\VisitRetriever;
use Tuleap\AgileDashboard\Kanban\TrackerReport\TrackerReportDao;
use Tuleap\AgileDashboard\Kanban\TrackerReport\TrackerReportUpdater;
use Tuleap\AgileDashboard\KanbanJavascriptDependenciesProvider;
use Tuleap\AgileDashboard\Masschange\AdditionalMasschangeActionProcessor;
use Tuleap\AgileDashboard\Milestone\AllBreadCrumbsForMilestoneBuilder;
use Tuleap\AgileDashboard\Milestone\Pane\Details\DetailsPaneInfo;
use Tuleap\AgileDashboard\MonoMilestone\MonoMilestoneBacklogItemDao;
use Tuleap\AgileDashboard\MonoMilestone\MonoMilestoneItemsFinder;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneDao;
use Tuleap\AgileDashboard\Planning\PlanningJavascriptDependenciesProvider;
use Tuleap\AgileDashboard\Planning\PlanningTrackerBacklogChecker;
use Tuleap\AgileDashboard\Planning\XML\ProvideCurrentUserForXMLImport;
use Tuleap\AgileDashboard\RealTime\RealTimeArtifactMessageController;
use Tuleap\AgileDashboard\RemainingEffortValueRetriever;
use Tuleap\AgileDashboard\Semantic\MoveChangesetXMLUpdater;
use Tuleap\AgileDashboard\Semantic\MoveSemanticInitialEffortChecker;
use Tuleap\AgileDashboard\Semantic\XML\SemanticsExporter;
use Tuleap\AgileDashboard\Widget\MyKanban;
use Tuleap\AgileDashboard\Widget\ProjectKanban;
use Tuleap\AgileDashboard\Widget\WidgetKanbanConfigDAO;
use Tuleap\AgileDashboard\Widget\WidgetKanbanConfigRetriever;
use Tuleap\AgileDashboard\Widget\WidgetKanbanConfigUpdater;
use Tuleap\AgileDashboard\Widget\WidgetKanbanCreator;
use Tuleap\AgileDashboard\Widget\WidgetKanbanDao;
use Tuleap\AgileDashboard\Widget\WidgetKanbanDeletor;
use Tuleap\AgileDashboard\Widget\WidgetKanbanRetriever;
use Tuleap\AgileDashboard\Workflow\AddToTopBacklog;
use Tuleap\AgileDashboard\Workflow\AddToTopBacklogPostActionDao;
use Tuleap\AgileDashboard\Workflow\AddToTopBacklogPostActionFactory;
use Tuleap\AgileDashboard\Workflow\PostAction\Update\AddToTopBacklogValue;
use Tuleap\AgileDashboard\Workflow\PostAction\Update\Internal\AddToTopBacklogValueRepository;
use Tuleap\AgileDashboard\Workflow\PostAction\Update\Internal\AddToTopBacklogValueUpdater;
use Tuleap\AgileDashboard\Workflow\REST\v1\AddToTopBacklogJsonParser;
use Tuleap\AgileDashboard\Workflow\REST\v1\AddToTopBacklogRepresentation;
use Tuleap\Cardwall\Agiledashboard\CardwallPaneInfo;
use Tuleap\CLI\Events\GetWhitelistedKeys;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Http\HttpClientFactory;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Layout\HomePage\StatisticsCollectionCollector;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\JavascriptAsset;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupDisplayEvent;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupPaneCollector;
use Tuleap\Project\Event\ProjectXMLImportPreChecksEvent;
use Tuleap\Project\Registration\RegisterProjectCreationEvent;
use Tuleap\Project\XML\Import\ImportNotValidException;
use Tuleap\Project\XML\ServiceEnableForXmlImportRetriever;
use Tuleap\RealTime\NodeJSClient;
use Tuleap\Tracker\Artifact\ActionButtons\AdditionalArtifactActionButtonsFetcher;
use Tuleap\Tracker\Artifact\ActionButtons\MoveArtifactActionAllowedByPluginRetriever;
use Tuleap\Tracker\Artifact\Event\ArtifactCreated;
use Tuleap\Tracker\Artifact\Event\ArtifactDeleted;
use Tuleap\Tracker\Artifact\Event\ArtifactsReordered;
use Tuleap\Tracker\Artifact\Event\ArtifactUpdated;
use Tuleap\Tracker\Artifact\RecentlyVisited\HistoryQuickLinkCollection;
use Tuleap\Tracker\Artifact\RecentlyVisited\RecentlyVisitedDao;
use Tuleap\Tracker\Artifact\RecentlyVisited\VisitRecorder;
use Tuleap\Tracker\Artifact\RedirectAfterArtifactCreationOrUpdateEvent;
use Tuleap\Tracker\Artifact\Renderer\BuildArtifactFormActionEvent;
use Tuleap\Tracker\CreateTrackerFromXMLEvent;
use Tuleap\Tracker\Creation\DefaultTemplatesXMLFileCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\JiraImporterExternalPluginsEvent;
use Tuleap\Tracker\Events\MoveArtifactGetExternalSemanticCheckers;
use Tuleap\Tracker\Events\MoveArtifactParseFieldChangeNodes;
use Tuleap\Tracker\FormElement\Event\MessageFetcherAdditionalWarnings;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStaticValueDao;
use Tuleap\Tracker\FormElement\Field\ListFields\FieldValueMatcher;
use Tuleap\Tracker\Masschange\TrackerMasschangeGetExternalActionsEvent;
use Tuleap\Tracker\Masschange\TrackerMasschangeProcessExternalActionsEvent;
use Tuleap\Tracker\RealTime\RealTimeArtifactMessageSender;
use Tuleap\Tracker\Report\Event\TrackerReportDeleted;
use Tuleap\Tracker\Report\Event\TrackerReportProcessAdditionalQuery;
use Tuleap\Tracker\Report\Event\TrackerReportSetToPrivate;
use Tuleap\Tracker\REST\v1\Event\GetExternalPostActionJsonParserEvent;
use Tuleap\Tracker\REST\v1\Event\PostActionVisitExternalActionsEvent;
use Tuleap\Tracker\REST\v1\Workflow\PostAction\CheckPostActionsForTracker;
use Tuleap\Tracker\Semantic\Progress\Events\GetSemanticProgressUsageEvent;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDoneDao;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDoneFactory;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDoneUsedExternalService;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDoneUsedExternalServiceEvent;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDoneValueChecker;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\TrackerCrumbInContext;
use Tuleap\Tracker\Workflow\Event\GetWorkflowExternalPostActionsValuesForUpdate;
use Tuleap\Tracker\Workflow\Event\GetWorkflowExternalPostActionsValueUpdater;
use Tuleap\Tracker\Workflow\Event\TransitionDeletionEvent;
use Tuleap\Tracker\Workflow\Event\WorkflowDeletionEvent;
use Tuleap\Tracker\Workflow\PostAction\ExternalPostActionSaveObjectEvent;
use Tuleap\Tracker\Workflow\PostAction\GetExternalPostActionPluginsEvent;
use Tuleap\Tracker\Workflow\PostAction\GetExternalSubFactoriesEvent;
use Tuleap\Tracker\Workflow\PostAction\GetExternalSubFactoryByNameEvent;
use Tuleap\Tracker\Workflow\PostAction\GetPostActionShortNameFromXmlTagNameEvent;
use Tuleap\Tracker\XML\Importer\ImportXMLProjectTrackerDone;
use Tuleap\User\History\HistoryEntryCollection;
use Tuleap\User\History\HistoryQuickLink;
use Tuleap\User\History\HistoryRetriever;
use Tuleap\User\ProvideCurrentUser;

require_once __DIR__ . '/../../tracker/include/trackerPlugin.php';
require_once __DIR__ . '/../../cardwall/include/cardwallPlugin.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once 'constants.php';

/**
 * AgileDashboardPlugin
 */
class AgileDashboardPlugin extends Plugin  // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    public const PLUGIN_NAME      = 'agiledashboard';
    public const PLUGIN_SHORTNAME = 'plugin_agiledashboard';

    /** @var AgileDashboard_SequenceIdManager */
    private $sequence_id_manager;

    /**
     * @var AddToTopBacklogPostActionFactory
     */
    private $add_to_top_backlog_post_action_factory;

    /**
     * Plugin constructor
     */
    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
        bindTextDomain('tuleap-agiledashboard', AGILEDASHBOARD_BASE_DIR . '/../site-content');
    }

    public function getHooksAndCallbacks()
    {
        // Do not load the plugin if tracker is not installed & active
        if (defined('TRACKER_BASE_URL')) {
            $this->addHook('cssfile', 'cssfile', false);
            $this->addHook('javascript_file');
            $this->addHook(\Tuleap\Widget\Event\GetWidget::NAME);
            $this->addHook(\Tuleap\Widget\Event\GetUserWidgetList::NAME);
            $this->addHook(\Tuleap\Widget\Event\GetProjectWidgetList::NAME);
            $this->addHook(\Tuleap\Widget\Event\ConfigureAtXMLImport::NAME);
            $this->addHook(TRACKER_EVENT_INCLUDE_CSS_FILE);
            $this->addHook(TRACKER_EVENT_TRACKERS_DUPLICATED, 'tracker_event_trackers_duplicated', false);
            $this->addHook(BuildArtifactFormActionEvent::NAME);
            $this->addHook(TRACKER_EVENT_ARTIFACT_ASSOCIATION_EDITED, 'tracker_event_artifact_association_edited', false);
            $this->addHook(RedirectAfterArtifactCreationOrUpdateEvent::NAME);
            $this->addHook(TRACKER_EVENT_MANAGE_SEMANTICS, 'tracker_event_manage_semantics', false);
            $this->addHook(TRACKER_EVENT_SEMANTIC_FROM_XML, 'tracker_event_semantic_from_xml');
            $this->addHook(TRACKER_EVENT_GET_SEMANTICS_NAMES, 'tracker_event_get_semantics_names');
            $this->addHook(TRACKER_EVENT_GET_SEMANTIC_DUPLICATORS);
            $this->addHook('plugin_statistics_service_usage');
            $this->addHook(TRACKER_EVENT_REPORT_DISPLAY_ADDITIONAL_CRITERIA);
            $this->addHook(TRACKER_EVENT_REPORT_SAVE_ADDITIONAL_CRITERIA);
            $this->addHook(TRACKER_EVENT_REPORT_LOAD_ADDITIONAL_CRITERIA);
            $this->addHook(TRACKER_EVENT_FIELD_AUGMENT_DATA_FOR_REPORT);
            $this->addHook(TRACKER_USAGE);
            $this->addHook(Event::SERVICE_CLASSNAMES);
            $this->addHook(Event::SERVICES_ALLOWED_FOR_PROJECT);
            $this->addHook(Event::SERVICE_IS_USED);
            $this->addHook(RegisterProjectCreationEvent::NAME);
            $this->addHook(TRACKER_EVENT_PROJECT_CREATION_TRACKERS_REQUIRED);
            $this->addHook(TRACKER_EVENT_GENERAL_SETTINGS);
            $this->addHook(Event::IMPORT_XML_PROJECT_CARDWALL_DONE);
            $this->addHook(Event::REST_RESOURCES);
            $this->addHook(Event::REST_PROJECT_ADDITIONAL_INFORMATIONS);
            $this->addHook(Event::REST_PROJECT_RESOURCES);
            $this->addHook(Event::GET_PROJECTID_FROM_URL);
            $this->addHook(Event::COLLECT_ERRORS_WITHOUT_IMPORTING_XML_PROJECT);
            $this->addHook(ITEM_PRIORITY_CHANGE);
            $this->addHook(Tracker_Artifact_EditRenderer::EVENT_ADD_VIEW_IN_COLLECTION);
            $this->addHook(Event::BURNING_PARROT_GET_STYLESHEETS);
            $this->addHook(Event::BURNING_PARROT_GET_JAVASCRIPT_FILES);
            $this->addHook(PermissionPerGroupDisplayEvent::NAME);
            $this->addHook(HistoryEntryCollection::NAME);
            $this->addHook(Event::USER_HISTORY_CLEAR);
            $this->addHook(ArtifactCreated::NAME);
            $this->addHook(ArtifactsReordered::NAME);
            $this->addHook(ArtifactUpdated::NAME);
            $this->addHook(TrackerReportDeleted::NAME);
            $this->addHook(TrackerReportSetToPrivate::NAME);
            $this->addHook(Tracker_FormElementFactory::GET_CLASSNAMES);
            $this->addHook(Event::GET_SYSTEM_EVENT_CLASS);
            $this->addHook('codendi_daily_start');
            $this->addHook(Event::SYSTEM_EVENT_GET_TYPES_FOR_DEFAULT_QUEUE);
            $this->addHook(MessageFetcherAdditionalWarnings::NAME);
            $this->addHook(ImportXMLProjectTrackerDone::NAME);
            $this->addHook(PermissionPerGroupPaneCollector::NAME);
            $this->addHook(ArtifactDeleted::NAME);
            $this->addHook(MoveArtifactGetExternalSemanticCheckers::NAME);
            $this->addHook(MoveArtifactParseFieldChangeNodes::NAME);
            $this->addHook(MoveArtifactActionAllowedByPluginRetriever::NAME);
            $this->addHook(\Tuleap\Request\CollectRoutesEvent::NAME);
            $this->addHook(TrackerCrumbInContext::NAME);
            $this->addHook(HistoryQuickLinkCollection::NAME);
            $this->addHook(StatisticsCollectionCollector::NAME);
            $this->addHook('project_is_deleted');
            $this->addHook(AdditionalArtifactActionButtonsFetcher::NAME);
            $this->addHook(TrackerMasschangeGetExternalActionsEvent::NAME);
            $this->addHook(TrackerMasschangeProcessExternalActionsEvent::NAME);
            $this->addHook(ServiceEnableForXmlImportRetriever::NAME);
            $this->addHook(TrackerReportProcessAdditionalQuery::NAME);
            $this->addHook(GetExternalSubFactoriesEvent::NAME);
            $this->addHook(WorkflowDeletionEvent::NAME);
            $this->addHook(TransitionDeletionEvent::NAME);
            $this->addHook(PostActionVisitExternalActionsEvent::NAME);
            $this->addHook(GetExternalPostActionJsonParserEvent::NAME);
            $this->addHook(GetWorkflowExternalPostActionsValueUpdater::NAME);
            $this->addHook(GetExternalSubFactoryByNameEvent::NAME);
            $this->addHook(ExternalPostActionSaveObjectEvent::NAME);
            $this->addHook(GetPostActionShortNameFromXmlTagNameEvent::NAME);
            $this->addHook(CreateTrackerFromXMLEvent::NAME);
            $this->addHook(ProjectXMLImportPreChecksEvent::NAME);
            $this->addHook(GetExternalPostActionPluginsEvent::NAME);
            $this->addHook(CheckPostActionsForTracker::NAME);
            $this->addHook(GetWorkflowExternalPostActionsValuesForUpdate::NAME);
            $this->addHook(DefaultTemplatesXMLFileCollection::NAME);
            $this->addHook(GetWhitelistedKeys::NAME);
            $this->addHook(JiraImporterExternalPluginsEvent::NAME);
            $this->addHook(GetSemanticProgressUsageEvent::NAME);
            $this->addHook(SemanticDoneUsedExternalServiceEvent::NAME);
        }

        if (defined('CARDWALL_BASE_URL')) {
            $this->addHook(CARDWALL_EVENT_USE_STANDARD_JAVASCRIPT, 'cardwall_event_use_standard_javascript');
        }

        if (defined('TESTMANAGEMENT_BASE_URL')) {
            $this->addHook(\Tuleap\TestManagement\Event\GetMilestone::NAME);
            $this->addHook(\Tuleap\TestManagement\Event\GetItemsFromMilestone::NAME);
        }

        return parent::getHooksAndCallbacks();
    }

    public function getHistoryEntryCollection(HistoryEntryCollection $collection): void
    {
        $visit_retriever = new VisitRetriever(
            new RecentlyVisitedKanbanDao(),
            $this->getKanbanFactory(),
            TrackerFactory::instance()
        );
        $visit_retriever->getVisitHistory($collection, HistoryRetriever::MAX_LENGTH_HISTORY);
    }

    /**
     * @see Event::USER_HISTORY_CLEAR
     */
    public function userHistoryClear(array $params): void
    {
        $user = $params['user'];
        assert($user instanceof PFUser);

        $visit_cleaner = new RecentlyVisitedKanbanDao();
        $visit_cleaner->deleteVisitByUserId((int) $user->getId());
    }

    public function tracker_formelement_get_classnames($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $params['dynamic']['burnup'] = \Tuleap\AgileDashboard\FormElement\Burnup::class;
    }

    /**
     * @see Plugin::getDependencies()
     */
    public function getDependencies()
    {
        return ['tracker', 'cardwall'];
    }

    public function getServiceShortname()
    {
        return self::PLUGIN_SHORTNAME;
    }

    public function service_classnames(&$params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $params['classnames'][$this->getServiceShortname()] = \Tuleap\AgileDashboard\AgileDashboardService::class;
    }

    public function registerProjectCreationEvent(RegisterProjectCreationEvent $event): void
    {
        if ($event->shouldProjectInheritFromTemplate()) {
            $this->getConfigurationManager()->duplicate(
                (int) $event->getJustCreatedProject()->getID(),
                (int) $event->getTemplateProject()->getID(),
            );

            $explicit_backlog_configuration_updater = $this->getExplicitBacklogConfigurationUpdater();
            $explicit_backlog_configuration_updater->activateExplicitBacklogManagement(
                $event->getJustCreatedProject(),
                UserManager::instance()->getCurrentUser()
            );

            (new ProjectsCountModeDao())->inheritBurnupCountMode(
                (int) $event->getTemplateProject()->getID(),
                (int) $event->getJustCreatedProject()->getID(),
            );
        }
    }

    public function collect_errors_without_importing_xml_project($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $is_mono_milestone_enabled = $this->getMonoMilestoneChecker()->isMonoMilestoneEnabled(
            $params['project']->getId()
        );

        if ($is_mono_milestone_enabled && count($params['xml_content']->agiledashboard->plannings->planning) > 1) {
            $params['errors'] = dgettext('tuleap-agiledashboard', 'You cannot import more than one planning in scrum V2, please check your XML.');
        }
    }

    /**
     * @return AgileDashboard_ConfigurationManager
     */
    private function getConfigurationManager()
    {
        return new AgileDashboard_ConfigurationManager(
            new AgileDashboard_ConfigurationDao(),
            EventManager::instance()
        );
    }

    public function cardwall_event_get_swimline_tracker($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $planning_factory = $this->getPlanningFactory();
        if ($planning = $planning_factory->getPlanningByPlanningTracker($params['tracker'])) {
            $params['backlog_trackers'] = $planning->getBacklogTrackers();
        }
    }

    /**
     * @see TRACKER_EVENT_REPORT_DISPLAY_ADDITIONAL_CRITERIA
     */
    public function tracker_event_report_display_additional_criteria($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $backlog_tracker = $params['tracker'];
        if (! $backlog_tracker) {
            return;
        }

        $planning_factory     = $this->getPlanningFactory();
        $user                 = $this->getCurrentUser();
        $provider             = new AgileDashboard_Milestone_MilestoneReportCriterionProvider(
            new AgileDashboard_Milestone_SelectedMilestoneProvider(
                $params['additional_criteria'],
                $this->getMilestoneFactory(),
                $user,
                $backlog_tracker->getProject()
            ),
            new AgileDashboard_Milestone_MilestoneReportCriterionOptionsProvider(
                new AgileDashboard_Planning_NearestPlanningTrackerProvider($planning_factory),
                $this->getMilestoneDao(),
                Tracker_HierarchyFactory::instance(),
                $planning_factory,
                TrackerFactory::instance(),
                Tracker_ArtifactFactory::instance()
            ),
            new UnplannedCriterionOptionsProvider(new ExplicitBacklogDao()),
            new UnplannedReportCriterionChecker($params['additional_criteria'])
        );
        $additional_criterion = $provider->getCriterion($backlog_tracker, $user);

        if (! $additional_criterion) {
            return;
        }

        $params['array_of_html_criteria'][] = $additional_criterion;
    }

    public function trackerReportProcessAdditionalQuery(TrackerReportProcessAdditionalQuery $event)
    {
        $backlog_tracker = $event->getTracker();

        $user    = $event->getUser();
        $project = $backlog_tracker->getProject();

        $unplanned_report_criterion_checker = new UnplannedReportCriterionChecker($event->getAdditionalCriteria());
        if ($unplanned_report_criterion_checker->isUnplannedValueSelected()) {
            $retriever = new UnplannedReportCriterionMatchingIdsRetriever(
                new ExplicitBacklogDao(),
                new ArtifactsInExplicitBacklogDao(),
                new PlannedArtifactDao(),
                $this->getArtifactFactory()
            );

            try {
                $event->addResult($retriever->getMatchingIds($backlog_tracker, $user));
                $event->setSearchIsPerformed();
            } catch (ProjectNotUsingExplicitBacklogException $exception) {
                //Do nothing
            } finally {
                return;
            }
        }

        $milestone_provider = new AgileDashboard_Milestone_SelectedMilestoneProvider($event->getAdditionalCriteria(), $this->getMilestoneFactory(), $user, $project);
        $milestone          = $milestone_provider->getMilestone();

        if ($milestone) {
            $provider = new AgileDashboard_BacklogItem_SubBacklogItemProvider(
                new Tracker_ArtifactDao(),
                $this->getBacklogFactory(),
                $this->getBacklogItemCollectionFactory(
                    $this->getMilestoneFactory(),
                    new AgileDashboard_Milestone_Backlog_BacklogItemBuilder()
                ),
                $this->getPlanningFactory(),
                new ExplicitBacklogDao(),
                new ArtifactsInExplicitBacklogDao()
            );

            $event->addResult($provider->getMatchingIds($milestone, $backlog_tracker, $user));
            $event->setSearchIsPerformed();
        }
    }

    /**
     * @see TRACKER_EVENT_REPORT_SAVE_ADDITIONAL_CRITERIA
     */
    public function tracker_event_report_save_additional_criteria($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $dao     = new MilestoneReportCriterionDao();
        $project = $params['report']->getTracker()->getProject();
        $user    = $this->getCurrentUser();

        $unplanned_report_criterion_checker = new UnplannedReportCriterionChecker($params['additional_criteria']);
        if ($unplanned_report_criterion_checker->isUnplannedValueSelected()) {
            $dao->save($params['report']->getId(), AgileDashboard_Milestone_MilestoneReportCriterionProvider::UNPLANNED);
            return;
        }

        $milestone_provider = new AgileDashboard_Milestone_SelectedMilestoneProvider($params['additional_criteria'], $this->getMilestoneFactory(), $user, $project);
        if ($milestone_provider->getMilestone()) {
            $dao->save($params['report']->getId(), $milestone_provider->getMilestoneId());
        } else {
            $dao->delete($params['report']->getId());
        }
    }

    /**
     * @see TRACKER_EVENT_REPORT_LOAD_ADDITIONAL_CRITERIA
     */
    public function tracker_event_report_load_additional_criteria($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $dao        = new MilestoneReportCriterionDao();
        $report_id  = $params['report']->getId();
        $field_name = AgileDashboard_Milestone_MilestoneReportCriterionProvider::FIELD_NAME;

        $row = $dao->searchByReportId($report_id)->getRow();
        if ($row) {
            $params['additional_criteria_values'][$field_name]['value'] = $row['milestone_id'];
        }
    }

    public function tracker_event_include_css_file($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $request = HTTPRequest::instance();
        if ($request->get('pane') === CardwallPaneInfo::IDENTIFIER || $this->isHomepageURL($request)) {
            $params['include_tracker_css_file'] = true;
        }
    }

    public function tracker_event_general_settings($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $hierarchyChecker                                        = new AgileDashboard_HierarchyChecker(
            $this->getPlanningFactory(),
            $this->getKanbanFactory(),
            $this->getTrackerFactory()
        );
        $params['cannot_configure_instantiate_for_new_projects'] = $hierarchyChecker->isPartOfScrumOrKanbanHierarchy($params['tracker']);
    }

    public function tracker_event_project_creation_trackers_required($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $hierarchyChecker           = new AgileDashboard_HierarchyChecker(
            $this->getPlanningFactory(),
            $this->getKanbanFactory(),
            $this->getTrackerFactory()
        );
        $params['tracker_ids_list'] = array_merge(
            $params['tracker_ids_list'],
            $hierarchyChecker->getADTrackerIdsByProjectId($params['project_id'])
        );
    }

    public function tracker_event_trackers_duplicated($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        PlanningFactory::build()->duplicatePlannings(
            $params['group_id'],
            $params['tracker_mapping'],
            $params['ugroups_mapping']
        );

        $this->getKanbanManager()->duplicateKanbans(
            $params['tracker_mapping'],
            $params['field_mapping'],
            $params['report_mapping']
        );
    }

    public function redirectAfterArtifactCreationOrUpdateEvent(RedirectAfterArtifactCreationOrUpdateEvent $event): void
    {
        $planning_factory = PlanningFactory::build();
        $params_extractor = new AgileDashboard_PaneRedirectionExtractor();

        $processor = new EventRedirectAfterArtifactCreationOrUpdateHandler(
            $params_extractor,
            new HomeServiceRedirectionExtractor(),
            new Planning_ArtifactLinker($this->getArtifactFactory(), $planning_factory),
            $planning_factory,
            new RedirectParameterInjector(
                $params_extractor,
                Tracker_ArtifactFactory::instance(),
                $GLOBALS['Response'],
                $this->getTemplateRenderer()
            ),
            $this->getMilestoneFactory(),
            $this->getMilestonePaneFactory()
        );

        $processor->process($event->getRequest(), $event->getRedirect(), $event->getArtifact());
    }

    public function tracker_usage($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $tracker    = $params['tracker'];
        $tracker_id = $tracker->getId();

        $is_used_in_planning = PlanningFactory::build()->isTrackerIdUsedInAPlanning($tracker_id);
        $is_used_in_backlog  = PlanningFactory::build()->isTrackerUsedInBacklog($tracker_id);
        $is_used_in_kanban   = $this->getKanbanManager()->doesKanbanExistForTracker($tracker);

        if ($is_used_in_planning || $is_used_in_backlog || $is_used_in_kanban) {
            $result['can_be_deleted'] = false;
            $result['message']        = 'Agile Dashboard';
            $params['result']         = $result;
        }
    }

    public function getWhitelistedKeys(GetWhitelistedKeys $event): void
    {
        $event->addConfigClass(ScrumForMonoMilestoneChecker::class);
    }

    public function widgetInstance(\Tuleap\Widget\Event\GetWidget $event)
    {
        if ($event->getName() !== MyKanban::NAME && $event->getName() !== ProjectKanban::NAME) {
            return;
        }

        $widget_kanban_dao        = new WidgetKanbanDao();
        $widget_kanban_config_dao = new WidgetKanbanConfigDAO();
        $widget_kanban_creator    = new WidgetKanbanCreator(
            $widget_kanban_dao
        );
        $widget_kanban_retriever  = new WidgetKanbanRetriever(
            $widget_kanban_dao
        );
        $widget_kanban_deletor    = new WidgetKanbanDeletor(
            $widget_kanban_dao
        );

        $widget_config_retriever = new WidgetKanbanConfigRetriever(
            $widget_kanban_config_dao
        );

        $permission_manager = new AgileDashboard_PermissionsManager();
        $kanban_factory     = $this->getKanbanFactory();

        $widget_kanban_config_updater = new WidgetKanbanConfigUpdater(
            $widget_kanban_config_dao
        );

        switch ($event->getName()) {
            case MyKanban::NAME:
                $event->setWidget(
                    new MyKanban(
                        $widget_kanban_creator,
                        $widget_kanban_retriever,
                        $widget_kanban_deletor,
                        $kanban_factory,
                        TrackerFactory::instance(),
                        $permission_manager,
                        $widget_config_retriever,
                        $widget_kanban_config_updater,
                        Tracker_ReportFactory::instance(),
                        EventManager::instance()
                    )
                );
                break;
            case ProjectKanban::NAME:
                $event->setWidget(
                    new ProjectKanban(
                        $widget_kanban_creator,
                        $widget_kanban_retriever,
                        $widget_kanban_deletor,
                        $kanban_factory,
                        TrackerFactory::instance(),
                        $permission_manager,
                        $widget_config_retriever,
                        $widget_kanban_config_updater,
                        Tracker_ReportFactory::instance(),
                        EventManager::instance()
                    )
                );
                break;
        }
    }

    public function getUserWidgetList(\Tuleap\Widget\Event\GetUserWidgetList $event)
    {
        $event->addWidget(MyKanban::NAME);
    }

    public function getProjectWidgetList(\Tuleap\Widget\Event\GetProjectWidgetList $event)
    {
        $event->addWidget(ProjectKanban::NAME);
    }

    public function configureAtXMLImport(\Tuleap\Widget\Event\ConfigureAtXMLImport $event)
    {
        if ($event->getWidget()->getId() === ProjectKanban::NAME) {
            $xml_import = new \Tuleap\AgileDashboard\Widget\WidgetKanbanXMLImporter();
            $xml_import->configureWidget($event);
        }
    }

    public function buildArtifactFormActionEvent(BuildArtifactFormActionEvent $event): void
    {
        $request  = $event->getRequest();
        $redirect = $event->getRedirect();

        $home_service_redirection_extractor = new HomeServiceRedirectionExtractor();
        if ($home_service_redirection_extractor->mustRedirectToAgiledashboardHomepage($request)) {
            $redirect->query_parameters['agiledashboard'] = $request->get('agiledashboard');
            return;
        }

        $injector = new RedirectParameterInjector(
            new AgileDashboard_PaneRedirectionExtractor(),
            Tracker_ArtifactFactory::instance(),
            $GLOBALS['Response'],
            $this->getTemplateRenderer()
        );

        $injector->injectAndInformUserAboutBacklogItemWillBeLinked($request, $redirect);
    }

    /**
     * @return AgileDashboardPluginInfo
     */
    public function getPluginInfo()
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new AgileDashboardPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function cssfile($params)
    {
        if ($this->isAnAgiledashboardRequest()) {
            $css_file_url = $this->getIncludeAssets()->getFileURL('style-fp.css');
            echo '<link rel="stylesheet" type="text/css" href="' . $css_file_url . '" />';
        }
    }

    public function javascript_file(array $params): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($this->isAnAgiledashboardRequest()) {
            $layout = $params['layout'];
            assert($layout instanceof \Tuleap\Layout\BaseLayout);
            $layout->addJavascriptAsset(new JavascriptAsset($this->getIncludeAssets(), 'home-burndowns.js'));
        }
    }

    /** @see Event::BURNING_PARROT_GET_STYLESHEETS */
    public function burning_parrot_get_stylesheets(array $params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $request = HTTPRequest::instance();
        if (AgileDashboardLegacyController::isInOverviewTab($request) || AgileDashboardLegacyController::isPlanningV2URL($request)) {
            $params['stylesheets'][] = $this->getIncludeAssets()->getFileURL('scrum-style.css');
        } elseif (AgileDashboardLegacyController::isScrumAdminURL($request)) {
            $params['stylesheets'][] = $this->getIncludeAssets()->getFileURL('administration-style.css');
        }
    }

    public function burning_parrot_get_javascript_files(array $params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if (AgileDashboardLegacyController::isInOverviewTab(HTTPRequest::instance())) {
            $params['javascript_files'][] = $this->getIncludeAssets()->getFileURL('scrum-header.js');
            return;
        }

        $provider = $this->getJavascriptDependenciesProvider();
        if ($provider === null) {
            return;
        }

        foreach ($provider->getDependencies() as $javascript) {
            if (isset($javascript['snippet'])) {
                $GLOBALS['HTML']->includeFooterJavascriptSnippet($javascript['snippet']);
            } else {
                $params['javascript_files'][] = $javascript['file'];
            }
        }
    }

    public function permissionPerGroupDisplayEvent(PermissionPerGroupDisplayEvent $event)
    {
        $script = $this->getScriptAssetByName('permission-per-group.js');
        $event->addJavascript($script->getFileURL());
    }

    /**
     * @return \Tuleap\AgileDashboard\JavascriptDependenciesProvider
     */
    private function getJavascriptDependenciesProvider()
    {
        $request = HTTPRequest::instance();
        if (KanbanURL::isKanbanURL($request)) {
            return new KanbanJavascriptDependenciesProvider($this->getIncludeAssets());
        } elseif (AgileDashboardLegacyController::isPlanningV2URL($request)) {
            return new PlanningJavascriptDependenciesProvider($this->getIncludeAssets());
        }

        return null;
    }

    private function isAnAgiledashboardRequest()
    {
        return $this->currentRequestIsForPlugin();
    }

    private function isScrumAdminURL()
    {
        $request = HTTPRequest::instance();

        return strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0 &&
            $request->get('action') === 'admin' &&
            $request->get('pane') !== 'kanban' &&
            $request->get('pane') !== 'charts';
    }

    private function isPlanningV2URL()
    {
        $request              = HTTPRequest::instance();
        $pane_info_identifier = new AgileDashboard_PaneInfoIdentifier();

        return $pane_info_identifier->isPaneAPlanningV2($request->get('pane'));
    }

    private function isHomepageURL(HTTPRequest $request)
    {
        return (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath() . '/?group_id=') === 0
            && $request->get('action') === false
        );
    }

    /**
     * Builds a new PlanningFactory instance.
     *
     * @return PlanningFactory
     */
    protected function getPlanningFactory()
    {
        return PlanningFactory::build();
    }

    /**
     * Builds a new Planning_MilestoneFactory instance.
     * @return Planning_MilestoneFactory
     */
    public function getMilestoneFactory()
    {
        return Planning_MilestoneFactory::build();
    }

    private function getArtifactFactory()
    {
        return Tracker_ArtifactFactory::instance();
    }

    private function getHierarchyFactory()
    {
        return Tracker_HierarchyFactory::instance();
    }

    private function getBacklogFactory()
    {
        return new AgileDashboard_Milestone_Backlog_BacklogFactory(
            new AgileDashboard_BacklogItemDao(),
            $this->getArtifactFactory(),
            PlanningFactory::build(),
            $this->getMonoMilestoneChecker(),
            $this->getMonoMilestoneItemsFinder()
        );
    }

    public function tracker_event_artifact_association_edited($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($params['request']->isAjax()) {
            $milestone_factory = $this->getMilestoneFactory();
            $milestone         = $milestone_factory->getBareMilestoneByArtifact($params['user'], $params['artifact']);

            $milestone_with_contextual_info = $milestone_factory->updateMilestoneContextualInfo($params['user'], $milestone);

            $capacity         = $milestone_with_contextual_info->getCapacity();
            $remaining_effort = $milestone_with_contextual_info->getRemainingEffort();

            header('Content-type: application/json');
            echo json_encode([
                'remaining_effort' => $remaining_effort,
                'is_over_capacity' => $capacity !== null && $remaining_effort !== null && $capacity < $remaining_effort,
            ]);
        }
    }

    /**
     * @see Event::TRACKER_EVENT_MANAGE_SEMANTICS
     */
    public function tracker_event_manage_semantics($parameters) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $tracker   = $parameters['tracker'];
        $semantics = $parameters['semantics'];
        \assert($semantics instanceof Tracker_SemanticCollection);

        $semantics->add(AgileDashBoard_Semantic_InitialEffort::load($tracker));
    }

    /**
     * @see Event::TRACKER_EVENT_SEMANTIC_FROM_XML
     */
    public function tracker_event_semantic_from_xml(&$parameters) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $tracker           = $parameters['tracker'];
        $xml               = $parameters['xml'];
        $full_semantic_xml = $parameters['full_semantic_xml'];
        $xmlMapping        = $parameters['xml_mapping'];
        $type              = $parameters['type'];

        if ($type == AgileDashBoard_Semantic_InitialEffort::NAME) {
            $parameters['semantic'] = $this->getSemanticInitialEffortFactory()->getInstanceFromXML(
                $xml,
                $full_semantic_xml,
                $xmlMapping,
                $tracker,
                []
            );
        }
    }

    private function getSemanticDoneFactory(): SemanticDoneFactory
    {
        return new SemanticDoneFactory(new SemanticDoneDao(), new SemanticDoneValueChecker());
    }

    /**
     * @see TRACKER_EVENT_GET_SEMANTIC_DUPLICATORS
     */
    public function tracker_event_get_semantic_duplicators($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $params['duplicators'][] = $this->getSemanticInitialEffortFactory();
    }

    protected function getSemanticInitialEffortFactory()
    {
        return AgileDashboard_Semantic_InitialEffortFactory::instance();
    }

    /**
     * Augment $params['semantics'] with names of AgileDashboard semantics
     *
     * @see TRACKER_EVENT_GET_SEMANTICS_NAMES
     */
    public function tracker_event_get_semantics_names(&$params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $params['semantics'][] = AgileDashBoard_Semantic_InitialEffort::NAME;
    }

    /**
     *
     * @param array $params
     *  Expected key/ values:
     *      project_id  int             The ID of the project for the import
     *      xml_content SimpleXmlObject A string of valid xml
     *      mapping     array           An array of mappings between xml tracker IDs and their true IDs
     *
     */
    public function import_xml_project_cardwall_done($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $request = new HTTPRequest();
        $request->set('action', 'import');
        $request->set('xml_content', $params['xml_content']);
        $request->set('mapping', $params['mapping']);
        $request->set('artifact_id_mapping', $params['artifact_id_mapping']);
        $request->set('logger', $params['logger']);
        $request->set('project_id', $params['project_id']);
        $request->set('group_id', $params['project_id']);

        $this->routeLegacyController(new ProvideCurrentUserForXMLImport(UserManager::instance()))
            ->process($request, $GLOBALS['Response'], []);
    }

    public function plugin_statistics_service_usage($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $dao                  = new AgileDashboard_Dao();
        $statistic_aggregator = new AgileDashboardStatisticsAggregator();
        $params['csv_exporter']->buildDatas($dao->getProjectsWithADActivated(), "Agile Dashboard activated");
        foreach ($statistic_aggregator->getStatisticsLabels() as $statistic_key => $statistic_name) {
            $statistic_data = $statistic_aggregator->getStatistics(
                $statistic_key,
                $params['start_date'],
                $params['end_date']
            );
            $params['csv_exporter']->buildDatas($statistic_data, $statistic_name);
        }
    }

    /**
     * @see REST_PROJECT_ADDITIONAL_INFORMATIONS
     */
    public function rest_project_additional_informations($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $root_planning = $this->getPlanningFactory()->getRootPlanning($this->getCurrentUser(), $params['project']->getGroupId());
        if (! $root_planning) {
            return;
        }

        $planning_representation = new \Tuleap\AgileDashboard\REST\v1\PlanningRepresentation();
        $planning_representation->build($root_planning);

        $params['informations'][$this->getName()]['root_planning'] = $planning_representation;
    }

    /**
     * @see REST_RESOURCES
     */
    public function rest_resources($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $injector = new AgileDashboard_REST_ResourcesInjector();
        $injector->populate($params['restler']);

        EventManager::instance()->processEvent(
            AGILEDASHBOARD_EVENT_REST_RESOURCES,
            $params
        );
    }

    /**
     * @see Event::REST_PROJECT_RESOURCES
     */
    public function rest_project_resources(array $params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $injector = new AgileDashboard_REST_ResourcesInjector();
        $injector->declareProjectPlanningResource($params['resources'], $params['project']);
    }

    /**
    * @see ITEM_PRIORITY_CHANGE
    */
    public function item_priority_change($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $planning_id = $this->getPlanningIdFromParameters($params);

        $params['user_is_authorized'] = $this->getPlanningPermissionsManager()->userHasPermissionOnPlanning(
            $planning_id,
            $params['group_id'],
            $params['user'],
            PlanningPermissionsManager::PERM_PRIORITY_CHANGE
        );
    }

    private function getPlanningIdFromParameters($params)
    {
        if ($params['milestone_id'] == 0) {
            $planning = $this->getPlanningFactory()->getRootPlanning(
                $params['user'],
                $params['group_id']
            );

            return $planning->getId();
        }

        $artifact  = $this->getArtifactFactory()->getArtifactById($params['milestone_id']);
        $milestone = $this->getMilestoneFactory()->getMilestoneFromArtifact($artifact);

        return $milestone->getPlanningId();
    }

    /** @see Event::GET_PROJECTID_FROM_URL */
    public function get_projectid_from_url($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if (strpos($params['url'], '/plugins/agiledashboard/') === 0) {
            $params['project_id'] = $params['request']->get('group_id');
        }
    }

    /**
     * @see TRACKER_EVENT_FIELD_AUGMENT_DATA_FOR_REPORT
     */
    public function tracker_event_field_augment_data_for_report($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if (! $this->isFieldPriority($params['field'])) {
            return;
        }

        $params['result'] = $this->getFieldPriorityAugmenter()->getAugmentedDataForFieldPriority(
            $this->getCurrentUser(),
            $params['field']->getTracker()->getProject(),
            $params['additional_criteria'],
            $params['artifact_id']
        );
    }

    private function getFieldPriorityAugmenter()
    {
        return new AgileDashboard_FieldPriorityAugmenter(
            $this->getSequenceIdManager(),
            $this->getMilestoneFactory()
        );
    }

    private function isFieldPriority(Tracker_FormElement_Field $field)
    {
        return $field instanceof Tracker_FormElement_Field_Priority;
    }

    private function getSequenceIdManager()
    {
        if (! $this->sequence_id_manager) {
            $this->sequence_id_manager = new AgileDashboard_SequenceIdManager(
                $this->getBacklogFactory(),
                $this->getBacklogItemCollectionFactory(
                    $this->getMilestoneFactory(),
                    new AgileDashboard_Milestone_Backlog_BacklogItemBuilder()
                )
            );
        }

        return $this->sequence_id_manager;
    }

    public function cardwall_event_use_standard_javascript($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $request              = HTTPRequest::instance();
        $pane_info_identifier = new AgileDashboard_PaneInfoIdentifier();
        if ($pane_info_identifier->isPaneAPlanningV2($request->get('pane')) || KanbanURL::isKanbanURL($request)) {
            $params['use_standard'] = false;
        }
    }

    /** @see Tracker_Artifact_EditRenderer::EVENT_ADD_VIEW_IN_COLLECTION */
    public function tracker_artifact_editrenderer_add_view_in_collection(array $params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $user       = $params['user'];
        $request    = $params['request'];
        $artifact   = $params['artifact'];
        $collection = $params['collection'];

        $milestone = $this->getMilestoneFactory()->getBareMilestoneByArtifact($user, $artifact);
        if ($milestone) {
            $collection->add(new Tuleap\AgileDashboard\Milestone\ArtifactView($milestone, $request, $user));
        }
    }

    /**
     * @return TrackerFactory
     */
    private function getTrackerFactory()
    {
        return TrackerFactory::instance();
    }

    /**
     * @return AgileDashboard_KanbanManager
     */
    private function getKanbanManager()
    {
        return new AgileDashboard_KanbanManager(
            new AgileDashboard_KanbanDao(),
            $this->getTrackerFactory()
        );
    }

    private function getCurrentUser(): PFUser
    {
        return UserManager::instance()->getCurrentUser();
    }

    private function getPlanningPermissionsManager()
    {
        return new PlanningPermissionsManager();
    }

    /**
     * @return AgileDashboard_KanbanFactory
     */
    private function getKanbanFactory()
    {
        return new AgileDashboard_KanbanFactory(
            TrackerFactory::instance(),
            new AgileDashboard_KanbanDao()
        );
    }

    /**
     * @return ScrumForMonoMilestoneChecker
     */
    private function getMonoMilestoneChecker()
    {
        return new ScrumForMonoMilestoneChecker(new ScrumForMonoMilestoneDao(), $this->getPlanningFactory());
    }

    private function getMonoMilestoneItemsFinder()
    {
        return new MonoMilestoneItemsFinder(
            new MonoMilestoneBacklogItemDao(),
            $this->getArtifactFactory()
        );
    }

    public function testmanagementGetMilestone(\Tuleap\TestManagement\Event\GetMilestone $event)
    {
        $milestone_factory = $this->getMilestoneFactory();
        $milestone         = $milestone_factory->getBareMilestoneByArtifactId($event->getUser(), $event->getMilestoneId());
        $event->setMilestone($milestone);
    }

    public function testmanagementGetItemsFromMilestone(\Tuleap\TestManagement\Event\GetItemsFromMilestone $event)
    {
        $milestone_factory               = $this->getMilestoneFactory();
        $backlog_factory                 = $this->getBacklogFactory();
        $backlog_item_collection_factory = $this->getBacklogItemCollectionFactory(
            $milestone_factory,
            new AgileDashboard_Milestone_Backlog_BacklogItemBuilder()
        );

        $user         = $event->getUser();
        $milestone_id = $event->getMilestoneId();
        $milestone    = $milestone_factory->getValidatedBareMilestoneByArtifactId($user, $milestone_id);
        if (! $milestone) {
            return;
        }
        $backlog       = $backlog_factory->getSelfBacklog($milestone);
        $backlog_items = $backlog_item_collection_factory->getOpenAndClosedCollection($user, $milestone, $backlog, '');
        $items_ids     = [];

        foreach ($backlog_items as $item) {
            $items_ids[] = $item->id();

            if ($item->hasChildren()) {
                $this->parseChildrenElements($item, $user, $items_ids);
            }
        }

        $event->setItemsIds(array_unique($items_ids));
    }

    private function parseChildrenElements(AgileDashboard_Milestone_Backlog_BacklogItem $item, PFUser $user, array &$item_ids)
    {
        $tracker_artifact_dao = new Tracker_ArtifactDao();

        $children = $tracker_artifact_dao->getChildren($item->getArtifact()->getId())
            ->instanciateWith([$this->getArtifactFactory(), 'getInstanceFromRow']);

        foreach ($children as $child) {
            if ($child->userCanView($user)) {
                $item_ids[] = $child->getId();
            }
        }
    }

    /**
     * @return KanbanArtifactMessageSender
     */
    private function getKanbanArtifactMessageSender()
    {
        $kanban_item_dao                   = new AgileDashboard_KanbanItemDao();
        $permissions_serializer            = new Tracker_Permission_PermissionsSerializer(
            new Tracker_Permission_PermissionRetrieveAssignee(UserManager::instance())
        );
        $node_js_client                    = new NodeJSClient(
            HttpClientFactory::createClient(),
            HTTPFactoryBuilder::requestFactory(),
            HTTPFactoryBuilder::streamFactory(),
            BackendLogger::getDefaultLogger()
        );
        $realtime_artifact_message_builder = new KanbanArtifactMessageBuilder(
            $kanban_item_dao,
            Tracker_Artifact_ChangesetFactoryBuilder::build()
        );
        $backend_logger                    = BackendLogger::getDefaultLogger('realtime_syslog');
        $realtime_artifact_message_sender  = new RealTimeArtifactMessageSender($node_js_client, $permissions_serializer);

        return new KanbanArtifactMessageSender(
            $realtime_artifact_message_sender,
            $realtime_artifact_message_builder,
            $backend_logger
        );
    }

    /**
     * @return RealTimeArtifactMessageController
     */
    public function getRealtimeMessageController()
    {
        return new RealTimeArtifactMessageController(
            $this->getKanbanFactory(),
            $this->getKanbanArtifactMessageSender()
        );
    }

    public function trackerArtifactCreated(ArtifactCreated $event)
    {
        $artifact = $event->getArtifact();
        $this->getRealtimeMessageController()->sendMessageForKanban(
            $this->getCurrentUser(),
            $artifact,
            RealTimeArtifactMessageController::EVENT_NAME_ARTIFACT_CREATED
        );

        $cleaner = new DirectArtifactLinkCleaner(
            $this->getMilestoneFactory(),
            new ExplicitBacklogDao(),
            new ArtifactsInExplicitBacklogDao()
        );

        $cleaner->cleanDirectlyMadeArtifactLinks($artifact, $this->getCurrentUser());
    }

    public function trackerArtifactUpdated(ArtifactUpdated $event)
    {
        $artifact = $event->getArtifact();
        $this->getRealtimeMessageController()->sendMessageForKanban(
            $this->getCurrentUser(),
            $artifact,
            RealTimeArtifactMessageController::EVENT_NAME_ARTIFACT_UPDATED
        );

        $cleaner = new DirectArtifactLinkCleaner(
            $this->getMilestoneFactory(),
            new ExplicitBacklogDao(),
            new ArtifactsInExplicitBacklogDao()
        );

        $cleaner->cleanDirectlyMadeArtifactLinks($artifact, $event->getUser());
    }

    public function trackerArtifactsReordered(ArtifactsReordered $event)
    {
        $artifacts_ids = $event->getArtifactsIds();
        $artifacts     = $this->getArtifactFactory()->getArtifactsByArtifactIdList($artifacts_ids);
        foreach ($artifacts as $artifact) {
            $this->getRealtimeMessageController()->sendMessageForKanban(
                $this->getCurrentUser(),
                $artifact,
                RealTimeArtifactMessageController::EVENT_NAME_ARTIFACT_REORDERED
            );
        }
    }

    public function trackerReportDeleted(TrackerReportDeleted $event)
    {
        $report  = $event->getReport();
        $updater = new TrackerReportUpdater(new TrackerReportDao());

        $updater->deleteAllForReport($report);

        $this->deleteReportConfigForKanbanWidget(
            $event->getReport()
        );
    }

    public function trackerReportSetToPrivate(TrackerReportSetToPrivate $event)
    {
        $this->deleteReportConfigForKanbanWidget(
            $event->getReport()
        );
    }

    private function deleteReportConfigForKanbanWidget(Tracker_Report $report)
    {
        $widget_kanban_config_updater = new WidgetKanbanConfigUpdater(
            new WidgetKanbanConfigDAO()
        );

        $widget_kanban_config_updater->deleteConfigurationForWidgetMatchingReportId($report);
    }

    public function codendi_daily_start($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        SystemEventManager::instance()->createEvent(
            SystemEvent_BURNUP_DAILY::class,
            "",
            SystemEvent::PRIORITY_MEDIUM,
            SystemEvent::OWNER_APP
        );
    }


    public function get_system_event_class($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        switch ($params['type']) {
            case SystemEvent_BURNUP_DAILY::class:
                $params['class']        = SystemEvent_BURNUP_DAILY::class;
                $params['dependencies'] = [
                    $this->getBurnupDao(),
                    $this->getBurnupCalculator(),
                    $this->getBurnupCountElementsCalculator(),
                    new BurnupCacheDao(),
                    new CountElementsCacheDao(),
                    $this->getLogger(),
                    new BurnupCacheDateRetriever(),
                ];
                break;
            case SystemEvent_BURNUP_GENERATE::class:
                $tracker_artifact_factory = Tracker_ArtifactFactory::instance();
                $params['class']          = SystemEvent_BURNUP_GENERATE::class;
                $params['dependencies']   = [
                    $tracker_artifact_factory,
                    SemanticTimeframeBuilder::build(),
                    new BurnupDao(),
                    $this->getBurnupCalculator(),
                    $this->getBurnupCountElementsCalculator(),
                    new BurnupCacheDao(),
                    new CountElementsCacheDao(),
                    $this->getLogger(),
                    new BurnupCacheDateRetriever(),
                ];
                break;
            default:
                break;
        }
    }

    public function system_event_get_types_for_default_queue($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $params['types'][] = SystemEvent_BURNUP_DAILY::class;
        $params['types'][] = SystemEvent_BURNUP_GENERATE::class;
    }

    /**
     * @return BurnupDao
     */
    private function getBurnupDao()
    {
        return new BurnupDao();
    }

    private function getLogger(): \Psr\Log\LoggerInterface
    {
        return BackendLogger::getDefaultLogger();
    }

    /**
     * @return BurnupCalculator
     */
    private function getBurnupCalculator()
    {
        $changeset_factory = Tracker_Artifact_ChangesetFactoryBuilder::build();

        return new BurnupCalculator(
            $changeset_factory,
            $this->getArtifactFactory(),
            $this->getBurnupDao(),
            $this->getSemanticInitialEffortFactory(),
            $this->getSemanticDoneFactory()
        );
    }

    /**
     * @return CountElementsCalculator
     */
    private function getBurnupCountElementsCalculator()
    {
        $changeset_factory = Tracker_Artifact_ChangesetFactoryBuilder::build();

        return new CountElementsCalculator(
            $changeset_factory,
            $this->getArtifactFactory(),
            Tracker_FormElementFactory::instance(),
            $this->getBurnupDao()
        );
    }

    public function getMessageFetcherAdditionalWarnings(MessageFetcherAdditionalWarnings $event)
    {
        $message_fetcher = new MessageFetcher(
            $this->getPlanningFactory(),
            $this->getSemanticInitialEffortFactory(),
            $this->getSemanticDoneFactory()
        );

        $field = $event->getField();

        if ($field::class === Tuleap\AgileDashboard\FormElement\Burnup::class) {
            $event->setWarnings($message_fetcher->getWarningsRelatedToPlanningConfiguration($field->getTracker()));
        }
    }

    public function importXMLProjectTrackerDone(ImportXMLProjectTrackerDone $event): void
    {
        $xml             = $event->getXmlElement();
        $tracker_mapping = $event->getCreatedTrackersMapping();
        $value_mapping   = $event->getXmlFieldValuesMapping();
        $logger          = $event->getLogger();
        $project         = $event->getProject();
        $user            = UserManager::instance()->getCurrentUser();

        $kanban = new KanbanXmlImporter(
            new WrapperLogger($logger, "kanban"),
            $this->getKanbanManager(),
            $this->getConfigurationManager(),
            $this->getDashboardKanbanColumnManager(),
            $this->getKanbanFactory(),
            $this->getKanbanColumnFactory()
        );
        $kanban->import($xml, $tracker_mapping, $project, $value_mapping, $user, $event->getMappingsRegistery());
    }

    private function getDashboardKanbanColumnManager()
    {
        return new AgileDashboard_KanbanColumnManager(
            new AgileDashboard_KanbanColumnDao(),
            new BindStaticValueDao(),
            new AgileDashboard_KanbanActionsChecker(
                $this->getTrackerFactory(),
                new AgileDashboard_PermissionsManager(),
                $this->getFormElementFactory()
            )
        );
    }

    /**
     * @return Tracker_FormElementFactory
     */
    private function getFormElementFactory()
    {
        return Tracker_FormElementFactory::instance();
    }

    /**
     * @return AgileDashboard_KanbanColumnFactory
     */
    private function getKanbanColumnFactory()
    {
        return new AgileDashboard_KanbanColumnFactory(
            new AgileDashboard_KanbanColumnDao(),
            new AgileDashboard_KanbanUserPreferences()
        );
    }

    private function isInOverviewTab()
    {
        $request = HTTPRequest::instance();

        return $this->isAnAgiledashboardRequest()
            && $request->get('action') === DetailsPaneInfo::ACTION
            && $request->get('pane') === DetailsPaneInfo::IDENTIFIER;
    }

    public function permissionPerGroupPaneCollector(PermissionPerGroupPaneCollector $event)
    {
        $project = $event->getProject();

        if (! $project->usesService($this->getServiceShortname())) {
            return;
        }

        $ugroup_id = HTTPRequest::instance()->get('group');

        $ugroup_manager = new UGroupManager();
        $ugroup         = $ugroup_manager->getUGroup($project, $ugroup_id);
        $ugroup_name    = ($ugroup) ? $ugroup->getTranslatedName() : "";

        $template_factory      = TemplateRendererFactory::build();
        $admin_permission_pane = $template_factory
            ->getRenderer(AGILEDASHBOARD_TEMPLATE_DIR)
            ->renderToString(
                'project-admin-permission-per-group',
                [
                    "ugroup_id"            => $ugroup_id,
                    "project_id"           => $project->getID(),
                    "selected_ugroup_name" => $ugroup_name,
                ]
            );

        $service = $project->getService($this->getServiceShortname());
        if ($service !== null) {
            $rank_in_project = $service->getRank();
            $event->addPane($admin_permission_pane, $rank_in_project);
        }
    }

    public function trackerArtifactDeleted(ArtifactDeleted $artifact_deleted): void
    {
        $burnup_cache_dao = new BurnupCacheDao();
        $artifact         = $artifact_deleted->getArtifact();

        $burnup_cache_dao->deleteArtifactCacheValue($artifact->getId());

        $artifact_explicit_backlog_dao = new ArtifactsInExplicitBacklogDao();
        $artifact_explicit_backlog_dao->removeArtifactFromExplicitBacklog($artifact->getId());
    }

    public function moveArtifactGetExternalSemanticCheckers(MoveArtifactGetExternalSemanticCheckers $event)
    {
        $checker = new MoveSemanticInitialEffortChecker(
            $this->getSemanticInitialEffortFactory(),
            $this->getFormElementFactory()
        );

        $event->addExternalSemanticsChecker($checker);
    }

    public function moveArtifactParseFieldChangeNodes(MoveArtifactParseFieldChangeNodes $event)
    {
        if (
            ! $this->getMoveSemanticInitialEffortChecker()->areSemanticsAligned(
                $event->getSourceTracker(),
                $event->getTargetTracker()
            )
        ) {
            return;
        }

        $updater = new MoveChangesetXMLUpdater(
            $this->getSemanticInitialEffortFactory(),
            $this->getFormElementFactory(),
            new FieldValueMatcher(new XMLImportHelper(UserManager::instance()))
        );

        if (
            $updater->parseFieldChangeNodesAtGivenIndex(
                $event->getSourceTracker(),
                $event->getTargetTracker(),
                $event->getChangesetXml(),
                $event->getIndex(),
                $event->getFeedbackFieldCollector()
            )
        ) {
            $event->setModifiedByPlugin();
        }
    }

    /**
     * @return MoveSemanticInitialEffortChecker
     */
    private function getMoveSemanticInitialEffortChecker()
    {
        return new MoveSemanticInitialEffortChecker(
            $this->getSemanticInitialEffortFactory(),
            $this->getFormElementFactory()
        );
    }

    public function moveArtifactActionAllowedByPluginRetriever(MoveArtifactActionAllowedByPluginRetriever $event)
    {
        if ($this->getSemanticInitialEffortFactory()->getByTracker($event->getTracker())->getFieldId() !== 0) {
            $event->hasExternalSemanticDefined();
        }
    }

    public function collectRoutesEvent(\Tuleap\Request\CollectRoutesEvent $event)
    {
        $event->getRouteCollector()->addGroup('/plugins/agiledashboard', function (FastRoute\RouteCollector $r) {
            $r->addRoute(['GET', 'POST'], '[/[index.php]]', $this->getRouteHandler('routeLegacyController'));
        });
    }

    public function routeLegacyController(?ProvideCurrentUser $current_user_provider = null): AgileDashboardLegacyController
    {
        if ($current_user_provider === null) {
            $current_user_provider = UserManager::instance();
        }

        return new AgileDashboardLegacyController(
            new AgileDashboardRouterBuilder(
                PluginFactory::instance(),
                $this->getMilestonePaneFactory(),
                new VisitRecorder(new RecentlyVisitedDao()),
                $this->getAllBreadCrumbsForMilestoneBuilder(),
                $this->getBacklogFactory(),
                $current_user_provider,
            )
        );
    }

    public function getAllBreadCrumbsForMilestoneBuilder(): AllBreadCrumbsForMilestoneBuilder
    {
        return new AllBreadCrumbsForMilestoneBuilder(
            new AgileDashboardCrumbBuilder($this->getPluginPath()),
            new VirtualTopMilestoneCrumbBuilder($this->getPluginPath()),
            new MilestoneCrumbBuilder(
                $this->getPluginPath(),
                $this->getMilestonePaneFactory(),
                $this->getMilestoneFactory()
            )
        );
    }

    public function trackerCrumbInContext(TrackerCrumbInContext $crumb)
    {
        (new \Tuleap\AgileDashboard\Kanban\BreadCrumbBuilder($this->getTrackerFactory(), $this->getKanbanFactory()))->addKanbanCrumb($crumb);
    }

    public function getHistoryQuickLinkCollection(HistoryQuickLinkCollection $collection): void
    {
        $milestone = $this->getMilestoneFactory()->getMilestoneFromArtifact($collection->getArtifact());
        if ($milestone === null) {
            return;
        }

        $pane_factory = $this->getMilestonePaneFactory();

        foreach ($pane_factory->getListOfPaneInfo($milestone, $collection->getCurrentUser()) as $pane) {
            $collection->add(
                new HistoryQuickLink(
                    $pane->getTitle(),
                    $pane->getUri(),
                    $pane->getIconName()
                )
            );
        }
    }

    private function getBacklogItemCollectionFactory(
        Planning_MilestoneFactory $milestone_factory,
        AgileDashboard_Milestone_Backlog_IBuildBacklogItemAndBacklogItemCollection $presenter_builder,
    ): AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory {
        $form_element_factory = Tracker_FormElementFactory::instance();

        return new AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory(
            new AgileDashboard_BacklogItemDao(),
            $this->getArtifactFactory(),
            $milestone_factory,
            $this->getPlanningFactory(),
            $presenter_builder,
            new RemainingEffortValueRetriever($form_element_factory),
            new ArtifactsInExplicitBacklogDao(),
            new Tracker_Artifact_PriorityDao()
        );
    }

    public function getMilestonePaneFactory(): Planning_MilestonePaneFactory
    {
        $request = HTTPRequest::instance();

        $planning_factory       = $this->getPlanningFactory();
        $milestone_factory      = $this->getMilestoneFactory();
        $hierarchy_factory      = $this->getHierarchyFactory();
        $mono_milestone_checker = $this->getMonoMileStoneChecker();
        $submilestone_finder    = new AgileDashboard_Milestone_Pane_Planning_SubmilestoneFinder(
            $hierarchy_factory,
            $planning_factory,
            $mono_milestone_checker
        );

        $pane_info_factory = new AgileDashboard_PaneInfoFactory(
            $submilestone_finder
        );

        $event_manager = EventManager::instance();

        return new Planning_MilestonePaneFactory(
            $request,
            $milestone_factory,
            new AgileDashboard_Milestone_Pane_PanePresenterBuilderFactory(
                $this->getBacklogFactory(),
                $this->getBacklogItemCollectionFactory(
                    $this->getMilestoneFactory(),
                    new AgileDashboard_Milestone_Backlog_BacklogItemPresenterBuilder()
                ),
                new BurnupFieldRetriever(Tracker_FormElementFactory::instance()),
                $event_manager
            ),
            $submilestone_finder,
            $pane_info_factory,
            $event_manager
        );
    }

    public function getIncludeAssets(): IncludeAssets
    {
        return new IncludeAssets(
            __DIR__ . '/../../../src/www/assets/agiledashboard',
            '/assets/agiledashboard'
        );
    }

    public function statisticsCollectionCollector(StatisticsCollectionCollector $collector): void
    {
        $collector->addStatistics(
            dgettext('tuleap-agiledashboard', 'Kanban cards'),
            $this->getKanbanDao()->countKanbanCards(),
            $this->getKanbanDao()->countKanbanCardsAfter($collector->getTimestamp())
        );

        $collector->addStatistics(
            dgettext('tuleap-agiledashboard', 'Milestones'),
            $this->getMilestoneDao()->countMilestones(),
            $this->getMilestoneDao()->countMilestonesAfter($collector->getTimestamp())
        );
    }

    private function getKanbanDao(): AgileDashboard_KanbanDao
    {
        return new AgileDashboard_KanbanDao();
    }

    private function getMilestoneDao(): AgileDashboard_Milestone_MilestoneDao
    {
        return new AgileDashboard_Milestone_MilestoneDao();
    }

    public function project_is_deleted($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if (! empty($params['group_id'])) {
            $artifact_explicit_backlog_dao = new ArtifactsInExplicitBacklogDao();
            $artifact_explicit_backlog_dao->removeExplicitBacklogOfProject((int) $params['group_id']);
        }
    }

    public function additionalArtifactActionButtonsFetcher(AdditionalArtifactActionButtonsFetcher $event): void
    {
        $artifact = $event->getArtifact();
        $user     = $event->getUser();

        $builder = new AdditionalArtifactActionBuilder(
            new ExplicitBacklogDao(),
            $this->getPlanningFactory(),
            $this->getPlanningPermissionsManager(),
            new ArtifactsInExplicitBacklogDao(),
            new PlannedArtifactDao(),
            $this->getScriptAssetByName('artifact-additional-action.js'),
            new PlanningTrackerBacklogChecker($this->getPlanningFactory()),
            EventManager::instance()
        );

        $action = $builder->buildArtifactAction($artifact, $user);

        if ($action !== null) {
            $event->addAction($action);
        }
    }

    public function trackerMasschangeGetExternalActionsEvent(TrackerMasschangeGetExternalActionsEvent $event): void
    {
        $builder = new \Tuleap\AgileDashboard\Masschange\AdditionalMasschangeActionBuilder(
            new ExplicitBacklogDao(),
            $this->getPlanningFactory(),
            TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../templates/masschange'),
            EventManager::instance()
        );

        $additional_action = $builder->buildMasschangeAction($event->getTracker(), $event->getUser());
        if ($additional_action !== null) {
            $event->addExternalActions($additional_action);
        }
    }

    public function serviceEnableForXmlImportRetriever(ServiceEnableForXmlImportRetriever $event): void
    {
        $event->addServiceIfPluginIsNotRestricted($this, $this->getServiceShortname());
    }

    public function trackerMasschangeProcessExternalActionsEvent(TrackerMasschangeProcessExternalActionsEvent $event): void
    {
        $processor = new AdditionalMasschangeActionProcessor(
            new ArtifactsInExplicitBacklogDao(),
            new PlannedArtifactDao(),
            $this->getUnplannedArtifactsAdder(),
            EventManager::instance()
        );

        $processor->processAction(
            $event->getUser(),
            $event->getTracker(),
            $event->getRequest(),
            $event->getMasschangeAids()
        );
    }

    public function getExternalSubFactoriesEvent(GetExternalSubFactoriesEvent $event)
    {
        $event->addFactory(
            $this->getAddToTopBacklogPostActionFactory()
        );
    }

    public function workflowDeletionEvent(WorkflowDeletionEvent $event): void
    {
        $workflow_id = (int) $event->getWorkflow()->getId();

        (new AddToTopBacklogPostActionDao())->deleteWorkflowPostActions($workflow_id);
    }

    private function getUnplannedArtifactsAdder(): UnplannedArtifactsAdder
    {
        return new UnplannedArtifactsAdder(
            new ExplicitBacklogDao(),
            new ArtifactsInExplicitBacklogDao(),
            new PlannedArtifactDao()
        );
    }

    public function transitionDeletionEvent(TransitionDeletionEvent $event)
    {
        $transition_id = (int) $event->getTransition()->getId();

        (new AddToTopBacklogPostActionDao())->deleteTransitionPostActions($transition_id);
    }

    public function postActionVisitExternalActionsEvent(PostActionVisitExternalActionsEvent $event)
    {
        $post_action = $event->getPostAction();

        if (! $post_action instanceof AddToTopBacklog) {
            return;
        }

        $representation = AddToTopBacklogRepresentation::buildFromObject($post_action);
        $event->setRepresentation($representation);
    }

    public function getExternalPostActionJsonParserEvent(GetExternalPostActionJsonParserEvent $event): void
    {
        $event->addParser(
            new AddToTopBacklogJsonParser(new ExplicitBacklogDao())
        );
    }

    public function getWorkflowExternalPostActionsValueUpdater(GetWorkflowExternalPostActionsValueUpdater $event): void
    {
        $event->addValueUpdater(
            new AddToTopBacklogValueUpdater(
                new AddToTopBacklogValueRepository(
                    new AddToTopBacklogPostActionDao()
                )
            )
        );
    }

    public function getExternalSubFactoryByNameEvent(GetExternalSubFactoryByNameEvent $event): void
    {
        if ($event->getPostActionShortName() === AddToTopBacklog::SHORT_NAME) {
            $event->setFactory(
                $this->getAddToTopBacklogPostActionFactory()
            );
        }
    }

    public function externalPostActionSaveObjectEvent(ExternalPostActionSaveObjectEvent $event): void
    {
        $post_action = $event->getPostAction();
        if (! $post_action instanceof AddToTopBacklog) {
            return;
        }

        $factory = $this->getAddToTopBacklogPostActionFactory();
        $factory->saveObject($post_action);
    }

    public function getPostActionShortNameFromXmlTagNameEvent(GetPostActionShortNameFromXmlTagNameEvent $event): void
    {
        if ($event->getXmlTagName() === AddToTopBacklog::XML_TAG_NAME) {
            $event->setPostActionShortName(AddToTopBacklog::SHORT_NAME);
        }
    }

    private function getAddToTopBacklogPostActionFactory(): AddToTopBacklogPostActionFactory
    {
        if (! $this->add_to_top_backlog_post_action_factory) {
            $this->add_to_top_backlog_post_action_factory = new AddToTopBacklogPostActionFactory(
                new AddToTopBacklogPostActionDao(),
                $this->getUnplannedArtifactsAdder(),
                new ExplicitBacklogDao()
            );
        }
        return $this->add_to_top_backlog_post_action_factory;
    }

    /**
     * @throws TrackerFromXmlException
     */
    public function createTrackerFromXMLEvent(CreateTrackerFromXMLEvent $event): void
    {
        $checker = new CreateTrackerFromXMLChecker(new ExplicitBacklogDao());
        $checker->checkTrackerCanBeCreatedInTrackerCreationContext(
            $event->getProject(),
            $event->getTrackerXml()
        );
    }

    /**
     * @throws ImportNotValidException
     */
    public function projectXMLImportPreChecksEvent(ProjectXMLImportPreChecksEvent $event): void
    {
        $xml_content = $event->getXmlElement();
        $checker     = new CreateTrackerFromXMLChecker(new ExplicitBacklogDao());

        try {
            $checker->checkTrackersCanBeCreatedInProjectImportContext($xml_content);
        } catch (ProjectNotUsingExplicitBacklogException $exception) {
            throw new ImportNotValidException(
                'Explicit backlog management is not used and some "AddToTopBacklog" workflow post actions are defined.'
            );
        }
    }

    public function getExternalPostActionPluginsEvent(GetExternalPostActionPluginsEvent $event): void
    {
        $tracker    = $event->getTracker();
        $project_id = (int) $tracker->getGroupId();

        $is_agile_dashboard_used  = $this->isAllowed($project_id);
        $is_explicit_backlog_used = (new ExplicitBacklogDao())->isProjectUsingExplicitBacklog($project_id);

        $planning_tracker_backlog_checker    = new PlanningTrackerBacklogChecker($this->getPlanningFactory());
        $is_tracker_backlog_of_root_planning = $planning_tracker_backlog_checker->isTrackerBacklogOfProjectRootPlanning(
            $tracker,
            $this->getCurrentUser()
        );

        if (! $is_agile_dashboard_used || ! $is_explicit_backlog_used || ! $is_tracker_backlog_of_root_planning || $this->isScrumAccessBlocked($tracker->getProject())) {
            return;
        }

        $event->addServiceNameUsed('agile_dashboard');
    }

    private function getScriptAssetByName(string $name): JavascriptAsset
    {
        return new JavascriptAsset($this->getIncludeAssets(), $name);
    }

    public function checkPostActionsForTracker(CheckPostActionsForTracker $event): void
    {
        $planning_tracker_backlog_checker = new PlanningTrackerBacklogChecker($this->getPlanningFactory());
        $tracker                          = $event->getTracker();
        $external_post_actions            = $event->getPostActions()->getExternalPostActionsValue();
        foreach ($external_post_actions as $post_action) {
            if (
                $post_action instanceof AddToTopBacklogValue &&
                (
                    ! $planning_tracker_backlog_checker->isTrackerBacklogOfProjectRootPlanning(
                        $tracker,
                        $this->getCurrentUser(),
                    ) ||
                    $this->isScrumAccessBlocked($tracker->getProject())
                )
            ) {
                $message = dgettext(
                    'tuleap-agiledashboard',
                    'The post actions cannot be saved because this tracker is not a top backlog tracker and a "AddToTopBacklog" is defined.'
                );

                $event->setErrorMessage($message);
                $event->setPostActionsNonEligible();
            }
        }
    }

    public function getWorkflowExternalPostActionsValuesForUpdate(GetWorkflowExternalPostActionsValuesForUpdate $event): void
    {
        $project_id = (int) $event->getTransition()->getGroupId();
        if (! (new ExplicitBacklogDao())->isProjectUsingExplicitBacklog($project_id)) {
            return;
        }

        $add_to_top_backlog_post_actions = $this->getAddToTopBacklogPostActionFactory()->loadPostActions(
            $event->getTransition()
        );

        if (count($add_to_top_backlog_post_actions) > 0) {
            $event->addExternalValue(new AddToTopBacklogValue());
        }
    }

    public function defaultTemplatesXMLFileCollection(DefaultTemplatesXMLFileCollection $collection): void
    {
        $this->addKanbanTemplates($collection);
    }

    private function addKanbanTemplates(DefaultTemplatesXMLFileCollection $collection): void
    {
        $collection->add(__DIR__ . '/../resources/templates/Tracker_activity.xml');
    }

    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function service_is_used(array $params): void
    {
        if (! isset($params['shortname']) || ! isset($params['is_used'])) {
            return;
        }

        $service_short_name = (string) $params['shortname'];
        $service_is_used    = (bool) $params['is_used'];

        if ($service_short_name !== $this->getServiceShortname() || ! $service_is_used) {
            return;
        }

        $explicit_backlog_configuration_updater = $this->getExplicitBacklogConfigurationUpdater();

        $project = ProjectManager::instance()->getProject((int) $params['group_id']);
        $user    = UserManager::instance()->getCurrentUser();

        $explicit_backlog_configuration_updater->activateExplicitBacklogManagement(
            $project,
            $user
        );
    }

    private function getExplicitBacklogConfigurationUpdater(): ConfigurationUpdater
    {
        return new ConfigurationUpdater(
            new ExplicitBacklogDao(),
            new MilestoneReportCriterionDao(),
            new AgileDashboard_BacklogItemDao(),
            Planning_MilestoneFactory::build(),
            new ArtifactsInExplicitBacklogDao(),
            new UnplannedArtifactsAdder(
                new ExplicitBacklogDao(),
                new ArtifactsInExplicitBacklogDao(),
                new PlannedArtifactDao()
            ),
            new AddToTopBacklogPostActionDao(),
            new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection()),
            EventManager::instance()
        );
    }

    /**
     * @return MustacheRenderer|TemplateRenderer
     */
    private function getTemplateRenderer()
    {
        return TemplateRendererFactory::build()->getRenderer(AGILEDASHBOARD_TEMPLATE_DIR);
    }

    private function isScrumAccessBlocked(Project $project): bool
    {
        $block_scrum_access = new \Tuleap\AgileDashboard\BlockScrumAccess($project);
        EventManager::instance()->dispatch($block_scrum_access);
        return ! $block_scrum_access->isScrumAccessEnabled();
    }

    public function jiraImporterExternalPluginsEvent(JiraImporterExternalPluginsEvent $event): void
    {
        (new SemanticsExporter())->process(
            $event->getXmlTracker(),
            $event->getJiraPlatformConfiguration(),
            $event->getFieldMappingCollection(),
        );
    }

    public function getSemanticProgressUsageEvent(GetSemanticProgressUsageEvent $event): void
    {
        $event->addFutureUsageLocation(
            dgettext('tuleap-agiledashboard', 'the Agile Dashboard')
        );
    }

    public function semanticDoneUsedExternalServiceEvent(SemanticDoneUsedExternalServiceEvent $event): void
    {
        $project = $event->getTracker()->getProject();
        if (! $project->usesService($this->getServiceShortname())) {
            return;
        }

        $event->setExternalServicesDescriptions(
            new SemanticDoneUsedExternalService(
                dgettext('tuleap-agiledashboard', 'AgileDashboard service'),
                dgettext('tuleap-agiledashboard', 'burnup and velocity charts')
            )
        );
    }
}
