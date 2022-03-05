<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\FRS;

use ForgeConfig;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementInterface;
use Tuleap\FRS\REST\v1\ReleaseRepresentation;
use Tuleap\Markdown\ContentInterpretor;

class ReleasePresenter
{
    /**
     * @var \Tuleap\FRS\REST\v1\ReleaseRepresentation
     */
    public $release_representation;
    /**
     * @psalm-readonly
     */
    public string $release_note_html;

    /** @var string */
    public $language;

    /** @var string */
    public $platform_license_info;

    /** @var string */
    public $custom_license_agreement;
    public string $changelog_html;

    public function __construct(
        ReleaseRepresentation $release_representation,
        string $language,
        LicenseAgreementInterface $agreement,
        ContentInterpretor $interpreter,
    ) {
        $this->release_representation = json_encode($release_representation);
        $this->release_note_html      = $interpreter->getInterpretedContentWithReferences($release_representation->release_note, $release_representation->project->id);
        $this->changelog_html         = $interpreter->getInterpretedContentWithReferences($release_representation->changelog, $release_representation->project->id);
        $this->language               = $language;

        $platform_license_info = [
            "exchange_policy_url" => ForgeConfig::get('sys_exchange_policy_url'),
            "organisation_name"   => ForgeConfig::get('sys_org_name'),
            "contact_email"       => ForgeConfig::get('sys_email_contact'),
        ];

        $this->platform_license_info = json_encode($platform_license_info);

        $this->custom_license_agreement = $agreement->getAsJson();
    }

    public function getTemplateName()
    {
        return 'release';
    }
}
