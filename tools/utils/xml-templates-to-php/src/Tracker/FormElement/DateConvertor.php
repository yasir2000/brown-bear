<?php
/**
 * Copyright (c) BrownBear, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Tools\Xml2Php\Tracker\FormElement;

use PhpParser\Node\Expr\MethodCall;
use Psr\Log\LoggerInterface;

class DateConvertor extends FieldConvertor
{
    protected function buildWith(LoggerInterface $logger, IdToNameMapping $id_to_name_mapping): self
    {
        parent::buildWith($logger, $id_to_name_mapping);

        return $this->withDateTime();
    }

    private function withDateTime(): self
    {
        $properties = $this->xml->properties;
        if (! $properties) {
            return $this;
        }

        if ((string) $properties['display_time'] === '1') {
            $this->current_expr = new MethodCall(
                $this->current_expr,
                'withDateTime'
            );
        }

        return $this;
    }
}
