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

namespace Tuleap\Tracker\Report\Query\Advanced\QueryBuilder;

use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\StatusOpenValueWrapper;
use Tuleap\Tracker\Report\Query\CommentFromWhereBuilder;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\BetweenValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentDateTimeValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentUserValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\InValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\MetadataValueWrapperParameters;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ValueWrapperParameters;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ValueWrapperVisitor;
use Tuleap\Tracker\Report\Query\IProvideFromAndWhereSQLFragments;

class MetadataEqualComparisonFromWhereBuilder implements MetadataComparisonFromWhereBuilder, ValueWrapperVisitor
{
    /**
     * @var CommentFromWhereBuilder
     */
    private $comment_from_where_builder;

    public function __construct(CommentFromWhereBuilder $comment_from_where_builder)
    {
        $this->comment_from_where_builder = $comment_from_where_builder;
    }

    public function getFromWhere(Metadata $metadata, Comparison $comparison)
    {
        $value = $comparison->getValueWrapper()->accept($this, new MetadataValueWrapperParameters($metadata));

        if ($value === '') {
            return $this->searchArtifactsWithoutComment($comparison);
        }

        return $this->searchComment($comparison, $value);
    }

    public function visitCurrentDateTimeValueWrapper(
        CurrentDateTimeValueWrapper $value_wrapper,
        ValueWrapperParameters $parameters,
    ) {
        throw new \RuntimeException("Metadata is not supported here.");
    }

    public function visitSimpleValueWrapper(SimpleValueWrapper $value_wrapper, ValueWrapperParameters $parameters)
    {
        return $value_wrapper->getValue();
    }

    public function visitBetweenValueWrapper(BetweenValueWrapper $value_wrapper, ValueWrapperParameters $parameters)
    {
        throw new \RuntimeException("Metadata is not supported here.");
    }

    public function visitInValueWrapper(
        InValueWrapper $collection_of_value_wrappers,
        ValueWrapperParameters $parameters,
    ) {
        throw new \RuntimeException("Metadata is not supported here.");
    }

    public function visitCurrentUserValueWrapper(
        CurrentUserValueWrapper $value_wrapper,
        ValueWrapperParameters $parameters,
    ) {
        throw new \RuntimeException("Metadata is not supported here.");
    }

    public function visitStatusOpenValueWrapper(
        StatusOpenValueWrapper $value_wrapper,
        ValueWrapperParameters $parameters,
    ) {
        throw new \RuntimeException("Metadata is not supported here.");
    }

    /**
     * @param            $value
     *
     * @return IProvideFromAndWhereSQLFragments
     */
    private function searchComment(Comparison $comparison, $value)
    {
        $suffix = spl_object_hash($comparison);

        return $this->comment_from_where_builder->getFromWhereWithComment($value, $suffix);
    }

    /**
     * @return IProvideFromAndWhereSQLFragments
     */
    private function searchArtifactsWithoutComment(Comparison $comparison)
    {
        $suffix = spl_object_hash($comparison);

        return $this->comment_from_where_builder->getFromWhereWithoutComment($suffix);
    }
}
