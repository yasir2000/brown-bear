<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard;

use AdminKanbanPresenter;
use AgileDashboard_BacklogItemDao;
use AgileDashboard_ConfigurationManager;
use AgileDashboard_FirstKanbanCreator;
use AgileDashboard_FirstScrumCreator;
use AgileDashboard_KanbanFactory;
use AgileDashboard_KanbanManager;
use AgileDashboardConfigurationResponse;
use AgileDashboardKanbanConfigurationUpdater;
use AgileDashboardScrumConfigurationUpdater;
use Codendi_Request;
use CSRFSynchronizerToken;
use EventManager;
use Feedback;
use MilestoneReportCriterionDao;
use PFUser;
use Planning_MilestoneFactory;
use PlanningFactory;
use Project;
use ProjectXMLImporter;
use Tracker_ReportFactory;
use TrackerFactory;
use TrackerXmlImport;
use Tuleap\AgileDashboard\Artifact\PlannedArtifactDao;
use Tuleap\AgileDashboard\BreadCrumbDropdown\AdministrationCrumbBuilder;
use Tuleap\AgileDashboard\BreadCrumbDropdown\AgileDashboardCrumbBuilder;
use Tuleap\AgileDashboard\Event\GetAdditionalScrumAdminSection;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\AgileDashboard\ExplicitBacklog\ConfigurationUpdater;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\AgileDashboard\ExplicitBacklog\UnplannedArtifactsAdder;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsModeChecker;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsModeUpdater;
use Tuleap\AgileDashboard\FormElement\Burnup\ProjectsCountModeDao;
use Tuleap\AgileDashboard\Kanban\TrackerReport\TrackerReportDao;
use Tuleap\AgileDashboard\Kanban\TrackerReport\TrackerReportUpdater;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneDao;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneDisabler;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneEnabler;
use Tuleap\AgileDashboard\Scrum\ScrumPresenterBuilder;
use Tuleap\AgileDashboard\Workflow\AddToTopBacklogPostActionDao;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbCollection;
use Tuleap\Layout\IncludeAssets;
use UserManager;
use XMLImportHelper;

class AdminController extends BaseController
{
    /** @var AgileDashboard_KanbanFactory */
    private $kanban_factory;

    /** @var PlanningFactory */
    private $planning_factory;

    /** @var AgileDashboard_KanbanManager */
    private $kanban_manager;

    /** @var AgileDashboard_ConfigurationManager */
    private $config_manager;

    /** @var TrackerFactory */
    private $tracker_factory;

    /** @var EventManager */
    private $event_manager;

    /** @var Project */
    private $project;

    /** @var AgileDashboardCrumbBuilder */
    private $service_crumb_builder;

    /** @var AdministrationCrumbBuilder */
    private $admin_crumb_builder;

    /**
     * @var CountElementsModeChecker
     */
    private $count_elements_mode_checker;
    /**
     * @var ScrumPresenterBuilder
     */
    private $scrum_presenter_builder;

    /**
     * @var GetAdditionalScrumAdminSection
     */
    private $additional_scrum_sections;

    public function __construct(
        Codendi_Request $request,
        PlanningFactory $planning_factory,
        AgileDashboard_KanbanManager $kanban_manager,
        AgileDashboard_KanbanFactory $kanban_factory,
        AgileDashboard_ConfigurationManager $config_manager,
        TrackerFactory $tracker_factory,
        EventManager $event_manager,
        AgileDashboardCrumbBuilder $service_crumb_builder,
        AdministrationCrumbBuilder $admin_crumb_builder,
        CountElementsModeChecker $count_elements_mode_checker,
        ScrumPresenterBuilder $scrum_presenter_builder,
    ) {
        parent::__construct('agiledashboard', $request);

        $this->group_id                    = (int) $this->request->get('group_id');
        $this->project                     = $this->request->getProject();
        $this->planning_factory            = $planning_factory;
        $this->kanban_manager              = $kanban_manager;
        $this->kanban_factory              = $kanban_factory;
        $this->config_manager              = $config_manager;
        $this->tracker_factory             = $tracker_factory;
        $this->event_manager               = $event_manager;
        $this->service_crumb_builder       = $service_crumb_builder;
        $this->admin_crumb_builder         = $admin_crumb_builder;
        $this->count_elements_mode_checker = $count_elements_mode_checker;
        $this->scrum_presenter_builder     = $scrum_presenter_builder;

        $this->additional_scrum_sections = new GetAdditionalScrumAdminSection($this->project);
        $this->event_manager->dispatch($this->additional_scrum_sections);
    }

    /**
     * @return BreadCrumbCollection
     */
    public function getBreadcrumbs()
    {
        $breadcrumbs = new BreadCrumbCollection();
        $breadcrumbs->addBreadCrumb(
            $this->service_crumb_builder->build(
                $this->getCurrentUser(),
                $this->project
            )
        );
        $breadcrumbs->addBreadCrumb(
            $this->admin_crumb_builder->build($this->project)
        );

        return $breadcrumbs;
    }

    public function adminScrum(): string
    {
        $this->redirectToKanbanPaneIfScrumAccessIsBlocked();
        $include_assets = new IncludeAssets(
            __DIR__ . '/../../../../src/www/assets/agiledashboard',
            '/assets/agiledashboard'
        );

        $GLOBALS['HTML']->includeFooterJavascriptFile($include_assets->getFileURL('administration.js'));

        return $this->renderToString(
            'admin-scrum',
            $this->scrum_presenter_builder->getAdminScrumPresenter(
                $this->getCurrentUser(),
                $this->project,
                $this->additional_scrum_sections
            )
        );
    }

    public function adminKanban()
    {
        return $this->renderToString(
            'admin-kanban',
            $this->getAdminKanbanPresenter(
                $this->getCurrentUser(),
                $this->group_id
            )
        );
    }

    public function adminCharts()
    {
        $this->redirectToKanbanPaneIfScrumAccessIsBlocked();
        return $this->renderToString(
            "admin-charts",
            $this->getAdminChartsPresenter(
                $this->project
            )
        );
    }

    private function getAdminKanbanPresenter(PFUser $user, $project_id)
    {
        $has_kanban = count($this->kanban_factory->getListOfKanbansForProject($user, $project_id)) > 0;

        return new AdminKanbanPresenter(
            $project_id,
            $this->config_manager->kanbanIsActivatedForProject($project_id),
            $this->config_manager->getKanbanTitle($project_id),
            $has_kanban,
            $this->isScrumAccessible(),
            \ForgeConfig::get('use_burnup_count_elements')
        );
    }

    public function updateConfiguration(): void
    {
        if (! $this->request->getCurrentUser()->isAdmin($this->group_id)) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('global', 'perm_denied')
            );

            return;
        }

        $token = new CSRFSynchronizerToken('/plugins/agiledashboard/?action=admin');
        $token->check();

        $response = new AgileDashboardConfigurationResponse(
            $this->request->getProject(),
            $this->request->exist('home-ease-onboarding')
        );

        if ($this->request->exist('activate-kanban')) {
            $updater = new AgileDashboardKanbanConfigurationUpdater(
                $this->request,
                $this->config_manager,
                $response,
                new AgileDashboard_FirstKanbanCreator(
                    $this->request->getProject(),
                    $this->kanban_manager,
                    $this->tracker_factory,
                    TrackerXmlImport::build(new XMLImportHelper(UserManager::instance())),
                    $this->kanban_factory,
                    new TrackerReportUpdater(new TrackerReportDao()),
                    Tracker_ReportFactory::instance()
                )
            );
        } elseif ($this->request->exist("burnup-count-mode")) {
            $updater = new AgileDashboardChartsConfigurationUpdater(
                $this->request,
                new CountElementsModeUpdater(
                    new ProjectsCountModeDao()
                )
            );
        } else {
            $scrum_mono_milestone_dao = new ScrumForMonoMilestoneDao();
            $updater                  = new AgileDashboardScrumConfigurationUpdater(
                $this->request,
                $this->config_manager,
                $response,
                new AgileDashboard_FirstScrumCreator(
                    $this->request->getProject(),
                    $this->planning_factory,
                    $this->tracker_factory,
                    ProjectXMLImporter::build(
                        new XMLImportHelper(UserManager::instance()),
                        \ProjectCreator::buildSelfByPassValidation()
                    )
                ),
                new ScrumForMonoMilestoneEnabler($scrum_mono_milestone_dao),
                new ScrumForMonoMilestoneDisabler($scrum_mono_milestone_dao),
                new ScrumForMonoMilestoneChecker($scrum_mono_milestone_dao, $this->planning_factory),
                new ConfigurationUpdater(
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
                    $this->event_manager
                ),
                $this->event_manager
            );
        }

        $this->additional_scrum_sections->notifyAdditionalSectionsControllers(\HTTPRequest::instance());
        $updater->updateConfiguration();
    }

    public function createKanban()
    {
        $kanban_name = $this->request->get('kanban-name');
        $tracker_id  = $this->request->get('tracker-kanban');
        $tracker     = $this->tracker_factory->getTrackerById($tracker_id);
        $user        = $this->request->getCurrentUser();

        if (! $user->isAdmin($this->group_id)) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('global', 'perm_denied')
            );

            return;
        }

        if (! $tracker_id || $tracker === null) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-agiledashboard', 'No tracker has been selected.')
            );

            $this->redirectToHome();

            return;
        }

        if ($this->kanban_manager->doesKanbanExistForTracker($tracker)) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-agiledashboard', 'Tracker already used by another Kanban.')
            );

            $this->redirectToHome();

            return;
        }

        if ($this->kanban_manager->createKanban($kanban_name, $tracker_id)) {
            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                sprintf(dgettext('tuleap-agiledashboard', 'Kanban %1$s successfully created.'), $kanban_name)
            );
        } else {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                sprintf(dgettext('tuleap-agiledashboard', 'Error while creating Kanban %1$s.'), $kanban_name)
            );
        }

        $this->redirectToHome();
    }

    private function redirectToHome()
    {
        $this->redirect(
            [
                'group_id' => $this->group_id,
            ]
        );
    }

    private function isScrumAccessible(): bool
    {
        $block_access_scrum = new BlockScrumAccess($this->project);
        $this->event_manager->dispatch($block_access_scrum);

        return $block_access_scrum->isScrumAccessEnabled();
    }

    private function redirectToKanbanPaneIfScrumAccessIsBlocked(): void
    {
        if (! $this->isScrumAccessible()) {
            $this->redirect(['group_id' => $this->project->getID(), 'action' => 'admin', 'pane' => 'kanban']);
        }
    }

    private function getAdminChartsPresenter(Project $project): AdminChartsPresenter
    {
        $token = new CSRFSynchronizerToken('/plugins/agiledashboard/?action=admin');

        return new AdminChartsPresenter(
            $project,
            $token,
            $this->count_elements_mode_checker->burnupMustUseCountElementsMode($project)
        );
    }
}
