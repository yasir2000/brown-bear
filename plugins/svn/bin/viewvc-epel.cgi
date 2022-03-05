#!/usr/bin/python
# -*-python-*-
#
# Copyright (C) 1999-2006 The ViewCVS Group. All Rights Reserved.
# Copyright (c) Enalean, 2016 - Present. All rights reserved
#
# By using this file, you agree to the terms and conditions set forth in
# the LICENSE.html file which can be found at the top level of the ViewVC
# distribution or at http://viewvc.org/license-1.html.
#
# For more information, visit http://viewvc.org/
#

import sys
import os
import string
import urllib

if os.getenv('LD_LIBRARY_PATH', '') == '/opt/rh/python27/root/usr/lib64':
    LIBRARY_DIR = '/usr/lib/python2.6/site-packages/viewvc/lib'
else:
    from distutils.sysconfig import get_python_lib
    LIBRARY_DIR = get_python_lib()+'/viewvc/lib'

CONF_PATHNAME = '/etc/viewvc/viewvc.conf'
TULEAP_UTILS = '/usr/share/tuleap/src/utils'
TULEAP_UTILS_SVN = '/usr/share/tuleap/src/utils/svn'

sys.path.insert(0, TULEAP_UTILS)
sys.path.insert(0, TULEAP_UTILS_SVN)
sys.path.insert(0, LIBRARY_DIR)

import svnaccess

username = os.getenv('REMOTE_USER', '')
project_name = os.getenv('TULEAP_PROJECT_NAME', '')
repo_name = os.getenv('TULEAP_REPO_NAME', '')
full_repo_name = os.getenv('TULEAP_FULL_REPO_NAME', '')
repo_path = os.getenv('TULEAP_REPO_PATH', '')
tuleap_user_is_super_user = os.getenv('TULEAP_USER_IS_SUPER_USER', '0')
path_info = os.getenv('PATH_INFO', '/')
# Remove potential / duplicates
path_parts = filter(None, string.split(path_info, '/'))
requested_path = string.join(path_parts, '/')

if tuleap_user_is_super_user != '1' and not svnaccess.check_read_access(username, repo_path, requested_path):
    exit(128)

import sapi
import viewvc

server = sapi.CgiServer()
cfg = viewvc.load_config(CONF_PATHNAME, server)

cfg.general.svn_roots[full_repo_name] = repo_path
cfg.options.template_dir = '/usr/share/viewvc-theme-tuleap/templates'
cfg.options.root_as_url_component = 0
cfg.options.allowed_views = ['annotate', 'diff', 'markup', 'roots', 'co']
# Used by the views
cfg.options.repo_name = repo_name
cfg.options.root_href = os.getenv('SCRIPT_NAME', '') + '/?' + urllib.urlencode({'root': full_repo_name})
cfg.options.docroot = '/viewvc-tuleap'
# Force the desactivation of risky options security wise
cfg.options.use_re_search = 0
cfg.options.show_subdir_lastmod = 0

viewvc.main(server, cfg)
