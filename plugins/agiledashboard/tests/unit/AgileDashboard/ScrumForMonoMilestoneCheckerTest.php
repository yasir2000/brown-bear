<?php
/**
 * Copyright (c) Enalean, 2017 - present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\AgileDashboard;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneDao;
use Tuleap\ForgeConfigSandbox;

class ScrumForMonoMilestoneCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    /**
     * @var \PlanningFactory
     */
    private $planning_factory;

    /**
     * @var \PFUser
     */
    private $user;

    /**
     * @var ScrumForMonoMilestoneChecker
     */
    private $scrum_mono_milestone_checker;

    /**
     * @var ScrumForMonoMilestoneDao
     */
    private $scrum_mono_milestone_dao;

    protected function setUp(): void
    {
        $this->user                     = \Mockery::spy(\PFUser::class);
        $this->scrum_mono_milestone_dao = \Mockery::spy(\Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneDao::class);
        $this->planning_factory         = \Mockery::spy(\PlanningFactory::class);

        $this->scrum_mono_milestone_checker = new ScrumForMonoMilestoneChecker(
            $this->scrum_mono_milestone_dao,
            $this->planning_factory
        );
    }

    public function testItReturnsTrueWhenConfigurationIsInScrumV1(): void
    {
        $this->scrum_mono_milestone_dao->shouldReceive('isMonoMilestoneActivatedForProject')->andReturns(false);

        $this->assertTrue(
            $this->scrum_mono_milestone_checker->doesScrumMonoMilestoneConfigurationAllowsPlanningCreation(
                $this->user,
                101
            )
        );
    }

    public function testItReturnsFalseWhenOnePlanningIsDefinedAndConfigurationAllowsMonoMilestone(): void
    {
        $this->scrum_mono_milestone_dao->shouldReceive('isMonoMilestoneActivatedForProject')->andReturns([101]);
        $this->planning_factory->shouldReceive('getPlannings')->andReturns([1]);

        $this->assertFalse(
            $this->scrum_mono_milestone_checker->doesScrumMonoMilestoneConfigurationAllowsPlanningCreation(
                $this->user,
                101
            )
        );
    }

    public function testItReturnsFalseWhenTwoPlanningsAreDefinedAndConfigurationAllowsMonoMilestone(): void
    {
        $this->scrum_mono_milestone_dao->shouldReceive('isMonoMilestoneActivatedForProject')->andReturns([101]);
        $this->planning_factory->shouldReceive('getPlannings')->andReturns([1, 2]);

        $this->assertFalse(
            $this->scrum_mono_milestone_checker->doesScrumMonoMilestoneConfigurationAllowsPlanningCreation(
                $this->user,
                101
            )
        );
    }

    public function testItAlwaysReturnsTrueWhenMonoMilestoneIsDefinedInDb(): void
    {
        $this->scrum_mono_milestone_dao->shouldReceive('isMonoMilestoneActivatedForProject')->andReturns([101]);

        $this->assertTrue(
            $this->scrum_mono_milestone_checker->isScrumMonoMilestoneAvailable(
                $this->user,
                101
            )
        );
    }

    public function testItReturnsTrueWhenOnePlanningIsDefinedAndFeatureFlagIsActivated(): void
    {
        $this->scrum_mono_milestone_dao->shouldReceive('isMonoMilestoneActivatedForProject')->andReturns(false);
        $this->planning_factory->shouldReceive('getPlannings')->andReturns([1]);
        \ForgeConfig::set('feature_flag_allow_scrum_mono_milestone_usage', 1);

        $this->assertTrue(
            $this->scrum_mono_milestone_checker->isScrumMonoMilestoneAvailable(
                $this->user,
                101
            )
        );
    }

    public function testItReturnsFalseWhenOnePlanningIsDefinedAndFeatureFlagIsNotActivated(): void
    {
        $this->scrum_mono_milestone_dao->shouldReceive('isMonoMilestoneActivatedForProject')->andReturns(false);
        $this->planning_factory->shouldReceive('getPlannings')->andReturns([1]);
        \ForgeConfig::set('feature_flag_allow_scrum_mono_milestone_usage', 0);

        $this->assertFalse(
            $this->scrum_mono_milestone_checker->isScrumMonoMilestoneAvailable(
                $this->user,
                101
            )
        );
    }
}
