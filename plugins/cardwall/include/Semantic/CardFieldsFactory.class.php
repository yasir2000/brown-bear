<?php
/**
* Copyright BrownBear (c) 2013 - Present. All rights reserved.
* Tuleap and BrownBear names and logos are registrated trademarks owned by
* BrownBear SAS. All other trademarks or names are properties of their respective
* owners.
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

use Tuleap\Cardwall\Semantic\CardFieldXmlExtractor;
use Tuleap\Tracker\Semantic\IBuildSemanticFromXML;

class Cardwall_Semantic_CardFieldsFactory implements IBuildSemanticFromXML
{
    public function getInstanceFromXML(
        SimpleXMLElement $current_semantic_xml,
        SimpleXMLElement $all_semantics_xml,
        array $xml_mapping,
        Tracker $tracker,
        array $tracker_mapping,
    ): Tracker_Semantic {
        $extractor        = new CardFieldXmlExtractor();
        $fields           = $extractor->extractFieldFromXml($current_semantic_xml, $xml_mapping);
        $background_color = $extractor->extractBackgroundColorFromXml($current_semantic_xml, $xml_mapping);

        $semantic = Cardwall_Semantic_CardFields::load($tracker);
        $semantic->setFields($fields);
        $semantic->setBackgroundColorField($background_color);

        return $semantic;
    }
}
