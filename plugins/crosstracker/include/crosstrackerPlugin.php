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

use Tuleap\CrossTracker\CrossTrackerArtifactReportDao;
use Tuleap\CrossTracker\CrossTrackerReportDao;
use Tuleap\CrossTracker\CrossTrackerReportFactory;
use Tuleap\CrossTracker\Permission\CrossTrackerPermissionGate;
use Tuleap\CrossTracker\Report\CrossTrackerArtifactReportFactory;
use Tuleap\CrossTracker\Report\CSV\CSVExportController;
use Tuleap\CrossTracker\Report\CSV\CSVRepresentationBuilder;
use Tuleap\CrossTracker\Report\CSV\CSVRepresentationFactory;
use Tuleap\CrossTracker\Report\CSV\Format\BindToValueVisitor;
use Tuleap\CrossTracker\Report\CSV\Format\CSVFormatterVisitor;
use Tuleap\CrossTracker\Report\CSV\SimilarFieldsFormatter;
use Tuleap\CrossTracker\Report\Query\Advanced\InvalidComparisonCollectorVisitor;
use Tuleap\CrossTracker\Report\Query\Advanced\InvalidSearchableCollectorVisitor;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\CrossTrackerExpertQueryReportDao;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\AlwaysThereField\Date;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\AlwaysThereField\Users;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\BetweenComparisonFromWhereBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\EqualComparisonFromWhereBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\GreaterThanComparisonFromWhereBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\GreaterThanOrEqualComparisonFromWhereBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\InComparisonFromWhereBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\LesserThanComparisonFromWhereBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\LesserThanOrEqualComparisonFromWhereBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\ListValueExtractor;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\NotEqualComparisonFromWhereBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\NotInComparisonFromWhereBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\Semantic\AssignedTo;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\Semantic\Description;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\Semantic\Status;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\Semantic\Title;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\SearchableVisitor;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilderVisitor;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\Between\BetweenComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\Equal\EqualComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\GreaterThan\GreaterThanComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\GreaterThan\GreaterThanOrEqualComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\In\InComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\LesserThan\LesserThanComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\LesserThan\LesserThanOrEqualComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\ListValueValidator;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\NotEqual\NotEqualComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\NotIn\NotInComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Metadata\MetadataChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Metadata\MetadataUsageChecker;
use Tuleap\CrossTracker\Report\SimilarField\BindNameVisitor;
use Tuleap\CrossTracker\Report\SimilarField\SimilarFieldsFilter;
use Tuleap\CrossTracker\Report\SimilarField\SimilarFieldsMatcher;
use Tuleap\CrossTracker\Report\SimilarField\SupportedFieldsDao;
use Tuleap\CrossTracker\REST\ResourcesInjector;
use Tuleap\CrossTracker\Widget\ProjectCrossTrackerSearch;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\Tracker\FormElement\Field\Date\CSVFormatter;
use Tuleap\Tracker\Report\Query\Advanced\DateFormat;
use Tuleap\Tracker\Report\Query\Advanced\ExpertQueryValidator;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Parser;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Date\DateFormatValidator;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\EmptyStringAllowed;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\EmptyStringForbidden;
use Tuleap\Tracker\Report\Query\Advanced\ParserCacheProxy;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\DateTimeValueRounder;
use Tuleap\Tracker\Report\Query\Advanced\SizeValidatorVisitor;
use Tuleap\Tracker\Report\TrackerReportConfig;
use Tuleap\Tracker\Report\TrackerReportConfigDao;

require_once __DIR__ . '/../../tracker/include/trackerPlugin.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/constants.php';

class crosstrackerPlugin extends Plugin // phpcs:ignore
{
    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_SYSTEM);

        bindtextdomain('tuleap-crosstracker', __DIR__ . '/../site-content');
    }

    public function getHooksAndCallbacks()
    {
        if (defined('TRACKER_BASE_URL')) {
            $this->addHook(\Tuleap\Widget\Event\GetWidget::NAME);
            $this->addHook(\Tuleap\Widget\Event\GetUserWidgetList::NAME);
            $this->addHook(\Tuleap\Widget\Event\GetProjectWidgetList::NAME);
            $this->addHook(Event::REST_RESOURCES);
            $this->addHook(TRACKER_EVENT_PROJECT_CREATION_TRACKERS_REQUIRED);
            $this->addHook(CollectRoutesEvent::NAME);
        }

        return parent::getHooksAndCallbacks();
    }

    /**
     * @see Plugin::getDependencies()
     */
    public function getDependencies()
    {
        return ['tracker'];
    }

    /**
     * @return Tuleap\CrossTracker\Plugin\PluginInfo
     */
    public function getPluginInfo()
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new Tuleap\CrossTracker\Plugin\PluginInfo($this);
        }

        return $this->pluginInfo;
    }

    public function getUserWidgetList(\Tuleap\Widget\Event\GetUserWidgetList $event)
    {
        $event->addWidget(ProjectCrossTrackerSearch::NAME);
    }

    public function getProjectWidgetList(\Tuleap\Widget\Event\GetProjectWidgetList $event)
    {
        $event->addWidget(ProjectCrossTrackerSearch::NAME);
    }

    public function widgetInstance(\Tuleap\Widget\Event\GetWidget $get_widget_event)
    {
        if ($get_widget_event->getName() === ProjectCrossTrackerSearch::NAME) {
            $get_widget_event->setWidget(new ProjectCrossTrackerSearch());
        }
    }

    public function uninstall()
    {
        $this->removeOrphanWidgets([ProjectCrossTrackerSearch::NAME]);
    }

    /** @see Event::REST_RESOURCES */
    public function restResources(array $params)
    {
        $injector = new ResourcesInjector();
        $injector->populate($params['restler']);
    }

    /** @see TRACKER_EVENT_PROJECT_CREATION_TRACKERS_REQUIRED */
    public function trackerEventProjectCreationTrackersRequired(array $params)
    {
        $dao = new CrossTrackerReportDao();
        foreach ($dao->searchTrackersIdUsedByCrossTrackerByProjectId($params['project_id']) as $row) {
            $params['tracker_ids_list'][] = $row['id'];
        }
    }

    public function collectRoutesEvent(CollectRoutesEvent $event)
    {
        $event->getRouteCollector()->get(CROSSTRACKER_BASE_URL . '/csv_export/{report_id:\d+}', $this->getRouteHandler('routeGetCSVExportReport'));
    }

    public function routeGetCSVExportReport(): CSVExportController
    {
        $user_manager = UserManager::instance();

        $report_config = new TrackerReportConfig(
            new TrackerReportConfigDao()
        );

        $parser = new ParserCacheProxy(new Parser());

        $validator = new ExpertQueryValidator(
            $parser,
            new SizeValidatorVisitor($report_config->getExpertQueryLimit())
        );

        $date_validator                 = new DateFormatValidator(new EmptyStringForbidden(), DateFormat::DATETIME);
        $list_value_validator           = new ListValueValidator(new EmptyStringAllowed(), $user_manager);
        $list_value_validator_not_empty = new ListValueValidator(new EmptyStringForbidden(), $user_manager);

        $form_element_factory = Tracker_FormElementFactory::instance();

        $invalid_comparisons_collector = new InvalidComparisonCollectorVisitor(
            new InvalidSearchableCollectorVisitor(),
            new MetadataChecker(
                new MetadataUsageChecker(
                    $form_element_factory,
                    new Tracker_Semantic_TitleDao(),
                    new Tracker_Semantic_DescriptionDao(),
                    new Tracker_Semantic_StatusDao(),
                    new Tracker_Semantic_ContributorDao()
                )
            ),
            new EqualComparisonChecker($date_validator, $list_value_validator),
            new NotEqualComparisonChecker($date_validator, $list_value_validator),
            new GreaterThanComparisonChecker($date_validator, $list_value_validator),
            new GreaterThanOrEqualComparisonChecker($date_validator, $list_value_validator),
            new LesserThanComparisonChecker($date_validator, $list_value_validator),
            new LesserThanOrEqualComparisonChecker($date_validator, $list_value_validator),
            new BetweenComparisonChecker($date_validator, $list_value_validator),
            new InComparisonChecker($date_validator, $list_value_validator_not_empty),
            new NotInComparisonChecker($date_validator, $list_value_validator_not_empty)
        );

        $submitted_on_alias_field     = 'tracker_artifact.submitted_on';
        $last_update_date_alias_field = 'last_changeset.submitted_on';
        $submitted_by_alias_field     = 'tracker_artifact.submitted_by';
        $last_update_by_alias_field   = 'last_changeset.submitted_by';

        $date_value_extractor    = new Date\DateValueExtractor();
        $date_time_value_rounder = new DateTimeValueRounder();
        $list_value_extractor    = new ListValueExtractor();
        $query_builder_visitor   = new QueryBuilderVisitor(
            new SearchableVisitor(),
            new EqualComparisonFromWhereBuilder(
                new Title\EqualComparisonFromWhereBuilder(),
                new Description\EqualComparisonFromWhereBuilder(),
                new Status\EqualComparisonFromWhereBuilder(),
                new Date\EqualComparisonFromWhereBuilder(
                    $date_value_extractor,
                    $date_time_value_rounder,
                    $submitted_on_alias_field
                ),
                new Date\EqualComparisonFromWhereBuilder(
                    $date_value_extractor,
                    $date_time_value_rounder,
                    $last_update_date_alias_field
                ),
                new Users\EqualComparisonFromWhereBuilder(
                    $list_value_extractor,
                    $user_manager,
                    $submitted_by_alias_field
                ),
                new Users\EqualComparisonFromWhereBuilder(
                    $list_value_extractor,
                    $user_manager,
                    $last_update_by_alias_field
                ),
                new AssignedTo\EqualComparisonFromWhereBuilder(
                    $list_value_extractor,
                    $user_manager
                )
            ),
            new NotEqualComparisonFromWhereBuilder(
                new Title\NotEqualComparisonFromWhereBuilder(),
                new Description\NotEqualComparisonFromWhereBuilder(),
                new Status\NotEqualComparisonFromWhereBuilder(),
                new Date\NotEqualComparisonFromWhereBuilder(
                    $date_value_extractor,
                    $date_time_value_rounder,
                    $submitted_on_alias_field
                ),
                new Date\NotEqualComparisonFromWhereBuilder(
                    $date_value_extractor,
                    $date_time_value_rounder,
                    $last_update_date_alias_field
                ),
                new Users\NotEqualComparisonFromWhereBuilder(
                    $list_value_extractor,
                    $user_manager,
                    $submitted_by_alias_field
                ),
                new Users\NotEqualComparisonFromWhereBuilder(
                    $list_value_extractor,
                    $user_manager,
                    $last_update_by_alias_field
                ),
                new AssignedTo\NotEqualComparisonFromWhereBuilder(
                    $list_value_extractor,
                    $user_manager
                )
            ),
            new GreaterThanComparisonFromWhereBuilder(
                new Date\GreaterThanComparisonFromWhereBuilder(
                    $date_value_extractor,
                    $date_time_value_rounder,
                    $submitted_on_alias_field
                ),
                new Date\GreaterThanComparisonFromWhereBuilder(
                    $date_value_extractor,
                    $date_time_value_rounder,
                    $last_update_date_alias_field
                )
            ),
            new GreaterThanOrEqualComparisonFromWhereBuilder(
                new Date\GreaterThanOrEqualComparisonFromWhereBuilder(
                    $date_value_extractor,
                    $date_time_value_rounder,
                    $submitted_on_alias_field
                ),
                new Date\GreaterThanOrEqualComparisonFromWhereBuilder(
                    $date_value_extractor,
                    $date_time_value_rounder,
                    $last_update_date_alias_field
                )
            ),
            new LesserThanComparisonFromWhereBuilder(
                new Date\LesserThanComparisonFromWhereBuilder(
                    $date_value_extractor,
                    $date_time_value_rounder,
                    $submitted_on_alias_field
                ),
                new Date\LesserThanComparisonFromWhereBuilder(
                    $date_value_extractor,
                    $date_time_value_rounder,
                    $last_update_date_alias_field
                )
            ),
            new LesserThanOrEqualComparisonFromWhereBuilder(
                new Date\LesserThanOrEqualComparisonFromWhereBuilder(
                    $date_value_extractor,
                    $date_time_value_rounder,
                    $submitted_on_alias_field
                ),
                new Date\LesserThanOrEqualComparisonFromWhereBuilder(
                    $date_value_extractor,
                    $date_time_value_rounder,
                    $last_update_date_alias_field
                )
            ),
            new BetweenComparisonFromWhereBuilder(
                new Date\BetweenComparisonFromWhereBuilder(
                    $date_value_extractor,
                    $date_time_value_rounder,
                    $submitted_on_alias_field
                ),
                new Date\BetweenComparisonFromWhereBuilder(
                    $date_value_extractor,
                    $date_time_value_rounder,
                    $last_update_date_alias_field
                )
            ),
            new InComparisonFromWhereBuilder(
                new Users\InComparisonFromWhereBuilder(
                    $list_value_extractor,
                    $user_manager,
                    $submitted_by_alias_field
                ),
                new Users\InComparisonFromWhereBuilder(
                    $list_value_extractor,
                    $user_manager,
                    $last_update_by_alias_field
                ),
                new AssignedTo\InComparisonFromWhereBuilder(
                    $list_value_extractor,
                    $user_manager
                )
            ),
            new NotInComparisonFromWhereBuilder(
                new Users\NotInComparisonFromWhereBuilder(
                    $list_value_extractor,
                    $user_manager,
                    $submitted_by_alias_field
                ),
                new Users\NotInComparisonFromWhereBuilder(
                    $list_value_extractor,
                    $user_manager,
                    $last_update_by_alias_field
                ),
                new AssignedTo\NotInComparisonFromWhereBuilder(
                    $list_value_extractor,
                    $user_manager
                )
            )
        );

        $cross_tracker_artifact_factory = new CrossTrackerArtifactReportFactory(
            new CrossTrackerArtifactReportDao(),
            \Tracker_ArtifactFactory::instance(),
            $validator,
            $query_builder_visitor,
            $parser,
            new CrossTrackerExpertQueryReportDao(),
            $invalid_comparisons_collector
        );

        $report_dao = new CrossTrackerReportDao();

        $formatter_visitor = new CSVFormatterVisitor(new CSVFormatter());

        $csv_representation_builder = new CSVRepresentationBuilder(
            $formatter_visitor,
            $user_manager,
            new SimilarFieldsFormatter($formatter_visitor, new BindToValueVisitor())
        );
        $representation_factory     = new CSVRepresentationFactory($csv_representation_builder);

        return new CSVExportController(
            new CrossTrackerReportFactory(
                $report_dao,
                TrackerFactory::instance()
            ),
            $cross_tracker_artifact_factory,
            $representation_factory,
            $report_dao,
            ProjectManager::instance(),
            new CrossTrackerPermissionGate(new URLVerification()),
            new SimilarFieldsMatcher(
                new SupportedFieldsDao(),
                $form_element_factory,
                new SimilarFieldsFilter(),
                new BindNameVisitor()
            )
        );
    }
}
