_pluginGit() {
    local -r git="/usr/lib/tuleap/git/bin/git"
    local -r gitolite="/usr/bin/gitolite"
    local -r git_group="gitolite"
    local -r git_user="gitolite"
    local -r git_home="/var/lib/gitolite"
    local -r ssh="/usr/bin/ssh"
    local -r sshkeygen="/usr/bin/ssh-keygen"
    local plugin_git_configured="false"

    _checkCommand ${git} ${ssh} ${sshkeygen}

    if [ ! -d "${tuleap_data}/gitolite/admin" ]; then
        ${install} --directory \
                   --group=${tuleap_unix_user} \
                   --owner=${tuleap_unix_user} \
                   --mode=750 \
                   ${tuleap_data}/gitolite/admin
        plugin_git_configured="true"
    fi

    if [ ! -L "${git_home}/repositories" ]; then
        ${rm} --force --recursive ${git_home}/repositories
        ${ln} --symbolic ${tuleap_data}/gitolite/repositories \
            ${git_home}/repositories
        plugin_git_configured="true"
    fi

    if [ -d "${git_home}/.gitolite" ]; then
        ${chmod} 750 ${git_home}/.gitolite
        ${chmod} 750 ${git_home}/.gitolite/*
    fi

    if [ ! -d "${tuleap_data}/.ssh" ]; then
        ${install} --directory \
                   --group=${tuleap_unix_user} \
                   --owner=${tuleap_unix_user} \
                   --mode=750 ${tuleap_data}/.ssh
        plugin_git_configured="true"
    fi

    if [ ! -f "${tuleap_data}/.ssh/id_rsa_gl-adm" ]; then
        ${sshkeygen} -q -t rsa -f ${tuleap_data}/.ssh/id_rsa_gl-adm \
            -N "" -C "Tuleap / gitolite admin key"
        ${chown} ${tuleap_unix_user}. \
            ${tuleap_data}/.ssh/id_rsa_gl-{adm,adm.pub}
        plugin_git_configured="true"
    fi

    if [ ! -f ${tuleap_data}/.ssh/config ] || \
        ${grep} --quiet "/home/codendiadm" "${tuleap_data}/.ssh/config"; then
        ${awk} '{ gsub("/home/codendiadm", "'"${tuleap_data}"'"); print }' \
            "${tuleap_src_plugins}/git/etc/ssh.config.dist" >> \
            "${tuleap_data}/.ssh/config"
        ${chown} ${tuleap_unix_user}. "${tuleap_data}/.ssh/config"
        plugin_git_configured="true"
    fi

    if ! $(${tuleapcfg} systemctl is-active sshd); then
        _errorMessage "Please, start the SSHD service to continue"
        exit 1
    fi

    if ! ${su} --command "${ssh} -q ${git_user}@gl-adm info" --login ${tuleap_unix_user} 2>&1 >/dev/null; then
        # Need to setup .profile so gitolite setup finds git executable
        ${tuleapcfg} site-deploy:gitolite3-config
        ${cp} --force "${tuleap_data}/.ssh/id_rsa_gl-adm.pub" /tmp
        ${su} --command "${gitolite} setup --pubkey /tmp/id_rsa_gl-adm.pub" --login ${git_user}
        # Need to setup .gitolite.rc so gitolite then find Git
        ${tuleapcfg} site-deploy:gitolite3-config

    fi

    if [ -f /tmp/id_rsa_gl-adm.pub ]; then
        ${rm} --force /tmp/id_rsa_gl-adm.pub
    fi

    for user in ${git_user} ${tuleap_unix_user}; do
        if [ "$(${su} --command "${git} config user.name" --login ${user})" != "${user}" ]; then
            ${su} --command \
                "${git} config --global user.name ${user}" --login ${user}
            plugin_git_configured="true"
        fi

        if [ "$(${su} --command "${git} config user.email" --login ${user})" != "${user}@localhost" ]; then
            ${su} --command \
                "${git} config --global user.email ${user}@localhost" \
                --login ${user}
                plugin_git_configured="true"
        fi
    done

    if [ ! -d "${tuleap_data}/gitolite/admin/.git" ]; then
        ${su} --command \
            "${git} clone ${git_user}@gl-adm:gitolite-admin \
            ${tuleap_data}/gitolite/admin" --login ${tuleap_unix_user}
        plugin_git_configured="true"
    fi

    if [ ! -f "${tuleap_data}/gitolite/projects.list" ]; then
        ${touch} ${tuleap_data}/gitolite/projects.list
        ${chown} ${tuleap_unix_user}. ${tuleap_data}/gitolite/projects.list
        plugin_git_configured="true"
    fi

    ${tuleapcfg} site-deploy:gitolite3-config

    if [ ! -f "${tuleap_data}/gitolite/admin/conf/gitolite.conf" ]; then
        ${install} --group=${tuleap_unix_user} \
                   --owner=${tuleap_unix_user} \
                   --mode=644 \
                   "${tuleap_src_plugins}/git/etc/gitolite.conf.dist" \
                   "${tuleap_data}/gitolite/admin/conf/gitolite.conf"
        plugin_git_configured="true"
    fi

    if ! ${su} --command '/usr/lib/tuleap/git/bin/git \
        --git-dir="/var/lib/tuleap/gitolite/admin/.git"  \
        cat-file -e origin/master:conf/gitolite.conf' \
        --login ${tuleap_unix_user}; then
        ${su} --command \
            "cd ${tuleap_data}/gitolite/admin && \
            ${git} add conf/gitolite.conf && \
            ${git} commit --message='Remove testing' && \
            ${git} push origin master" --login ${tuleap_unix_user}
        plugin_git_configured="true"
    fi

    if [ -d "${tuleap_data}/gitolite/repositories/testing.git" ]; then
        ${rm} --force --recursive \
            "${tuleap_data}/gitolite/repositories/testing.git"
        plugin_git_configured="true"
    fi

    if [ ! -L "${git_home}/.gitolite/hooks/common/post-receive" ]; then
        ${ln} -s "${tuleap_src_plugins}/git/hooks/post-receive-gitolite" \
            "${git_home}/.gitolite/hooks/common/post-receive"
        plugin_git_configured="true"
    fi

    if ! $(${tuleapcfg} systemctl is-enabled tuleap-process-system-events-git.timer); then
        ${tuleapcfg} systemctl enable "tuleap-process-system-events-git.timer"
        plugin_git_configured="true"
    fi

    if ! $(${tuleapcfg} systemctl is-active tuleap-process-system-events-git.timer); then
        ${tuleapcfg} systemctl start "tuleap-process-system-events-git.timer"
        plugin_git_configured="true"
    fi

    if ! $(${tuleapcfg} systemctl is-enabled tuleap-process-system-events-grokmirror.timer); then
        ${tuleapcfg} systemctl enable "tuleap-process-system-events-grokmirror.timer"
        plugin_git_configured="true"
    fi

    if ! $(${tuleapcfg} systemctl is-active tuleap-process-system-events-grokmirror.timer); then
        ${tuleapcfg} systemctl start "tuleap-process-system-events-grokmirror.timer"
        plugin_git_configured="true"
    fi

    if [ ${plugin_git_configured} = "true" ]; then
        _infoMessage "Plugin Git is configured"
        plugins_configured+=('true')
    else
        _infoMessage "Plugin Git is already configured"
    fi
}

_pluginSVN() {
    local -r httpd_vhost="/etc/httpd/conf.d/tuleap-vhost.conf"
    local plugin_svn_configured="false"

    if ! $(/usr/bin/tuleap config-get sys_dbauth_passwd 2>/dev/null); then
        if [ ${mysql_user:-NULL} != "NULL" ] && \
           [ ${mysql_password:-NULL} != "NULL" ]; then
            dbauthuser_password="$(_setupRandomPassword)"

            ${tuleapcfg} setup:mysql-init \
                --host="${mysql_server}" \
                --admin-user="${mysql_user}" \
                --admin-password="${mysql_password}" \
                --db-name="${sys_db_name}" \
                --nss-password="${dbauthuser_password}"

            plugin_svn_configured="true"
        else
            _errorMessage "You must enter your MySQL user and password"
            exit 1
        fi
    fi

    if [ ! -f ${httpd_vhost} ]; then
        server_name=$(${awk} --field-separator="'" \
            '/^\$sys_default_domain/ {print $2}' ${tuleap_conf}/local.inc)
        ${awk} '{ gsub("%sys_default_domain%", "'"${server_name}"'");
                  gsub("*:80$", "127.0.0.1:8080");
                  gsub("*:80>", "127.0.0.1:8080>");
                  print }' \
                    "${tuleap_src}/etc/tuleap-vhost.conf.dist" > ${httpd_vhost}
        ${sed} --in-place '/Include.*configuration\|tuleap-aliases/d' \
             ${httpd_vhost}
        plugin_svn_configured="true"
    fi

    if [ ${plugin_svn_configured} = "true" ]; then
        ${tuleapcfg} systemctl restart "httpd.service"
        ${tuleapcfg} systemctl enable "httpd.service"
        _infoMessage "Plugin SVN is configured"
        plugins_configured+=('true')
    else
        _infoMessage "Plugin SVN is already configured"
    fi
}

_pluginMediawiki() {
    ${tuleapcfg} setup:mysql-init \
        --host="${mysql_server}" \
        --admin-user="${mysql_user}" \
        --admin-password="${mysql_password}" \
        --mediawiki=per-project
}
