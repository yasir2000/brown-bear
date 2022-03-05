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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\GlobalLanguageMock;

final class ReferenceDescriptionTranslationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    public function testReferenceDescriptionNotLookingLikeAnInternalDescriptionIsNotTranslated(): void
    {
        $reference            = \Mockery::mock(\Reference::class);
        $expected_description = 'My reference description';
        $reference->shouldReceive('getDescription')->andReturn($expected_description);

        $reference_description_translation = new ReferenceDescriptionTranslation($reference);

        $this->assertEquals($expected_description, $reference_description_translation->getTranslatedDescription());
    }

    public function testPluginReferenceDescriptionIsTranslated(): void
    {
        $reference = \Mockery::mock(\Reference::class);
        $reference->shouldReceive('getDescription')->andReturn('plugin_aaaaa:myref_build_desc_key');

        $reference_description_translation = new ReferenceDescriptionTranslation($reference);

        $expected_translation = 'Plugin ref description';
        $GLOBALS['Language']->method('hasText')->willReturn(true);
        $GLOBALS['Language']->method('getOverridableText')->with('plugin_aaaaa', 'myref_build_desc_key')->willReturn($expected_translation);

        $this->assertEquals($expected_translation, $reference_description_translation->getTranslatedDescription());
    }

    public function testProjectReferenceDescriptionIsTranslated(): void
    {
        $reference = \Mockery::mock(\Reference::class);
        $reference->shouldReceive('getDescription')->andReturn('projectref_desc_key');

        $reference_description_translation = new ReferenceDescriptionTranslation($reference);

        $expected_translation = 'Project ref description';
        $GLOBALS['Language']->method('hasText')->willReturn(true);
        $GLOBALS['Language']->method('getOverridableText')->with('project_reference', 'projectref_desc_key')->willReturn($expected_translation);

        $this->assertEquals($expected_translation, $reference_description_translation->getTranslatedDescription());
    }

    /**
     * @testWith ["plugin_aaaaa:notfound_desc_key"]
     *           ["project_ref_notfound_desc_key"]
     */
    public function testDescriptionLookingLikeInternalDescriptionButNotExistingIsNotTranslated(string $raw_description): void
    {
        $reference = \Mockery::mock(\Reference::class);
        $reference->shouldReceive('getDescription')->andReturn($raw_description);

        $reference_description_translation = new ReferenceDescriptionTranslation($reference);

        $GLOBALS['Language']->method('hasText')->willReturn(false);

        $this->assertEquals($raw_description, $reference_description_translation->getTranslatedDescription());
    }
}
