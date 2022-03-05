<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink\UpdateValue;

/**
 * @psalm-immutable
 */
final class ArtifactLinksFieldUpdateValue
{
    private function __construct(
        private ?ArtifactLinksDiff $artifact_links_diff,
        private ?CollectionOfArtifactLinks $submitted_values,
        private ?ArtifactLink $parent_artifact_link,
    ) {
    }

    public static function build(
        ?ArtifactLinksDiff $artifact_links_diff,
        ?CollectionOfArtifactLinks $submitted_values,
        ?ArtifactLink $parent_artifact_link,
    ): self {
        return new self(
            $artifact_links_diff,
            $submitted_values,
            $parent_artifact_link
        );
    }

    public function getArtifactLinksDiff(): ?ArtifactLinksDiff
    {
        return $this->artifact_links_diff;
    }

    public function getParentArtifactLink(): ?ArtifactLink
    {
        return $this->parent_artifact_link;
    }

    public function getSubmittedValues(): ?CollectionOfArtifactLinks
    {
        return $this->submitted_values;
    }
}
