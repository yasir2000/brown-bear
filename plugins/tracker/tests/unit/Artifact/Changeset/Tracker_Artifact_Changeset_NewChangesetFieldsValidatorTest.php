<?php
/**
 * Copyright (c) Enalean, 2014 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Changeset\Validation\ChangesetWithFieldsValidationContext;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Validation\ManualActionContext;

final class Tracker_Artifact_Changeset_NewChangesetFieldsValidatorTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /** @var Tracker_Artifact_Changeset_NewChangesetFieldsValidator */
    private $new_changeset_fields_validator;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tuleap\Tracker\FormElement\ArtifactLinkValidator
     */
    private $artifact_link_validator;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_FormElementFactory
     */
    private $factory;

    /** @var Workflow */
    private $workflow;

    /** @var Artifact */
    private $artifact;

    /** @var Tracker_FormElement_Field */
    private $field1;

    /** @var Tracker_FormElement_Field */
    private $field2;

    /** @var Tracker_FormElement_Field */
    private $field3;

    /** @var Tracker_Artifact_Changeset */
    private $changeset;

    /** @var Tracker_Artifact_ChangesetValue */
    private $changeset_value1;

    /** @var Tracker_Artifact_ChangesetValue */
    private $changeset_value2;

    /** @var Tracker_Artifact_ChangesetValue */
    private $changeset_value3;

    protected function setUp(): void
    {
        $this->factory                 = \Mockery::spy(\Tracker_FormElementFactory::class);
        $this->artifact_link_validator = \Mockery::mock(\Tuleap\Tracker\FormElement\ArtifactLinkValidator::class);
        $workflow_checker              = \Mockery::mock(\Tuleap\Tracker\Workflow\WorkflowUpdateChecker::class);
        $workflow_checker->shouldReceive('canFieldBeUpdated')->andReturnTrue();
        $this->new_changeset_fields_validator = new Tracker_Artifact_Changeset_NewChangesetFieldsValidator(
            $this->factory,
            $this->artifact_link_validator,
            $workflow_checker
        );

        $this->field1 = $this->getFieldWithId(101);
        $this->field2 = $this->getFieldWithId(102);
        $this->field3 = $this->getFieldWithId(103);

        $this->factory->shouldReceive('getAllFormElementsForTracker')->andReturns([]);
        $this->factory->shouldReceive('getUsedFields')
            ->andReturns([$this->field1, $this->field2, $this->field3])
            ->byDefault();

        $this->workflow = \Mockery::spy(\Workflow::class);

        $this->changeset        = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $this->changeset_value1 = \Mockery::spy(\Tracker_Artifact_ChangesetValue::class);
        $this->changeset_value2 = \Mockery::spy(\Tracker_Artifact_ChangesetValue::class);
        $this->changeset_value3 = \Mockery::spy(\Tracker_Artifact_ChangesetValue::class);
        $this->changeset->shouldReceive('getValue')->with($this->field1)->andReturns($this->changeset_value1);

        $this->artifact = Mockery::mock(Artifact::class);
        $this->artifact->shouldReceive('getTracker')->andReturns(\Mockery::spy(\Tracker::class));
        $this->artifact->shouldReceive('getWorkflow')->andReturns($this->workflow);
        $this->artifact->shouldReceive('getLastChangeset')->andReturns($this->changeset);
    }

    /**
     * @return \Mockery\Mock|Tracker_FormElement_Field
     */
    private function getFieldWithId(int $id)
    {
        $field = \Mockery::mock(\Tracker_FormElement_Field::class)->makePartial(
        )->shouldAllowMockingProtectedMethods();
        $field->shouldReceive('getId')->andReturn($id);

        return $field;
    }

    public function testValidateUpdateFieldSubmitted(): void
    {
        $this->field1->shouldReceive('isValid')->andReturns(true);
        $this->field1->shouldReceive('userCanUpdate')->andReturns(true);
        $this->field2->shouldReceive('isValid')->andReturns(true);
        $this->field2->shouldReceive('userCanUpdate')->andReturns(true);
        $this->field3->shouldReceive('isValid')->andReturns(true);
        $this->field3->shouldReceive('userCanUpdate')->andReturns(true);

        $this->workflow->shouldReceive('validate')->andReturns(true);

        $user        = \Mockery::spy(\PFUser::class);
        $fields_data = ['101' => 666];
        $this->assertTrue(
            $this->new_changeset_fields_validator->validate(
                $this->artifact,
                $user,
                $fields_data,
                new \Tuleap\Tracker\Changeset\Validation\NullChangesetValidationContext()
            )
        );
        $this->assertNotNull($fields_data[101]);
        $this->assertEquals(666, $fields_data[101]);
    }

    public function testValidateUpdateFieldNotSubmitted(): void
    {
        $this->field1->shouldReceive('isValid')->andReturns(true);
        $this->field1->shouldReceive('userCanUpdate')->andReturns(false);
        $this->field1->shouldReceive('isRequired')->andReturns(true);
        $this->field2->shouldReceive('isValid')->andReturns(true);
        $this->field2->shouldReceive('userCanUpdate')->andReturns(false);
        $this->field3->shouldReceive('isValid')->andReturns(true);
        $this->field3->shouldReceive('userCanUpdate')->andReturns(false);

        $this->workflow->shouldReceive('validate')->andReturns(true);
        $this->changeset_value1->shouldReceive('getValue')->andReturns(999);

        $user        = \Mockery::spy(\PFUser::class);
        $fields_data = [];
        $this->assertTrue(
            $this->new_changeset_fields_validator->validate(
                $this->artifact,
                $user,
                $fields_data,
                new \Tuleap\Tracker\Changeset\Validation\NullChangesetValidationContext()
            )
        );
        $this->assertFalse(isset($fields_data[101]));
    }

    public function testValidateFieldsMissingFieldsOnUpdate(): void
    {
        $this->field1->shouldReceive('isValid')->andReturns(true);
        $this->field1->shouldReceive('userCanUpdate')->andReturns(true);
        $this->field2->shouldReceive('isValid')->andReturns(true);
        $this->field2->shouldReceive('userCanUpdate')->andReturns(true);
        $this->field3->shouldReceive('isValid')->andReturns(true);
        $this->field3->shouldReceive('userCanUpdate')->andReturns(true);
        $this->workflow->shouldReceive('validate')->andReturns(true);

        $this->changeset->shouldReceive('getValue')->with($this->field2)->andReturns($this->changeset_value2);
        $this->changeset->shouldReceive('getValue')->with($this->field3)->andReturns($this->changeset_value3);

        $user = \Mockery::spy(\PFUser::class);
        // field 102 is missing
        $fields_data = [
            '101' => 'foo',
            '103' => 'bar',
        ];
        $this->assertTrue(
            $this->new_changeset_fields_validator->validate(
                $this->artifact,
                $user,
                $fields_data,
                new \Tuleap\Tracker\Changeset\Validation\NullChangesetValidationContext()
            )
        );
        $this->assertFalse(isset($fields_data[102]));
    }

    public function testValidateFieldsMissingFieldsInPreviousChangesetOnUpdate(): void
    {
        $this->field1->shouldReceive('isValid')->andReturns(true);
        $this->field1->shouldReceive('userCanUpdate')->andReturns(true);
        $this->field2->shouldReceive('isValid')->andReturns(true);
        $this->field2->shouldReceive('userCanUpdate')->andReturns(true);
        $this->field3->shouldReceive('isValid')->andReturns(true);
        $this->field3->shouldReceive('userCanUpdate')->andReturns(true);
        $this->workflow->shouldReceive('validate')->andReturns(true);

        $this->changeset->shouldReceive('getValue')->with($this->field2)->andReturns(null);
        $this->changeset->shouldReceive('getValue')->with($this->field3)->andReturns($this->changeset_value3);

        $user = \Mockery::spy(\PFUser::class);
        // field 102 is missing
        $fields_data = [
            '101' => 'foo',
            '103' => 'bar',
        ];
        $this->assertTrue(
            $this->new_changeset_fields_validator->validate(
                $this->artifact,
                $user,
                $fields_data,
                new \Tuleap\Tracker\Changeset\Validation\NullChangesetValidationContext()
            )
        );
        $this->assertFalse(isset($fields_data[102]));
    }

    public function testItValidatesArtifactLinkField(): void
    {
        $this->artifact_link_validator->shouldReceive('isValid')->once()->andReturn(true);

        $artifact_link_field = Mockery::mock(Tracker_FormElement_Field_ArtifactLink::class);
        $artifact_link_field->shouldReceive('getId')->andReturn(101);
        $artifact_link_field->shouldReceive('userCanUpdate')->andReturnTrue();
        $artifact_link_field->shouldReceive('validateFieldWithPermissionsAndRequiredStatus')->andReturnTrue();
        $this->factory->shouldReceive('getUsedFields')->andReturns([$artifact_link_field]);

        $user        = UserTestBuilder::aUser()->build();
        $fields_data = [
            '101' => ['new_values' => '184'],
        ];
        $context     = new ChangesetWithFieldsValidationContext(new ManualActionContext());
        $this->assertTrue(
            $this->new_changeset_fields_validator->validate(
                $this->artifact,
                $user,
                $fields_data,
                $context
            )
        );
    }
}
