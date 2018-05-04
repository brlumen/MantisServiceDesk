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

auth_reauthenticate();
access_ensure_global_level( config_get( 'manage_plugin_threshold' ) );

$f_process_disable_project            = gpc_get_bool( 'process_disable_project', FALSE );
$f_check_comments                     = gpc_get_bool( 'check_comments', TRUE );
$f_cheked_bug_status                  = gpc_get_int_array( 'bug_status_array', null );
$f_bug_status                         = gpc_get_int( 'bug_status' );
$f_bug_status_block_assignation_array = gpc_get_int_array( 'bug_status_block_assignation_array', NULL );
$f_bug_monitor_run                    = gpc_get_bool( 'bug_monitor_run', plugin_config_get( 'bug_monitor_run' ) );
$f_file_upload_multiple               = gpc_get_bool( 'file_upload_multiple', plugin_config_get( 'file_upload_multiple' ) );
$f_project_id_report_access           = gpc_get_int_array( 'project_id_report_access', plugin_config_get( 'projects_id_event_report_access' ) );

if( plugin_config_get( 'process_disable_project' ) != $f_process_disable_project ) {
    plugin_config_set( 'process_disable_project', $f_process_disable_project );
}

if( plugin_config_get( 'check_comments' ) != $f_check_comments ) {
    plugin_config_set( 'check_comments', $f_check_comments );
}

if( plugin_config_get( 'bug_status_array' ) != $f_cheked_bug_status ) {
    plugin_config_set( 'bug_status_array', $f_cheked_bug_status );
}

if( plugin_config_get( 'bug_status' ) != $f_bug_status ) {
    plugin_config_set( 'bug_status', $f_bug_status );
}

if( plugin_config_get( 'bug_status_block_assignation_array' ) != $f_bug_status_block_assignation_array ) {
    plugin_config_set( 'bug_status_block_assignation_array', $f_bug_status_block_assignation_array );
}

if( plugin_config_get( 'bug_monitor_run' ) != $f_bug_monitor_run ) {
    plugin_config_set( 'bug_monitor_run', $f_bug_monitor_run );
}

if( plugin_config_get( 'file_upload_multiple' ) != $f_file_upload_multiple ) {
    plugin_config_set( 'file_upload_multiple', $f_file_upload_multiple );
}

if( plugin_config_get( 'projects_id_event_report_access' ) != $f_project_id_report_access ) {
    plugin_config_set( 'projects_id_event_report_access', $f_project_id_report_access );
}

form_security_purge( 'service_desk_config_edit' );

html_page_top( null, plugin_page( 'config', true ) );
//print_successful_redirect( plugin_page( 'config', true ) );
//print_header_redirect( plugin_page( 'config', true ) );
?>


<div align="center">
    <?php
    echo lang_get( 'operation_successful' ) . '<br />';

    print_bracket_link( plugin_page( 'config', true ), lang_get( 'proceed' ) );
    ?>
</div>

<?php
html_page_bottom();
