<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Report\Query\Advanced\InvalidFields;

use BaseLanguageFactory;
use Tracker_FormElement_Field;
use Tracker_FormElement_Field_ArtifactId;
use Tracker_FormElement_Field_ArtifactLink;
use Tracker_FormElement_Field_Burndown;
use Tracker_FormElement_Field_Checkbox;
use Tracker_FormElement_Field_Computed;
use Tracker_FormElement_Field_CrossReferences;
use Tracker_FormElement_Field_Date;
use Tracker_FormElement_Field_File;
use Tracker_FormElement_Field_Float;
use Tracker_FormElement_Field_Integer;
use Tracker_FormElement_Field_LastModifiedBy;
use Tracker_FormElement_Field_LastUpdateDate;
use Tracker_FormElement_Field_MultiSelectbox;
use Tracker_FormElement_Field_OpenList;
use Tracker_FormElement_Field_PermissionsOnArtifact;
use Tracker_FormElement_Field_PerTrackerArtifactId;
use Tracker_FormElement_Field_Priority;
use Tracker_FormElement_Field_Radiobutton;
use Tracker_FormElement_Field_Selectbox;
use Tracker_FormElement_Field_String;
use Tracker_FormElement_Field_SubmittedBy;
use Tracker_FormElement_Field_SubmittedOn;
use Tracker_FormElement_Field_Text;
use Tracker_FormElement_FieldVisitor;
use Tuleap\Tracker\FormElement\TrackerFormElementExternalField;
use Tuleap\Tracker\Report\Query\Advanced\CollectionOfListValuesExtractor;
use Tuleap\Tracker\Report\Query\Advanced\ListFieldBindValueNormalizer;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\CollectionOfNormalizedBindLabelsExtractor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\ListFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\UgroupLabelConverter;

class InComparisonVisitor implements
    Tracker_FormElement_FieldVisitor,
    IProvideTheInvalidFieldCheckerForAComparison
{
    private static $OPERATOR = 'in()';

    /**
     * @return InvalidFieldChecker
     * @throws FieldIsNotSupportedForComparisonException
     */
    public function getInvalidFieldChecker(Tracker_FormElement_Field $field)
    {
        return $field->accept($this);
    }

    public function visitArtifactLink(Tracker_FormElement_Field_ArtifactLink $field)
    {
        throw new FieldIsNotSupportedAtAllException($field);
    }

    public function visitDate(Tracker_FormElement_Field_Date $field)
    {
        throw new FieldIsNotSupportedForComparisonException($field, self::$OPERATOR);
    }

    public function visitFile(Tracker_FormElement_Field_File $field)
    {
        throw new FieldIsNotSupportedForComparisonException($field, self::$OPERATOR);
    }

    public function visitFloat(Tracker_FormElement_Field_Float $field)
    {
        throw new FieldIsNotSupportedForComparisonException($field, self::$OPERATOR);
    }

    public function visitInteger(Tracker_FormElement_Field_Integer $field)
    {
        throw new FieldIsNotSupportedForComparisonException($field, self::$OPERATOR);
    }

    public function visitOpenList(Tracker_FormElement_Field_OpenList $field)
    {
        throw new FieldIsNotSupportedAtAllException($field);
    }

    public function visitPermissionsOnArtifact(Tracker_FormElement_Field_PermissionsOnArtifact $field)
    {
        throw new FieldIsNotSupportedAtAllException($field);
    }

    public function visitString(Tracker_FormElement_Field_String $field)
    {
        throw new FieldIsNotSupportedForComparisonException($field, self::$OPERATOR);
    }

    public function visitText(Tracker_FormElement_Field_Text $field)
    {
        throw new FieldIsNotSupportedForComparisonException($field, self::$OPERATOR);
    }

    public function visitRadiobutton(Tracker_FormElement_Field_Radiobutton $field)
    {
        return $this->visitList();
    }

    public function visitCheckbox(Tracker_FormElement_Field_Checkbox $field)
    {
        return $this->visitList();
    }

    public function visitMultiSelectbox(Tracker_FormElement_Field_MultiSelectbox $field)
    {
        return $this->visitList();
    }

    public function visitSelectbox(Tracker_FormElement_Field_Selectbox $field)
    {
        return $this->visitList();
    }

    private function visitList()
    {
        $list_field_bind_value_normalizer = new ListFieldBindValueNormalizer();
        $ugroup_label_converter           = new UgroupLabelConverter(
            $list_field_bind_value_normalizer,
            new BaseLanguageFactory()
        );

        return new ListFieldChecker(
            new EmptyStringForbidden(),
            new CollectionOfListValuesExtractor(),
            $list_field_bind_value_normalizer,
            new CollectionOfNormalizedBindLabelsExtractor(
                $list_field_bind_value_normalizer,
                $ugroup_label_converter
            ),
            $ugroup_label_converter
        );
    }

    public function visitSubmittedBy(Tracker_FormElement_Field_SubmittedBy $field)
    {
        return $this->visitList();
    }

    public function visitLastModifiedBy(Tracker_FormElement_Field_LastModifiedBy $field)
    {
        return $this->visitList();
    }

    public function visitArtifactId(Tracker_FormElement_Field_ArtifactId $field)
    {
        throw new FieldIsNotSupportedAtAllException($field);
    }

    public function visitPerTrackerArtifactId(Tracker_FormElement_Field_PerTrackerArtifactId $field)
    {
        throw new FieldIsNotSupportedAtAllException($field);
    }

    public function visitCrossReferences(Tracker_FormElement_Field_CrossReferences $field)
    {
        throw new FieldIsNotSupportedAtAllException($field);
    }

    public function visitBurndown(Tracker_FormElement_Field_Burndown $field)
    {
        throw new FieldIsNotSupportedAtAllException($field);
    }

    public function visitLastUpdateDate(Tracker_FormElement_Field_LastUpdateDate $field)
    {
        throw new FieldIsNotSupportedForComparisonException($field, self::$OPERATOR);
    }

    public function visitSubmittedOn(Tracker_FormElement_Field_SubmittedOn $field)
    {
        throw new FieldIsNotSupportedForComparisonException($field, self::$OPERATOR);
    }

    public function visitComputed(Tracker_FormElement_Field_Computed $field)
    {
        throw new FieldIsNotSupportedAtAllException($field);
    }

    public function visitExternalField(TrackerFormElementExternalField $element)
    {
        throw new FieldIsNotSupportedAtAllException($element);
    }

    public function visitPriority(Tracker_FormElement_Field_Priority $field)
    {
        throw new FieldIsNotSupportedAtAllException($field);
    }
}
