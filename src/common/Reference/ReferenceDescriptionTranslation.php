<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\Reference;

final class ReferenceDescriptionTranslation
{
    /**
     * @var \Reference
     */
    private $reference;

    public function __construct(\Reference $reference)
    {
        $this->reference = $reference;
    }

    public function getTranslatedDescription(): string
    {
        $description = $this->reference->getDescription();

        if (strpos($description, '_desc_key') === false) {
            return $description;
        }

        $reference_matches = [];
        if (
            preg_match('/(.*):(.*)/', $description, $reference_matches) === 1 &&
            $GLOBALS['Language']->hasText($reference_matches[1], $reference_matches[2])
        ) {
            return $GLOBALS['Language']->getOverridableText($reference_matches[1], $reference_matches[2]);
        }

        if ($GLOBALS['Language']->hasText('project_reference', $description)) {
            return $GLOBALS['Language']->getOverridableText('project_reference', $description);
        }

        return $description;
    }
}
