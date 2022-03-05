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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink\Type;

use DataAccessObject;

class TypeDao extends DataAccessObject
{
    public function create($shortname, $forward_label, $reverse_label)
    {
        $type          = $this->getTypeByShortname($shortname);
        $shortname     = $this->da->quoteSmart($shortname);
        $forward_label = $this->da->quoteSmart($forward_label);
        $reverse_label = $this->da->quoteSmart($reverse_label);

        $this->da->startTransaction();

        if ($type->count() > 0) {
            $this->rollBack();
            throw new UnableToCreateTypeException(
                sprintf(dgettext('tuleap-tracker', 'a type with %1$s as shortname already exists.'), $shortname)
            );
        }

        $sql = "INSERT INTO plugin_tracker_artifactlink_natures (shortname, forward_label, reverse_label)
                VALUES ($shortname, $forward_label, $reverse_label)";

        if (! $this->update($sql)) {
            $this->rollBack();
            return false;
        }

        $this->commit();
        return true;
    }

    public function getTypeByShortname($shortname)
    {
        $shortname = $this->da->quoteSmart($shortname);

        $sql = "SELECT * FROM plugin_tracker_artifactlink_natures WHERE shortname = $shortname";

        return $this->retrieve($sql);
    }

    public function edit($shortname, $forward_label, $reverse_label)
    {
        $shortname     = $this->da->quoteSmart($shortname);
        $forward_label = $this->da->quoteSmart($forward_label);
        $reverse_label = $this->da->quoteSmart($reverse_label);

        $sql = "UPDATE plugin_tracker_artifactlink_natures
                   SET forward_label = $forward_label, reverse_label = $reverse_label
                WHERE shortname = $shortname";

        return $this->update($sql);
    }

    public function delete($shortname)
    {
        $this->enableExceptionsOnError();
        $this->startTransaction();

        $this->deleteTypeInTableColumns($shortname);
        $this->purgeDeletedTypeInArtifactLinkTypeUsage($shortname);

        $shortname = $this->da->quoteSmart($shortname);
        $sql       = "DELETE FROM plugin_tracker_artifactlink_natures WHERE shortname = $shortname";

        $this->update($sql);

        $this->commit();
        return true;
    }

    private function purgeDeletedTypeInArtifactLinkTypeUsage($type_shortname)
    {
        $type_shortname = $this->da->quoteSmart($type_shortname);

        $sql = "DELETE FROM plugin_tracker_projects_unused_artifactlink_types
                WHERE type_shortname = $type_shortname";

        return $this->update($sql);
    }

    private function deleteTypeInTableColumns($shortname)
    {
        $shortname = $this->da->quoteSmart($shortname);

        $sql = "DELETE FROM tracker_report_renderer_table_columns WHERE artlink_nature = $shortname";

        return $this->update($sql);
    }

    public function isOrHasBeenUsed($shortname)
    {
        $shortname = $this->da->quoteSmart($shortname);

        $sql = "SELECT 1
                  FROM tracker_changeset_value_artifactlink
                 WHERE nature = $shortname
                 LIMIT 1";

        $dar = $this->retrieve($sql);
        return $dar && count($dar) !== 0;
    }

    public function searchAllUsedTypesByProject($project_id)
    {
        $project_id = $this->da->escapeInt($project_id);

        $sql = "SELECT DISTINCT nature
                  FROM tracker_changeset_value_artifactlink
                 WHERE group_id = $project_id
                 ORDER BY nature ASC";

        return $this->da->query($sql);
    }

    public function searchAll()
    {
        $sql = "SELECT *
                FROM plugin_tracker_artifactlink_natures
                ORDER BY shortname ASC";

        return $this->retrieve($sql);
    }

    public function getFromShortname($shortname)
    {
        $shortname = $this->da->quoteSmart($shortname);

        $sql = "SELECT *
                FROM plugin_tracker_artifactlink_natures
                WHERE shortname = $shortname";

        return $this->retrieveFirstRow($sql);
    }

    public function searchForwardTypeShortNamesForGivenArtifact($artifact_id)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);

        $sql = "SELECT DISTINCT IFNULL(artlink.nature, '') AS shortname
                FROM tracker_artifact parent_art
                    INNER JOIN tracker_field                        AS f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
                    INNER JOIN tracker_changeset_value              AS cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                    INNER JOIN tracker_changeset_value_artifactlink AS artlink    ON (artlink.changeset_value_id = cv.id)
                    INNER JOIN tracker_artifact                     AS linked_art ON (linked_art.id = artlink.artifact_id)
                    INNER JOIN tracker                              AS t          ON (t.id = linked_art.tracker_id)
                    INNER JOIN `groups` ON (`groups`.group_id = t.group_id)
                WHERE parent_art.id  = $artifact_id
                    AND `groups`.status = 'A'";

        return $this->retrieve($sql);
    }

    public function searchReverseTypeShortNamesForGivenArtifact($artifact_id)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);

        $sql = "SELECT DISTINCT IFNULL(artlink.nature, '') AS shortname
                FROM tracker_artifact parent_art
                    INNER JOIN tracker_field                        AS f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
                    INNER JOIN tracker_changeset_value              AS cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                    INNER JOIN tracker_changeset_value_artifactlink AS artlink    ON (artlink.changeset_value_id = cv.id)
                    INNER JOIN tracker_artifact                     AS linked_art ON (linked_art.id = artlink.artifact_id)
                    INNER JOIN tracker                              AS t          ON (t.id = parent_art.tracker_id)
                    INNER JOIN `groups` ON (`groups`.group_id = t.group_id)
                WHERE linked_art.id  = $artifact_id
                    AND `groups`.status = 'A'";

        return $this->retrieve($sql);
    }

    public function getForwardLinkedArtifactIds($artifact_id, $type, $limit, $offset)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);
        $type        = $this->da->quoteSmart($type);
        $limit       = $this->da->escapeInt($limit);
        $offset      = $this->da->escapeInt($offset);

        $sql = "SELECT SQL_CALC_FOUND_ROWS artlink.artifact_id AS id
                FROM tracker_artifact parent_art
                    INNER JOIN tracker_field                        AS f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
                    INNER JOIN tracker_changeset_value              AS cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                    INNER JOIN tracker_changeset_value_artifactlink AS artlink    ON (artlink.changeset_value_id = cv.id)
                    INNER JOIN tracker_artifact                     AS linked_art ON (linked_art.id = artlink.artifact_id)
                    INNER JOIN tracker                              AS t          ON (t.id = linked_art.tracker_id)
                    INNER JOIN `groups` ON (`groups`.group_id = t.group_id)
                WHERE parent_art.id  = $artifact_id
                    AND t.deletion_date IS NULL
                    AND `groups`.status = 'A'
                    AND IFNULL(artlink.nature, '') = $type
                LIMIT $limit
                OFFSET $offset";

        return $this->retrieveIds($sql);
    }

    public function getReverseLinkedArtifactIds($artifact_id, $type, $limit, $offset)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);
        $type        = $this->da->quoteSmart($type);
        $limit       = $this->da->escapeInt($limit);
        $offset      = $this->da->escapeInt($offset);

        $sql = "SELECT SQL_CALC_FOUND_ROWS parent_art.id AS id
                FROM tracker_artifact parent_art
                    INNER JOIN tracker_field                        AS f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
                    INNER JOIN tracker_changeset_value              AS cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                    INNER JOIN tracker_changeset_value_artifactlink AS artlink    ON (artlink.changeset_value_id = cv.id)
                    INNER JOIN tracker_artifact                     AS linked_art ON (linked_art.id = artlink.artifact_id)
                    INNER JOIN tracker                              AS t          ON (t.id = parent_art.tracker_id)
                    INNER JOIN `groups` ON (`groups`.group_id = t.group_id)
                WHERE linked_art.id  = $artifact_id
                    AND t.deletion_date IS NULL
                    AND `groups`.status = 'A'
                    AND IFNULL(artlink.nature, '') = $type
                LIMIT $limit
                OFFSET $offset";

        return $this->retrieveIds($sql);
    }

    public function hasReverseLinkedArtifacts($artifact_id, $type)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);
        $type        = $this->da->quoteSmart($type);

        $sql = "SELECT NULL
                FROM tracker_artifact parent_art
                    INNER JOIN tracker_field                        AS f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
                    INNER JOIN tracker_changeset_value              AS cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                    INNER JOIN tracker_changeset_value_artifactlink AS artlink    ON (artlink.changeset_value_id = cv.id)
                    INNER JOIN tracker_artifact                     AS linked_art ON (linked_art.id = artlink.artifact_id)
                    INNER JOIN tracker                              AS t          ON (t.id = parent_art.tracker_id)
                    INNER JOIN `groups` ON (`groups`.group_id = t.group_id)
                WHERE linked_art.id  = $artifact_id
                    AND `groups`.status = 'A'
                    AND IFNULL(artlink.nature, '') = $type
                LIMIT 1";

        return count($this->retrieve($sql)) > 0;
    }

    public function getUsedTypes()
    {
        $sql = "SELECT DISTINCT nature AS shortname
                FROM tracker_changeset_value_artifactlink
                WHERE nature IS NOT NULL";

        return $this->retrieve($sql);
    }
}
