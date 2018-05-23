<?php
# Copyright (c) 2018 Grigoriy Ermolaev (igflocal@gmail.com)
# Calendar for MantisBT is free software: 
# you can redistribute it and/or modify it under the terms of the GNU
# General Public License as published by the Free Software Foundation, 
# either version 2 of the License, or (at your option) any later version.
#
# Customer management plugin for MantisBT is distributed in the hope 
# that it will be useful, but WITHOUT ANY WARRANTY; without even the 
# implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
# See the GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Customer management plugin for MantisBT.  
# If not, see <http://www.gnu.org/licenses/>.
//config_set( 'show_monitor_list_threshold', constant( 'REPORTER' ) );
//config_delete( 'show_monitor_list_threshold' );

class MantisServiceDeskPlugin extends MantisPlugin {

    function register() {

        $this->name        = plugin_lang_get( 'name' );
        $this->description = plugin_lang_get( 'description' );
        $this->page        = 'config';

        $this->version  = '1.8.0';
        $this->requires = array(
                                  'MantisCore' => '2.0',
        );

        $this->author  = 'Grigoriy Ermolaev';
        $this->contact = 'igflocal@gmail.com';
        $this->url     = 'http://github.com/mantisbt-plugins/MantisServiceDesk';
    }

    /**
     * Default plugin configuration.
     */
    function config() {
        return array(
                                  'process_disable_project'            => FALSE,
                                  'check_comments'                     => TRUE,
                                  'bug_status_array'                   => array( 0 => 10,
                                                            1 => 50 ),
                                  'bug_status'                         => 30,
                                  'bug_status_block_assignation_array' => array( 0 => 50 ),
                                  'bug_monitor_run'                    => TRUE,
                                  'file_upload_multiple'               => TRUE,
                                  'projects_id_event_report_access'    => array( NULL ),
                                  'bug_submit_status'                   => ASSIGNED,
        );
    }

    public function errors() {
        return array(
        );
    }

    function init() {

        function custom_function_override_print_column_value( $p_column, $p_bug, $p_columns_target = COLUMNS_TARGET_VIEW_PAGE ) {

            switch( $p_column ) {

                case 'last_updated':
                    global $t_filter;

                    $time_last_updated = string_display_line( date( config_get( 'short_date_format' ), $p_bug->last_updated ) );
                    $time_now          = string_display_line( date( config_get( 'short_date_format' ), time() ) );

                    if( $time_now == $time_last_updated )
                        $t_last_updated = 'Сегодня<br>' . string_display_line( date( 'H:i', $p_bug->last_updated ) );
                    else
                        $t_last_updated = string_display_line( date( config_get( 'short_date_format' ), $p_bug->last_updated ) ) . '<br>' .
                                string_display_line( date( 'H:i', $p_bug->last_updated ) );

                    echo '<td style="white-space: nowrap;">';
                    if( $p_bug->last_updated > strtotime( '-' . $t_filter['highlight_changed'] . ' hours' ) ) {
                        printf( '<span class="bold">%s</span>', $t_last_updated );
                    } else {
                        echo $t_last_updated;
                    }
                    echo '</td>';
                    break;

//                case 'status':
//                    echo '<td style="white-space: nowrap;">';
//                    printf( '<span class="issue-status" title="%s">%s</span><br /> ', get_enum_element( 'resolution', $p_bug->resolution, auth_get_current_user_id(), $p_bug->project_id ), get_enum_element( 'status', $p_bug->status, auth_get_current_user_id(), $p_bug->project_id )
//                    );
//
//                    # print username instead of status
//                    if( ( ON == config_get( 'show_assigned_names' ) ) && ( $p_bug->handler_id > 0 ) && ( access_has_project_level( config_get( 'view_handler_threshold' ), $p_bug->project_id ) ) ) {
//                        printf( ' (%s)', prepare_user_name( $p_bug->handler_id ) );
//                    }
//                    echo '</td>';
//                    break;

                case 'category_id':
                    global $t_sort, $t_dir;

                    # grab the project name
                    $t_project_name  = project_get_field( $p_bug->project_id, 'name' );
                    $t_reporter_name = prepare_user_name( $p_bug->reporter_id );

                    echo '<td class="center">';

                    # type project name if viewing 'all projects' or if issue is in a subproject
                    if( ON == config_get( 'show_bug_project_links' ) && helper_get_current_project() != $p_bug->project_id ) {
                        echo '[';
                        print_view_bug_sort_link( string_display_line( $t_project_name ), 'project_id', $t_sort, $t_dir, $p_columns_target );
                        echo ']-';
                        echo '-[';
                        echo $t_reporter_name;
                        echo ']<br />';
                    }

                    echo string_display_line( category_full_name( $p_bug->category_id, false ) );

                    echo '</td>';
                    break;

                default:
                    custom_function_default_print_column_value( $p_column, $p_bug, $p_columns_target );
            }
        }

    }

    function hooks() {
        $hooks = array(
                                  'EVENT_MENU_ISSUE'             => 'change_issue',
                                  'EVENT_UPDATE_BUG_DATA'        => array( 'check_issue',
                                                            'bug_assignation_to_policy' ),
                                  'EVENT_UPDATE_BUG'             => 'bug_monitor_add',
                                  'EVENT_MANAGE_PROJECT_UPDATE'  => 'subprojects_change_status_enabled',
                                  'EVENT_UPDATE_BUG_STATUS_FORM' => array( 'issue_assignate_to',
                                                            'add_me_to_monitor_form' ),
                                  'EVENT_REPORT_BUG_FORM_TOP'    => 'event_report_bug_project_access',
                                  'EVENT_MENU_MANAGE'            => 'config_menu',
                                  'EVENT_VIEW_BUG_EXTRA'         => 'bug_monitor_list_view',
                                  'EVENT_MANAGE_PROJECT_CREATE'  => 'create_project_copy_users',
                                  'EVENT_LAYOUT_BODY_END'        => 'resources',
                                  'EVENT_LAYOUT_BODY_BEGIN'      => 'load_variables_to_manage_proj_edit_page',
        );
        return $hooks;
    }

    function load_variables_to_manage_proj_edit_page() {
        echo '<div id="manage_proj_user_add_var" value=' . plugin_page( 'manage_proj_user_add' ) . '></div>';
        echo '<div id="manage_proj_user_remove_var" value=' . plugin_page( 'manage_proj_user_remove' ) . '></div>';

        echo '<div id="manage_user_proj_add_var" value=' . plugin_page( 'manage_user_proj_add' ) . '></div>';
        echo '<div id="manage_user_proj_delete_var" value=' . plugin_page( 'manage_user_proj_delete' ) . '></div>';
    }

    function resources() {
        return '<script type="text/javascript" src="' . plugin_file( 'MantisServiceDesk.js' ) . '"></script>';
    }

    function create_project_copy_users( $p_type_event, $p_project_id ) {

        $t_parent_id = project_hierarchy_get_parent( $p_project_id, TRUE );

        if( 0 != $t_parent_id ) {
            project_copy_users( $p_project_id, $t_parent_id, access_get_project_level( $p_project_id ) );
        }
    }

    function bug_monitor_list_view( $p_type_event, $p_bug_id ) {

        include ( plugin_file_path( 'bug_monitor_list_view_inc.php', 'MantisServiceDesk' ));
    }

    function config_menu() {
        return array( '<a href="' . plugin_page( 'config' ) . '">' . plugin_lang_get( 'config' ) . ': ' . plugin_lang_get( 'name' ) . '</a>', );
    }

    function event_report_bug_project_access( $p_type_event, $p_project_id ) {

        if( in_array( $p_project_id, plugin_config_get( 'projects_id_event_report_access' ) ) ) {
            print_header_redirect( '/login_select_proj_page.php?ref=bug_report_page.php' );
        }
    }

    function check_issue( $type_event, $p_updated_bug, $p_existing_bug ) {

        if( plugin_config_get( 'check_comments' ) ) {
            if( trim( $_REQUEST["bugnote_text"] ) == null && $_REQUEST["status"] != 90 && $_REQUEST["status"] != null ) {
                error_parameters( plugin_lang_get( 'error_empty_field' ) );
                trigger_error( ERROR_EMPTY_FIELD, ERROR );
            }
        }
        return $p_updated_bug;
    }

    function change_issue( $p_type_issue, $p_bug_id ) {

        if( plugin_config_get( 'bug_status_array' ) && $_REQUEST["new_status"] == null ) {

            $tpl_bug = bug_get( $p_bug_id, true );

            $current_user_id  = auth_get_current_user_id();
            $assigned_user_id = $tpl_bug->handler_id;
            $status_id        = $tpl_bug->status;

            if( plugin_config_get( 'check_comments' ) ) {

                foreach( plugin_config_get( 'bug_status_array' ) as $t_bug_status ) {

                    if( ( $current_user_id == $assigned_user_id && $status_id == $t_bug_status ) && $_REQUEST["new_status"] == null ) {
//                        html_meta_redirect( 'bug_change_status_page.php?id=' . $f_bug_id . '&new_status='. plugin_config_get( 'bug_status' ) );
                        print_header_redirect( 'bug_change_status_page.php?id=' . $p_bug_id . '&new_status=' . plugin_config_get( 'bug_status' ), FALSE );
                    }
                }
            } else {

                foreach( plugin_config_get( 'bug_status_array' ) as $t_bug_status ) {

                    if( ( $current_user_id == $assigned_user_id && $status_id == $t_bug_status ) && $_REQUEST["new_status"] == null ) {

                        $tpl_bug->status = plugin_config_get( 'bug_status' );

                        $tpl_bug->update();

                        html_meta_redirect( 'view.php?id=' . $p_bug_id );
                    }
                }
            }
        }
    }

    function subprojects_change_status_enabled( $p_event, $p_project_id ) {

        $t_subprojects = project_hierarchy_get_all_subprojects( $p_project_id, plugin_config_get( 'process_disable_project' ) );

        foreach( $t_subprojects as $t_subproject ) {

            $t_name           = (string) project_get_field( $t_subproject, 'name' );
            $t_description    = (string) project_get_field( $t_subproject, 'description' );
            $t_status         = (int) project_get_field( $t_subproject, 'status' );
            $t_view_state     = (int) project_get_field( $t_subproject, 'view_state' );
            $t_file_path      = (string) project_get_field( $t_subproject, 'file_path' );
            $f_enabled        = gpc_get_bool( 'enabled' );
            $t_inherit_global = (boolean) project_get_field( $t_subproject, 'inherit_global' );

            # Don't reveal the absolute path to non-administrators for security reasons
            if( is_blank( $t_file_path ) && current_user_is_administrator() ) {
                $t_file_path = config_get( 'absolute_path_default_upload_folder' );
            }

            project_update( $t_subproject, $t_name, $t_description, $t_status, $t_view_state, $t_file_path, $f_enabled, $t_inherit_global );
            event_signal( 'EVENT_MANAGE_PROJECT_UPDATE', array( $t_subproject ) );
        }
    }

    function issue_assignate_to() {
        if( plugin_config_get( 'bug_status_block_assignation_array' ) ) {

            $t_new_status = gpc_get_int( 'new_status' );

            if( !in_array( $t_new_status, plugin_config_get( 'bug_status_block_assignation_array' ) ) ) {

                echo '<script type="text/javascript" src="' . plugin_file( 'MantisServiceDesk_1.js' ) . '"></script>';
            }
        }
    }

    function add_me_to_monitor_form( $p_type_event, $p_bug_id ) {

        if( !bug_is_user_reporter( $p_bug_id, auth_get_current_user_id() ) && gpc_get_int( 'new_status' ) == 50 && plugin_config_get( 'bug_monitor_run' ) ) {
            $temp;
            ?>
            <tr <?php echo helper_alternate_class() ?>>
                <td class="category">Добавить меня в отслеживающие</td>
                <td><input type="checkbox" name="add_me_to_monitor" id="add_me_to_monitor" value="true" checked></td>
            </tr>

            <?php
        }
    }

    function bug_monitor_add( $p_type_event, $p_existing_bug, $p_updated_bug ) {

        if( plugin_config_get( 'bug_monitor_run' ) ) {

            $f_add_monitor = gpc_get_bool( 'add_me_to_monitor', FALSE );

            if( $f_add_monitor ) {
                bug_monitor( $p_updated_bug->id, auth_get_current_user_id() );
            }

            if( in_array( $p_updated_bug->handler_id, bug_get_monitors( $p_updated_bug->id ) ) ) {
                bug_unmonitor( $p_updated_bug->id, $p_updated_bug->handler_id );
            }
        }
    }

    function bug_assignation_to_policy( $p_type_event, $p_updated_bug, $p_existing_bug ) {
        if( $p_updated_bug->status == $p_existing_bug->status && $p_updated_bug->handler_id != $p_existing_bug->handler_id ) {
            $p_updated_bug->status = plugin_config_get( 'bug_submit_status' );
            return $p_updated_bug;
        } else {
            return $p_updated_bug;
        }
    }

}
