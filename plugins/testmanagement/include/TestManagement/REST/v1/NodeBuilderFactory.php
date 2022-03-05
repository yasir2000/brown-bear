<?php
/**
 * Copyright (c) Enalean, 2015-present. All Rights Reserved.
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
 * MERCHANTABILITY or FITNEsemantic_status FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\TestManagement\REST\v1;

use PFUser;
use Tracker_ArtifactDao;
use Tracker_ArtifactFactory;
use Tracker_ResourceDoesntExistException;
use Tracker_URLVerification;
use Tuleap\REST\ProjectAuthorization;
use Tuleap\TestManagement\ArtifactDao;
use Tuleap\TestManagement\ArtifactFactory;
use Tuleap\TestManagement\Config;
use Tuleap\TestManagement\Dao as TestManagementDao;
use Tuleap\Tracker\Artifact\Artifact;

class NodeBuilderFactory
{
    /** @var ArtifactFactory */
    private $testmanagement_artifact_factory;

    /** @var ArtifactNodeBuilder */
    private $artifact_builder;

    public function __construct()
    {
        $dao    = new TestManagementDao();
        $config = new Config($dao, \TrackerFactory::instance());

        $this->testmanagement_artifact_factory = new ArtifactFactory(
            $config,
            Tracker_ArtifactFactory::instance(),
            new ArtifactDao()
        );

        $this->artifact_builder = new ArtifactNodeBuilder(
            new Tracker_ArtifactDao(),
            new ArtifactNodeDao(),
            $this
        );
    }

    public function getNodeRepresentation(PFUser $user, Artifact $artifact): NodeRepresentation
    {
        return $this->artifact_builder->getNodeRepresentation($user, $artifact);
    }


    /**
     * @param int $id
     *
     * @return Artifact
     */
    public function getArtifactById(PFUser $user, $id)
    {
        $artifact = $this->testmanagement_artifact_factory->getArtifactByIdUserCanView($user, $id);
        if ($artifact) {
            ProjectAuthorization::userCanAccessProject(
                $user,
                $artifact->getTracker()->getProject(),
                new Tracker_URLVerification()
            );
            return $artifact;
        }
        throw new Tracker_ResourceDoesntExistException();
    }
}
