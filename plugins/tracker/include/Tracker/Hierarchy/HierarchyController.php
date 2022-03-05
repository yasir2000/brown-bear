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

namespace Tuleap\Tracker\Hierarchy;

use Codendi_Request;
use TemplateRenderer;
use TemplateRendererFactory;
use Tracker;
use Tracker_Hierarchy_HierarchicalTracker;
use Tracker_Hierarchy_HierarchicalTrackerFactory;
use Tracker_Hierarchy_Presenter;
use Tracker_Workflow_Trigger_RulesDao;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Valid_UInt;

class HierarchyController
{
    /**
     * @var Codendi_Request
     */
    private $request;

    /**
     * @var Tracker_Hierarchy_HierarchicalTracker
     */
    private $tracker;

    /**
     * @var Tracker_Hierarchy_HierarchicalTrackerFactory
     */
    private $factory;

    /**
     * @var HierarchyDAO
     */
    private $dao;
    /**
     * @var Tracker_Workflow_Trigger_RulesDao
     */
    private $tracker_workflow_trigger_rules_dao;
    /**
     * @var TemplateRenderer
     */
    private $renderer;
    /**
     * @var ArtifactLinksUsageDao
     */
    private $artifact_links_usage_dao;

    public function __construct(
        Codendi_Request $request,
        Tracker_Hierarchy_HierarchicalTracker $tracker,
        Tracker_Hierarchy_HierarchicalTrackerFactory $factory,
        HierarchyDAO $dao,
        Tracker_Workflow_Trigger_RulesDao $tracker_workflow_trigger_rules_dao,
        ArtifactLinksUsageDao $artifact_links_usage_dao,
    ) {
        $this->request                            = $request;
        $this->tracker                            = $tracker;
        $this->factory                            = $factory;
        $this->dao                                = $dao;
        $this->tracker_workflow_trigger_rules_dao = $tracker_workflow_trigger_rules_dao;
        $this->renderer                           = TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../../../templates');
        $this->artifact_links_usage_dao           = $artifact_links_usage_dao;
    }

    public function edit(): void
    {
        $this->render('admin-hierarchy', $this->buildPresenter());
    }

    public function buildPresenter(): Tracker_Hierarchy_Presenter
    {
        return new Tracker_Hierarchy_Presenter(
            $this->tracker,
            $this->factory->getPossibleChildren($this->tracker),
            $this->factory->getHierarchy($this->tracker->getUnhierarchizedTracker()),
            $this->getChildrenUsedInTriggerRules()
        );
    }

    /**
     * @return Tracker[]
     * @psalm-return array<int, Tracker> Array of tracker by tracker ID
     */
    private function getChildrenUsedInTriggerRules(): array
    {
        $rows = $this->tracker_workflow_trigger_rules_dao->searchTriggeringTrackersByTargetTrackerID($this->tracker->getId());
        if ($rows === false || $rows === []) {
            return [];
        }

        $children_id_used_in_triggers_rules = [];
        foreach ($rows as $row) {
            $children_id_used_in_triggers_rules[$row['tracker_id']] = true;
        }

        $children_used_in_triggers_rules = [];
        foreach ($this->tracker->getChildren() as $child) {
            if (isset($children_id_used_in_triggers_rules[$child->getId()])) {
                $children_used_in_triggers_rules[$child->getId()] = $child;
            }
        }

        return $children_used_in_triggers_rules;
    }

    public function update(): void
    {
        $vChildren = new Valid_UInt('children');
        $vChildren->required();

        if (! $this->request->validArray($vChildren) && $this->request->exist('children')) {
            $GLOBALS['Response']->addFeedback('error', dgettext('tuleap-tracker', 'Your request contains invalid data, cowardly doing nothing (children parameter)'));
            $this->redirectToAdminHierarchy();
            return;
        }
        /** @var int[]|false $wanted_children */
        $wanted_children = $this->request->get('children');
        if ($wanted_children === false) {
            $wanted_children = [];
        }

        $children_used_in_trigger_rules = $this->getChildrenUsedInTriggerRules();

        if ($this->artifact_links_usage_dao->isProjectUsingArtifactLinkTypes((int) $this->tracker->getProject()->getID())) {
            $this->dao->changeTrackerHierarchy(
                $this->tracker->getId(),
                array_merge($wanted_children, array_keys($children_used_in_trigger_rules))
            );
        } else {
            //If project does not use the artifact link types yet, _is_child must continue
            //to be automatically set to that when an admin will enable the types everything
            //will be consistent
            $this->dao->updateChildren(
                $this->tracker->getId(),
                array_merge($wanted_children, array_keys($children_used_in_trigger_rules))
            );
        }

        $this->redirectToAdminHierarchy();
    }

    private function redirectToAdminHierarchy(): void
    {
        $redirect = http_build_query(
            [
                'tracker' => $this->tracker->getId(),
                'func'    => 'admin-hierarchy',
            ]
        );
        $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?' . $redirect);
    }

    private function render($template_name, $presenter)
    {
        $this->renderer->renderToPage($template_name, $presenter);
    }

    /**
     *
     * @param array $mapping the id of tracker's children
     */
    public function updateFromXmlProjectImportProcess(array $mapping)
    {
        $this->dao->updateChildren($this->tracker->getId(), $mapping);
    }
}
