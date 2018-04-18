<?php
# MantisBT - a php based bugtracking system
# Copyright (C) 2002 - 2012  MantisBT Team - mantisbt-dev@lists.sourceforge.net
# MantisBT is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 2 of the License, or
# (at your option) any later version.
#
# MantisBT is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with MantisBT.  If not, see <http://www.gnu.org/licenses/>.

form_security_validate( 'service_desk_config_edit' );

auth_reauthenticate( );
access_ensure_global_level( config_get( 'manage_plugin_threshold' ) );

$f_process_disable_project = gpc_get_bool( 'process_disable_project', FALSE );

if( plugin_config_get( 'process_disable_project' ) != $f_process_disable_project ) {
	plugin_config_set( 'process_disable_project', $f_process_disable_project );
}

form_security_purge( 'service_desk_config_edit' );

print_successful_redirect( plugin_page( 'config', true ) );
