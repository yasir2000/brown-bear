<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Project\Registration;

use Tuleap\Project\Admin\DescriptionFields\DescriptionFieldLabelBuilder;
use Tuleap\Project\DefaultProjectVisibilityRetriever;
use Tuleap\Project\DescriptionFieldsFactory;
use Tuleap\Project\Registration\Template\CategorisedTemplate;
use Tuleap\Project\Registration\Template\CompanyTemplate;
use Tuleap\Project\Registration\Template\CategorisedTemplatePresenter;
use Tuleap\Project\Registration\Template\ProjectTemplate;
use Tuleap\Project\Registration\Template\TemplateFactory;
use Tuleap\Project\Registration\Template\TemplatePresenter;

class ProjectRegistrationPresenterBuilder
{
    /**
     * @var TemplateFactory
     */
    private $template_factory;
    /**
     * @var DefaultProjectVisibilityRetriever
     */
    private $default_project_visibility_retriever;
    /**
     * @var \TroveCatFactory
     */
    private $trove_cat_factory;
    /**
     * @var DescriptionFieldsFactory
     */
    private $fields_factory;

    public function __construct(
        TemplateFactory $template_factory,
        DefaultProjectVisibilityRetriever $default_project_visibility_retriever,
        \TroveCatFactory $trove_cat_factory,
        DescriptionFieldsFactory $fields_factory,
    ) {
        $this->template_factory                     = $template_factory;
        $this->default_project_visibility_retriever = $default_project_visibility_retriever;
        $this->trove_cat_factory                    = $trove_cat_factory;
        $this->fields_factory                       = $fields_factory;
    }

    public function buildPresenter(): ProjectRegistrationPresenter
    {
        $company_templates = array_map(
            static function (CompanyTemplate $project_template) {
                return new TemplatePresenter($project_template);
            },
            $this->template_factory->getCompanyTemplateList()
        );

        $formatted_field = [];
        $fields          = $this->fields_factory->getAllDescriptionFields();
        foreach ($fields as $field) {
            $formatted_field[] = [
                'group_desc_id'    => (string) $field['group_desc_id'],
                'desc_name'        => DescriptionFieldLabelBuilder::getFieldTranslatedName($field['desc_name']),
                'desc_type'        => $field['desc_type'],
                'desc_description' => DescriptionFieldLabelBuilder::getFieldTranslatedDescription($field['desc_description']),
                'desc_required'    => (string) $field['desc_required'],
            ];
        }

        $categorised_external_templates = $this->template_factory->getCategorisedExternalTemplates();
        return new ProjectRegistrationPresenter(
            $this->default_project_visibility_retriever->getDefaultProjectVisibility(),
            $this->trove_cat_factory->getMandatoryParentCategoriesUnderRootOnlyWhenCategoryHasChildren(),
            $formatted_field,
            $company_templates,
            array_map(
                static fn (ProjectTemplate $project_template) => new TemplatePresenter($project_template),
                $this->template_factory->getValidTemplates()
            ),
            array_map(
                static fn(CategorisedTemplate $external_template) => new CategorisedTemplatePresenter($external_template),
                $categorised_external_templates
            )
        );
    }
}
