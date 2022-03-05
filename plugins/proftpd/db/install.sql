INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, `rank`)
       VALUES      ( 100, 'plugin_proftpd:service_lbl_key', 'plugin_proftpd:service_desc_key', 'plugin_proftpd', '/plugins/proftpd/?group_id=$group_id', 1, 0, 'system', 230 );

-- Create service for all other projects (but disabled)
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, `rank`)
  SELECT DISTINCT group_id, 'plugin_proftpd:service_lbl_key', 'plugin_proftpd:service_desc_key', 'plugin_proftpd', CONCAT('/plugins/proftpd/?group_id=', group_id), 1, 0, 'system', 230
        FROM service
        WHERE group_id NOT IN (SELECT group_id
                               FROM service
                               WHERE short_name
                               LIKE 'plugin_proftpd');

-- Designed after `man xferlog`
CREATE TABLE IF NOT EXISTS  plugin_proftpd_xferlog (
    id  INTEGER UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id int(11) NOT NULL,
    group_id int(11) NOT NULL,
    time INTEGER UNSIGNED NOT NULL DEFAULT 0,
    transfer_time INTEGER UNSIGNED NOT NULL DEFAULT 0,
    remote_host VARCHAR(255) NOT NULL DEFAULT '',
    file_size INTEGER UNSIGNED NOT NULL DEFAULT 0,
    file_name MEDIUMTEXT NOT NULL,
    transfer_type CHAR(1) NOT NULL DEFAULT '',
    special_action_flag  CHAR(1) NOT NULL DEFAULT '',
    direction CHAR(1) NOT NULL DEFAULT '',
    access_mode CHAR(1) NOT NULL DEFAULT '',
    username VARCHAR(255) NOT NULL DEFAULT '',
    service_name VARCHAR(255) NOT NULL DEFAULT '',
    authentication_method TINYINT(1) NOT NULL DEFAULT 0,
    authenticated_user_id INTEGER UNSIGNED NOT NULL DEFAULT 0,
    completion_status CHAR(1) NOT NULL DEFAULT '',
    INDEX idx_user_id(user_id),
    INDEX idx_group_id(user_id)
);

-- This view gives access to
CREATE OR REPLACE VIEW ftpgroups AS
(
    SELECT unix_group_name as groupname, group_id+1000 as gid, GROUP_CONCAT(user_name) as members
    FROM `groups`
        JOIN user_group USING (group_id)
        JOIN user USING (user_id)
    WHERE `groups`.status = 'A'
        AND user.status IN ('A', 'R')
        AND user_id > 100
    GROUP BY gid
)
UNION
(
    SELECT LOWER(CONCAT(`groups`.unix_group_name, '-', ugroup.name)) as groupname, ugroup.ugroup_id+10000 as gid, GROUP_CONCAT(DISTINCT user_name) as members
    FROM ugroup
        JOIN permissions ON (permission_type IN ('PLUGIN_PROFTPD_READ', 'PLUGIN_PROFTPD_WRITE') AND permissions.ugroup_id = ugroup.ugroup_id)
        JOIN `groups` USING (group_id)
        LEFT JOIN ugroup_user ON (ugroup_user.ugroup_id = ugroup.ugroup_id)
        LEFT JOIN user USING (user_id)
    WHERE `groups`.status = 'A'
    AND (
        user.user_id IS NULL
     OR user.status IN ('A', 'R') AND user_id > 100)
    GROUP BY gid
)
UNION
(
    SELECT user_name as groupname, unix_uid+20000 as gid, user_name
    FROM user
    WHERE status IN ('A', 'R')
        AND user_id > 100
    GROUP BY gid
);

-- View for users
CREATE VIEW ftpusers AS
SELECT user_name as username, unix_pw as password, unix_uid+20000 as uid, unix_uid+20000 as gid, CONCAT("/home/users/", user_name) as home, shell
FROM user
WHERE status IN ('A', 'R')
AND user_id > 100;
