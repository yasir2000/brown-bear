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

namespace Tuleap\CrossTracker\Report\Query\Advanced;

use Tracker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\BetweenComparisonFromWhereBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\EqualComparisonFromWhereBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\GreaterThanComparisonFromWhereBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\GreaterThanOrEqualComparisonFromWhereBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\InComparisonFromWhereBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\LesserThanComparisonFromWhereBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\LesserThanOrEqualComparisonFromWhereBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\NotEqualComparisonFromWhereBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\NotInComparisonFromWhereBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\SearchableVisitor;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\SearchableVisitorParameters;
use Tuleap\CrossTracker\Report\Query\IProvideParametrizedFromAndWhereSQLFragments;
use Tuleap\CrossTracker\Report\Query\ParametrizedAndFromWhere;
use Tuleap\CrossTracker\Report\Query\ParametrizedOrFromWhere;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\AndExpression;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\AndOperand;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\BetweenComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\EqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\GreaterThanComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\GreaterThanOrEqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\InComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\LesserThanComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\LesserThanOrEqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\NotEqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\NotInComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\OrExpression;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\OrOperand;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Visitable;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Visitor;

class QueryBuilderVisitor implements Visitor
{
    /** @var EqualComparisonFromWhereBuilder */
    private $equal_comparison_from_where_builder;

    /** @var SearchableVisitor */
    private $searchable_visitor;

    /** @var NotEqualComparisonFromWhereBuilder */
    private $not_equal_comparison_from_where_builder;

    /** @var GreaterThanComparisonFromWhereBuilder */
    private $greater_than_comparison_from_where_builder;

    /** @var GreaterThanOrEqualComparisonFromWhereBuilder */
    private $greater_than_or_equal_comparison_from_where_builder;

    /** @var LesserThanComparisonFromWhereBuilder */
    private $lesser_than_comparison_from_where_builder;

    /** @var LesserThanOrEqualComparisonFromWhereBuilder */
    private $lesser_than_or_equal_comparison_from_where_builder;

    /** @var BetweenComparisonFromWhereBuilder */
    private $between_comparison_from_where_builder;

    /** @var InComparisonFromWhereBuilder */
    private $in_comparison_from_where_builder;

    /** @var NotInComparisonFromWhereBuilder */
    private $not_in_comparison_from_where_builder;

    public function __construct(
        SearchableVisitor $searchable_visitor,
        EqualComparisonFromWhereBuilder $equal_comparison_from_where_builder,
        NotEqualComparisonFromWhereBuilder $not_equal_comparison_from_where_builder,
        GreaterThanComparisonFromWhereBuilder $greater_than_comparison_from_where_builder,
        GreaterThanOrEqualComparisonFromWhereBuilder $greater_than_or_equal_comparison_from_where_builder,
        LesserThanComparisonFromWhereBuilder $lesser_than_comparison_from_where_builder,
        LesserThanOrEqualComparisonFromWhereBuilder $lesser_than_or_equal_comparison_from_where_builder,
        BetweenComparisonFromWhereBuilder $between_comparison_from_where_builder,
        InComparisonFromWhereBuilder $in_comparison_from_where_builder,
        NotInComparisonFromWhereBuilder $not_in_comparison_from_where_builder,
    ) {
        $this->searchable_visitor                                  = $searchable_visitor;
        $this->equal_comparison_from_where_builder                 = $equal_comparison_from_where_builder;
        $this->not_equal_comparison_from_where_builder             = $not_equal_comparison_from_where_builder;
        $this->greater_than_comparison_from_where_builder          = $greater_than_comparison_from_where_builder;
        $this->greater_than_or_equal_comparison_from_where_builder = $greater_than_or_equal_comparison_from_where_builder;
        $this->lesser_than_comparison_from_where_builder           = $lesser_than_comparison_from_where_builder;
        $this->lesser_than_or_equal_comparison_from_where_builder  = $lesser_than_or_equal_comparison_from_where_builder;
        $this->between_comparison_from_where_builder               = $between_comparison_from_where_builder;
        $this->in_comparison_from_where_builder                    = $in_comparison_from_where_builder;
        $this->not_in_comparison_from_where_builder                = $not_in_comparison_from_where_builder;
    }

    /**
     * @param Tracker[] $trackers
     * @return IProvideParametrizedFromAndWhereSQLFragments
     */
    public function buildFromWhere(Visitable $parsed_query, array $trackers)
    {
        return $parsed_query->accept($this, new QueryBuilderVisitorParameters($trackers));
    }

    public function visitEqualComparison(EqualComparison $comparison, QueryBuilderVisitorParameters $parameters)
    {
        return $comparison->getSearchable()->accept(
            $this->searchable_visitor,
            new SearchableVisitorParameters(
                $comparison,
                $this->equal_comparison_from_where_builder,
                $parameters->getTrackers()
            )
        );
    }

    public function visitNotEqualComparison(NotEqualComparison $comparison, QueryBuilderVisitorParameters $parameters)
    {
        return $comparison->getSearchable()->accept(
            $this->searchable_visitor,
            new SearchableVisitorParameters(
                $comparison,
                $this->not_equal_comparison_from_where_builder,
                $parameters->getTrackers()
            )
        );
    }

    public function visitLesserThanComparison(
        LesserThanComparison $comparison,
        QueryBuilderVisitorParameters $parameters,
    ) {
        return $comparison->getSearchable()->accept(
            $this->searchable_visitor,
            new SearchableVisitorParameters(
                $comparison,
                $this->lesser_than_comparison_from_where_builder,
                $parameters->getTrackers()
            )
        );
    }

    public function visitGreaterThanComparison(
        GreaterThanComparison $comparison,
        QueryBuilderVisitorParameters $parameters,
    ) {
        return $comparison->getSearchable()->accept(
            $this->searchable_visitor,
            new SearchableVisitorParameters(
                $comparison,
                $this->greater_than_comparison_from_where_builder,
                $parameters->getTrackers()
            )
        );
    }

    public function visitLesserThanOrEqualComparison(
        LesserThanOrEqualComparison $comparison,
        QueryBuilderVisitorParameters $parameters,
    ) {
        return $comparison->getSearchable()->accept(
            $this->searchable_visitor,
            new SearchableVisitorParameters(
                $comparison,
                $this->lesser_than_or_equal_comparison_from_where_builder,
                $parameters->getTrackers()
            )
        );
    }

    public function visitGreaterThanOrEqualComparison(
        GreaterThanOrEqualComparison $comparison,
        QueryBuilderVisitorParameters $parameters,
    ) {
        return $comparison->getSearchable()->accept(
            $this->searchable_visitor,
            new SearchableVisitorParameters(
                $comparison,
                $this->greater_than_or_equal_comparison_from_where_builder,
                $parameters->getTrackers()
            )
        );
    }

    public function visitBetweenComparison(BetweenComparison $comparison, QueryBuilderVisitorParameters $parameters)
    {
        return $comparison->getSearchable()->accept(
            $this->searchable_visitor,
            new SearchableVisitorParameters(
                $comparison,
                $this->between_comparison_from_where_builder,
                $parameters->getTrackers()
            )
        );
    }

    public function visitInComparison(InComparison $comparison, QueryBuilderVisitorParameters $parameters)
    {
        return $comparison->getSearchable()->accept(
            $this->searchable_visitor,
            new SearchableVisitorParameters(
                $comparison,
                $this->in_comparison_from_where_builder,
                $parameters->getTrackers()
            )
        );
    }

    public function visitNotInComparison(NotInComparison $comparison, QueryBuilderVisitorParameters $parameters)
    {
        return $comparison->getSearchable()->accept(
            $this->searchable_visitor,
            new SearchableVisitorParameters(
                $comparison,
                $this->not_in_comparison_from_where_builder,
                $parameters->getTrackers()
            )
        );
    }

    public function visitAndExpression(AndExpression $and_expression, QueryBuilderVisitorParameters $parameters)
    {
        $from_where_expression = $and_expression->getExpression()->accept($this, $parameters);

        $tail = $and_expression->getTail();

        return $this->buildAndClause($parameters, $tail, $from_where_expression);
    }

    public function visitOrExpression(OrExpression $or_expression, QueryBuilderVisitorParameters $parameters)
    {
        $from_where_expression = $or_expression->getExpression()->accept($this, $parameters);

        $tail = $or_expression->getTail();

        return $this->buildOrClause($parameters, $tail, $from_where_expression);
    }

    public function visitOrOperand(OrOperand $or_operand, QueryBuilderVisitorParameters $parameters)
    {
        $from_where_expression = $or_operand->getOperand()->accept($this, $parameters);

        $tail = $or_operand->getTail();

        return $this->buildOrClause($parameters, $tail, $from_where_expression);
    }

    public function visitAndOperand(AndOperand $and_operand, QueryBuilderVisitorParameters $parameters)
    {
        $from_where_expression = $and_operand->getOperand()->accept($this, $parameters);

        $tail = $and_operand->getTail();

        return $this->buildAndClause($parameters, $tail, $from_where_expression);
    }

    private function buildAndClause(QueryBuilderVisitorParameters $parameters, $tail, $from_where_expression)
    {
        if (! $tail) {
            return $from_where_expression;
        }

        $from_where_tail = $tail->accept($this, $parameters);

        return new ParametrizedAndFromWhere($from_where_expression, $from_where_tail);
    }

    private function buildOrClause(QueryBuilderVisitorParameters $parameters, $tail, $from_where_expression)
    {
        if (! $tail) {
            return $from_where_expression;
        }

        $from_where_tail = $tail->accept($this, $parameters);

        return new ParametrizedOrFromWhere($from_where_expression, $from_where_tail);
    }
}
