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

use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker;
use Tuleap\Tracker\Artifact\Artifact;

/**
 * A planning milestone (e.g.: Sprint, Release...)
 */
class Planning_ArtifactMilestone implements Planning_Milestone
{
    /**
     * The project where the milestone is defined
     *
     * @var Project
     */
    private $project;

    /**
     * The association between the tracker that define the "Content" (aka Backlog) (ie. Epic)
     * and the tracker that define the plan (ie. Release)
     *
     * @var Planning
     */

    private $planning;

    /**
     * The artifact that represent the milestone
     *
     * For instance a Sprint or a Release
     *
     * @var Artifact
     */
    private $artifact;

    /**
     * The planned artifacts are the content of the milestone (stuff to be done)
     *
     * Given current Milestone is a Sprint
     * And I defined a Sprint planning that associate Stories to Sprints
     * Then I will have an array of Sprint as planned artifacts.
     *
     * @var ArtifactNode
     */
    private $planned_artifacts;

    /**
     * A parent milestone is the milestone that contains the current one.
     *
     * Given current Milestone is a Sprint
     * And there is a Parent/Child association between Release and Sprint
     * And there is a Parent/Child association between Product and Release
     * Then $parent_milestones will be a Release and a Product
     *
     * @var array of Planning_Milestone
     */
    private $parent_milestones = [];

    /**
     * @var TimePeriodWithoutWeekEnd|null
     */
    private $time_period = null;

    /**
     * The capacity of the milestone
     *
     * @var float
     */
    private $capacity = null;

     /**
     * The remaining effort of the milestone
     *
     * @var float
     */
    private $remaining_effort = null;

     /**
      * @var bool
      */
    private $has_useable_burndown_field;

    /**
     * @var ScrumForMonoMilestoneChecker
     */
    private $scrum_mono_milestone_checker;

    public function __construct(
        Project $project,
        Planning $planning,
        Artifact $artifact,
        ScrumForMonoMilestoneChecker $scrum_mono_milestone_checker,
        ?ArtifactNode $planned_artifacts = null,
    ) {
        $this->project                      = $project;
        $this->planning                     = $planning;
        $this->artifact                     = $artifact;
        $this->planned_artifacts            = $planned_artifacts;
        $this->scrum_mono_milestone_checker = $scrum_mono_milestone_checker;
    }

    /**
     * @return int The project identifier.
     */
    public function getGroupId()
    {
        return $this->project->getID();
    }

    /**
     * @return Project
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * @return Artifact
     */
    public function getArtifact()
    {
        return $this->artifact;
    }

    /**
     * @return bool
     */
    public function userCanView(PFUser $user)
    {
        return $this->artifact->getTracker()->userCanView($user);
    }

    /**
     * @return int
     */
    public function getTrackerId()
    {
        return $this->artifact->getTrackerId();
    }

    /**
     * @return int
     */
    public function getArtifactId()
    {
        return $this->artifact->getId();
    }

    /**
     * @return string
     */
    public function getArtifactTitle()
    {
        return $this->artifact->getTitle() ?? '';
    }

    /**
     * @return string
     */
    public function getXRef()
    {
        return $this->artifact->getXRef();
    }


    /**
     * @return Planning
     */
    public function getPlanning()
    {
        return $this->planning;
    }

    /**
     * @return int
     */
    public function getPlanningId()
    {
        return $this->planning->getId();
    }

    /**
     * @return ArtifactNode
     */
    public function getPlannedArtifacts()
    {
        return $this->planned_artifacts;
    }

    public function setPlannedArtifacts(ArtifactNode $node)
    {
        $this->planned_artifacts = $node;
    }

    /**
     * All artifacts linked by either the root artifact or any of the artifacts in plannedArtifacts()
     * @return Artifact[]
     */
    public function getLinkedArtifacts(PFUser $user)
    {
        $artifacts = $this->artifact->getUniqueLinkedArtifacts($user);
        $root_node = $this->getPlannedArtifacts();
        // TODO get rid of this if, in favor of an empty treenode
        if ($root_node) {
            $this->addChildrenNodes($root_node, $artifacts, $user);
        }
        return $artifacts;
    }

    private function addChildrenNodes(ArtifactNode $root_node, &$artifacts, $user)
    {
        foreach ($root_node->getChildren() as $node) {
            $artifact    = $node->getObject();
            $artifacts[] = $artifact;
            $artifacts   = array_merge($artifacts, $artifact->getUniqueLinkedArtifacts($user));
            $this->addChildrenNodes($node, $artifacts, $user);
        }
    }

    public function hasAncestors()
    {
        return ! empty($this->parent_milestones);
    }

    public function getAncestors()
    {
        return $this->parent_milestones;
    }

    public function getParent()
    {
        $parent_milestones_values = array_values($this->parent_milestones);
        return array_shift($parent_milestones_values);
    }

    public function setAncestors(array $ancestors)
    {
        $this->parent_milestones = $ancestors;
    }

    public function getStartDate()
    {
        if ($this->time_period !== null) {
            return $this->time_period->getStartDate();
        }

        return null;
    }

    public function getEndDate()
    {
        if (! $this->getStartDate()) {
            return null;
        }

        if ($this->getDuration() === null || $this->getDuration() <= 0) {
            return null;
        }

        if ($this->time_period !== null) {
            return (int) $this->time_period->getEndDate();
        }

        return null;
    }

    public function getDaysSinceStart()
    {
        if ($this->time_period !== null) {
            return $this->time_period->getNumberOfDaysSinceStart();
        }

        return null;
    }

    public function getDaysUntilEnd()
    {
        if ($this->time_period !== null) {
            return $this->time_period->getNumberOfDaysUntilEnd();
        }

        return null;
    }

    public function getCapacity()
    {
        return $this->capacity;
    }

    public function setCapacity($capacity)
    {
        $this->capacity = $capacity;
    }

    public function getRemainingEffort()
    {
        return $this->remaining_effort;
    }

    public function setRemainingEffort($remaining_effort)
    {
        $this->remaining_effort = $remaining_effort;
    }

    /**
     * @param array $artifacts_ids
     * @return bool True if nothing went wrong
     */
    public function solveInconsistencies(PFUser $user, array $artifacts_ids)
    {
        $artifact = $this->getArtifact();

        return $artifact->linkArtifacts($artifacts_ids, $user);
    }

    /**
     * Get the timestamp of the last modification of the milestone
     *
     * @return int
     */
    public function getLastModifiedDate()
    {
        return $this->getArtifact()->getLastUpdateDate();
    }

    /**
     * @see Planning_Milestone::getDuration()
     * @return int|null
     */
    public function getDuration()
    {
        if ($this->time_period !== null) {
            return $this->time_period->getDuration();
        }

        return null;
    }

    public function milestoneCanBeSubmilestone(Planning_Milestone $potential_submilestone)
    {
        if ($this->scrum_mono_milestone_checker->isMonoMilestoneEnabled($potential_submilestone->getProject()->getID()) === true) {
            return $this->acceptOnlySameTrackerInMonoMilestoneCofiguration($potential_submilestone);
        }

        if ($potential_submilestone->getArtifact()->getTracker()->getParent()) {
            $parent = $potential_submilestone->getArtifact()->getTracker()->getParent();
            if ($parent === null) {
                throw new RuntimeException('Tracker does not exist');
            }
            return $parent->getId() == $this->getArtifact()->getTracker()->getId();
        }

        return false;
    }

    private function acceptOnlySameTrackerInMonoMilestoneCofiguration(Planning_Milestone $potential_submilestone)
    {
        return $potential_submilestone->getArtifact()->getTracker()->getId()  == $this->getArtifact()->getTracker()->getId();
    }

    /**
     * @return bool
     */
    public function hasBurdownField(PFUser $user)
    {
        $burndown_field = $this->getArtifact()->getABurndownField($user);

        return (bool) $burndown_field;
    }

    /**
     * @param bool $bool
     */
    public function setHasUsableBurndownField($bool)
    {
        $this->has_useable_burndown_field = $bool;
    }

    public function hasUsableBurndownField()
    {
        return (bool) $this->has_useable_burndown_field;
    }

    public function getBurndownData(PFUser $user)
    {
        if (! $this->hasBurdownField($user)) {
            return null;
        }

        if ($this->time_period === null) {
            return null;
        }

        $milestone_artifact = $this->getArtifact();
        $burndown_field     = $milestone_artifact->getABurndownField($user);

        return $burndown_field->getBurndownData(
            $milestone_artifact,
            $user,
            $this->time_period
        );
    }

    public function setTimePeriod(TimePeriodWithoutWeekEnd $time_period)
    {
        $this->time_period = $time_period;
    }
}
