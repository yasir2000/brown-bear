DROP TABLE IF EXISTS tracker_workflow;
DROP TABLE IF EXISTS tracker_workflow_transition;
DROP TABLE IF EXISTS tracker_workflow_transition_condition_field_notempty;
DROP TABLE IF EXISTS tracker_workflow_transition_condition_comment_notempty;
DROP TABLE IF EXISTS tracker_workflow_transition_postactions_field_date;
DROP TABLE IF EXISTS tracker_workflow_transition_postactions_field_int;
DROP TABLE IF EXISTS tracker_workflow_transition_postactions_field_float;
DROP TABLE IF EXISTS tracker_workflow_transition_postactions_cibuild;
DROP TABLE IF EXISTS plugin_tracker_notification_email_custom_sender_format;
DROP TABLE IF EXISTS tracker;
DROP TABLE IF EXISTS tracker_field;
DROP TABLE IF EXISTS tracker_field_int;
DROP TABLE IF EXISTS tracker_field_float;
DROP TABLE IF EXISTS tracker_field_text;
DROP TABLE IF EXISTS tracker_field_string;
DROP TABLE IF EXISTS tracker_field_msb;
DROP TABLE IF EXISTS tracker_field_date;
DROP TABLE IF EXISTS tracker_field_list;
DROP TABLE IF EXISTS tracker_field_computed;
DROP TABLE IF EXISTS tracker_field_openlist;
DROP TABLE IF EXISTS tracker_field_openlist_value;
DROP TABLE IF EXISTS tracker_field_list_bind_users;
DROP TABLE IF EXISTS tracker_field_list_bind_static;
DROP TABLE IF EXISTS tracker_field_list_bind_defaultvalue;
DROP TABLE IF EXISTS tracker_field_list_bind_static_value;
DROP TABLE IF EXISTS tracker_field_burndown;
DROP TABLE IF EXISTS tracker_field_computed_cache;
DROP TABLE IF EXISTS tracker_changeset;
DROP TABLE IF EXISTS tracker_changeset_comment;
DROP TABLE IF EXISTS tracker_changeset_comment_fulltext;
DROP TABLE IF EXISTS tracker_changeset_incomingmail;
DROP TABLE IF EXISTS plugin_tracker_changeset_from_xml;
DROP TABLE IF EXISTS tracker_changeset_value;
DROP TABLE IF EXISTS tracker_changeset_value_file;
DROP TABLE IF EXISTS tracker_changeset_value_int;
DROP TABLE IF EXISTS tracker_changeset_value_float;
DROP TABLE IF EXISTS tracker_changeset_value_text;
DROP TABLE IF EXISTS tracker_changeset_value_date;
DROP TABLE IF EXISTS tracker_changeset_value_list;
DROP TABLE IF EXISTS tracker_changeset_value_openlist;
DROP TABLE IF EXISTS tracker_changeset_value_artifactlink;
DROP TABLE IF EXISTS tracker_changeset_value_permissionsonartifact;
DROP TABLE IF EXISTS tracker_changeset_value_computedfield_manual_value;
DROP TABLE IF EXISTS tracker_fileinfo;
DROP TABLE IF EXISTS tracker_hierarchy;
DROP TABLE IF EXISTS tracker_report;
DROP TABLE IF EXISTS tracker_report_renderer;
DROP TABLE IF EXISTS tracker_report_renderer_table;
DROP TABLE IF EXISTS tracker_report_renderer_table_sort;
DROP TABLE IF EXISTS tracker_report_renderer_table_columns;
DROP TABLE IF EXISTS tracker_report_renderer_table_functions_aggregates;
DROP TABLE IF EXISTS tracker_report_criteria;
DROP TABLE IF EXISTS tracker_report_criteria_date_value;
DROP TABLE IF EXISTS tracker_report_criteria_alphanum_value;
DROP TABLE IF EXISTS tracker_report_criteria_file_value;
DROP TABLE IF EXISTS tracker_report_criteria_list_value;
DROP TABLE IF EXISTS tracker_report_criteria_openlist_value;
DROP TABLE IF EXISTS tracker_report_criteria_permissionsonartifact_value;
DROP TABLE IF EXISTS tracker_field_list_bind_decorator;
DROP TABLE IF EXISTS tracker_artifact;
DROP TABLE IF EXISTS tracker_artifact_priority_rank;
DROP TABLE IF EXISTS tracker_artifact_priority_history;
DROP TABLE IF EXISTS tracker_tooltip;
DROP TABLE IF EXISTS tracker_global_notification;
DROP TABLE IF EXISTS tracker_global_notification_users;
DROP TABLE IF EXISTS tracker_global_notification_ugroups;
DROP TABLE IF EXISTS tracker_global_notification_unsubscribers;
DROP TABLE IF EXISTS tracker_only_status_change_notification_subscribers;
DROP TABLE IF EXISTS plugin_tracker_involved_notification_subscribers;
DROP TABLE IF EXISTS tracker_watcher;
DROP TABLE IF EXISTS tracker_notification_role;
DROP TABLE IF EXISTS tracker_notification_event;
DROP TABLE IF EXISTS tracker_notification;
DROP TABLE IF EXISTS tracker_notification_role_default;
DROP TABLE IF EXISTS tracker_notification_event_default;
DROP TABLE IF EXISTS tracker_canned_response;
DROP TABLE IF EXISTS tracker_staticfield_richtext;
DROP TABLE IF EXISTS tracker_semantic_title;
DROP TABLE IF EXISTS tracker_semantic_description;
DROP TABLE IF EXISTS tracker_semantic_status;
DROP TABLE IF EXISTS tracker_semantic_contributor;
DROP TABLE IF EXISTS tracker_semantic_timeframe;
DROP TABLE IF EXISTS tracker_semantic_progress;
DROP TABLE IF EXISTS tracker_rule;
DROP TABLE IF EXISTS tracker_rule_list;
DROP TABLE IF EXISTS tracker_rule_date;
DROP TABLE IF EXISTS tracker_reminder;
DROP TABLE IF EXISTS tracker_workflow_trigger_rule_static_value;
DROP TABLE IF EXISTS tracker_workflow_trigger_rule_trg_field_static_value;
DROP TABLE IF EXISTS tracker_artifact_unsubscribe;
DROP TABLE IF EXISTS tracker_post_creation_event_log;

DROP TABLE IF EXISTS plugin_tracker_config;
DROP TABLE IF EXISTS plugin_tracker_artifactlink_natures;
DROP TABLE IF EXISTS plugin_tracker_notification_assigned_to;
DROP TABLE IF EXISTS plugin_tracker_recently_visited;
DROP TABLE IF EXISTS plugin_tracker_projects_use_artifactlink_types;
DROP TABLE IF EXISTS plugin_tracker_projects_unused_artifactlink_types;
DROP TABLE IF EXISTS plugin_tracker_deleted_artifacts;
DROP TABLE IF EXISTS plugin_tracker_source_artifact_id;
DROP TABLE IF EXISTS plugin_tracker_artifact_pending_removal;

DROP TABLE IF EXISTS tracker_report_criteria_comment_value;
DROP TABLE IF EXISTS plugin_tracker_webhook_url;
DROP TABLE IF EXISTS plugin_tracker_webhook_log;
DROP TABLE IF EXISTS plugin_tracker_workflow_postactions_frozen_fields;
DROP TABLE IF EXISTS plugin_tracker_workflow_postactions_frozen_fields_value;
DROP TABLE IF EXISTS plugin_tracker_file_upload;
DROP TABLE IF EXISTS plugin_tracker_workflow_postactions_hidden_fieldsets;
DROP TABLE IF EXISTS plugin_tracker_workflow_postactions_hidden_fieldsets_value;
DROP TABLE IF EXISTS plugin_tracker_pending_jira_import;
DROP TABLE IF EXISTS plugin_tracker_in_new_dropdown;
DROP TABLE IF EXISTS tracker_field_list_bind_ugroups_value;
DROP TABLE IF EXISTS tracker_fileinfo_temporary;
DROP TABLE IF EXISTS tracker_reminder_notified_roles;
DROP TABLE IF EXISTS tracker_report_config;
DROP TABLE IF EXISTS tracker_widget_renderer;

DROP TABLE IF EXISTS plugin_tracker_legacy_tracker_migrated;

DROP TABLE IF EXISTS plugin_tracker_private_comment_disabled_tracker;
DROP TABLE IF EXISTS plugin_tracker_private_comment_permission;
DROP TABLE IF EXISTS plugin_tracker_semantic_done;

DELETE FROM permissions WHERE permission_type LIKE 'PLUGIN_TRACKER_%';
DELETE FROM permissions_values WHERE permission_type LIKE 'PLUGIN_TRACKER_%';

DELETE FROM service WHERE short_name = 'plugin_tracker';

-- Cleanup references
DELETE reference_group FROM reference_group INNER JOIN reference ON (reference_group.reference_id = reference.id) WHERE reference.service_short_name = 'plugin_tracker';
DELETE FROM reference WHERE service_short_name = 'plugin_tracker';
DELETE FROM cross_references WHERE source_type = 'plugin_tracker_artifact' OR target_type = 'plugin_tracker_artifact';

DELETE FROM user_preferences WHERE preference_name LIKE 'tracker\_%\_last\_renderer';
DELETE FROM user_preferences WHERE preference_name LIKE 'tracker\_%\_last\_report';

DELETE FROM user_access WHERE user_id = 90;
DELETE FROM user WHERE user_id = 90;

DELETE FROM user_access WHERE user_id = 91;
DELETE FROM user WHERE user_id = 91;

DELETE FROM forgeconfig WHERE name = 'feature_flag_use_list_pickers_in_trackers_and_modals';
DELETE FROM forgeconfig WHERE name = 'tracker_jira_force_basic_auth';