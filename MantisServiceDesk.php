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

        $this->version  = '1.7.0';
        $this->requires = array(
                                  'MantisCore' => '1.2.12',
                                  'jQuery'     => '1.11.1'
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

                case 'status':
                    echo '<td style="white-space: nowrap;">';
                    printf( '<span class="issue-status" title="%s">%s</span><br /> ', get_enum_element( 'resolution', $p_bug->resolution, auth_get_current_user_id(), $p_bug->project_id ), get_enum_element( 'status', $p_bug->status, auth_get_current_user_id(), $p_bug->project_id )
                    );

                    # print username instead of status
                    if( ( ON == config_get( 'show_assigned_names' ) ) && ( $p_bug->handler_id > 0 ) && ( access_has_project_level( config_get( 'view_handler_threshold' ), $p_bug->project_id ) ) ) {
                        printf( ' (%s)', prepare_user_name( $p_bug->handler_id ) );
                    }
                    echo '</td>';
                    break;

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
                                  'EVENT_UPDATE_BUG'             => array( 'check_issue',
                                                            'bug_monitor_add' ),
                                  'EVENT_MANAGE_PROJECT_UPDATE'  => 'subprojects_change_status_enabled',
                                  'EVENT_UPDATE_BUG_STATUS_FORM' => array( 'issue_assignate_to',
                                                            'add_me_to_monitor_form' ),
                                  'EVENT_LAYOUT_CONTENT_END'     => 'file_upload_multiple_options',
                                  'EVENT_REPORT_BUG_FORM_TOP'    => 'event_report_bug_project_access',
                                  'EVENT_MENU_MANAGE'            => 'config_menu',
                                  'EVENT_VIEW_BUG_EXTRA'         => 'bug_monitor_list_view',
                                  'EVENT_MANAGE_PROJECT_CREATE'  => 'create_project_copy_users',
        );
        return $hooks;
    }

    function create_project_copy_users( $p_type_event, $p_project_id ) {

        $t_parent_id = project_hierarchy_get_parent( $p_project_id, TRUE );

        if( 0 != $t_parent_id ) {
            project_copy_users( $p_project_id, $t_parent_id, access_get_project_level( $p_project_id ) );
        }
    }

    function bug_monitor_list_view( $p_type_event, $p_bug_id ) {
        ?> 
        <script>
            if (document.getElementsByName("monitors").length > 0) {
                document.getElementsByName("monitors")[0].hidden = true;
            }

        </script>
        <?php
        if( access_has_bug_level( config_get( 'show_monitor_list_threshold' ), $p_bug_id ) ) {

            $t_users   = bug_get_monitors( $p_bug_id );
            $num_users = sizeof( $t_users );

            echo '<a name="monitors_by_service_desk" id="monitors_by_service_desk" /><br />';

            collapse_open( 'monitoring' );
            ?>
            <table class="width100" cellspacing="1">
                <tr>
                    <td class="form-title" colspan="2">
                        <?php
                        collapse_icon( 'monitoring' );
                        ?>
                        <?php echo lang_get( 'users_monitoring_bug' ); ?>
                    </td>
                </tr>
                <tr class="row-1">
                    <td class="category" width="15%">
                        <?php echo lang_get( 'monitoring_user_list' ); ?>
                    </td>
                    <td>
                        <?php
                        if( 0 == $num_users ) {
                            echo lang_get( 'no_users_monitoring_bug' );
                        } else {
                            $t_can_delete_others = access_has_bug_level( config_get( 'monitor_delete_others_bug_threshold' ), $p_bug_id );
                            for( $i = 0; $i < $num_users; $i++ ) {
                                echo ($i > 0) ? ', ' : '';
                                echo print_user( $t_users[$i] );
                                if( $t_can_delete_others ) {
                                    echo ' [<a class="small" href="' . plugin_page( 'bug_monitor_delete' ) . '&bug_id=' . $p_bug_id . '&user_id=' . $t_users[$i] . form_security_param( 'bug_monitor_delete' ) . '">' . lang_get( 'delete_link' ) . '</a>]';
                                }
                            }
                        }

                        if( access_has_bug_level( config_get( 'monitor_add_others_bug_threshold' ), $p_bug_id ) ) {
                            echo '<br /><br />', lang_get( 'username' );

                            // Получаем массив пользователей, привязанных к текущему проекту
                            $project_users = project_get_all_user_rows( bug_get_field( $p_bug_id, 'project_id' ) );
                            ?>
                            <form method="post" action="<?php echo plugin_page( 'bug_monitor_add' ) ?>">
                                <?php echo form_security_field( 'bug_monitor_add' ) ?>
                                <input type="hidden" name="bug_id" value="<?php echo (integer) $p_bug_id; ?>" />
                                <?php //Мультиселект. Можно выбрать несколько отслеживающих пользователей           ?>
                                <?php if( is_array( $project_users ) && count( $project_users ) > 0 ): ?>			
                                    <select size="8" multiple name="user_ids[]">
                                        <?php foreach( $project_users as $project_user ): ?>
                                            <?php if( !empty( $project_user['id'] ) && !empty( $project_user['realname'] ) ): ?>
                                                <option value="<?php echo $project_user['id']; ?>"><?php echo $project_user['realname']; ?></option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select><br><br>
                                <?php endif; ?>

                                <input type="submit" class="button" value="<?php echo lang_get( 'add_user_to_monitor' ) ?>" />
                            </form>
                        <?php } ?>
                    </td>
                </tr>
            </table>
            <?php
            collapse_closed( 'monitoring' );
            ?>
            <table class="width100" cellspacing="1">
                <tr>
                    <td class="form-title" colspan="2"><?php collapse_icon( 'monitoring' ); ?>
                        <?php echo lang_get( 'users_monitoring_bug' ); ?>
                    </td>
                </tr>
            </table>
            <?php
            collapse_end( 'monitoring' );
            ?>

            <?php
        } # show monitor list
    }

    function config_menu() {
        return array( '<a href="' . plugin_page( 'config' ) . '">' . plugin_lang_get( 'config' ) . ': ' . plugin_lang_get( 'name' ) . '</a>', );
    }

    function event_report_bug_project_access( $p_type_event, array $p_project_id ) {

        if( in_array( $p_project_id, plugin_config_get( 'projects_id_event_report_access' ) ) ) {
            print_header_redirect( '/login_select_proj_page.php?ref=bug_report_page.php' );
        }
    }

    function file_upload_multiple_options( $p_type_event, array $p_bug_id ) {

        if( plugin_config_get( 'file_upload_multiple' ) ) {
            ?> 
            <script>

                if (document.getElementsByName("ufile[]").length > 0) {
                    document.getElementsByName("ufile[]")[0].multiple = true;
                }

            </script>
            <?php
        }
    }

    function check_issue( $type_event, $t_bug_data, $f_bug_id ) {

        if( plugin_config_get( 'check_comments' ) ) {
            if( trim( $_REQUEST["bugnote_text"] ) == null && $_REQUEST["status"] != 90 && $_REQUEST["status"] != null ) {
                error_parameters( plugin_lang_get( 'error_empty_field' ) );
                trigger_error( ERROR_EMPTY_FIELD, ERROR );
            }
        }
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
                ?>

                <script>

                    document.getElementsByName("handler_id")[0].disabled = true;

                </script>

                <?php
            }
        }
    }

    function add_me_to_monitor_form( $p_type_event, $p_bug_id ) {

        if( !bug_is_user_reporter( $p_bug_id, auth_get_current_user_id() ) && gpc_get_int( 'new_status' ) == 50 && plugin_config_get( 'bug_monitor_run' ) ) {
            ?>
            <tr <?php echo helper_alternate_class() ?>>
                <td class="category">Добавить меня в отслеживающие</td>
                <td><input type="checkbox" name="add_me_to_monitor" id="add_me_to_monitor" value="true" checked></td>
            </tr>

            <?php
        }
    }

    function bug_monitor_add( $p_type_event, $p_bug_data, $p_bug_id ) {

        if( plugin_config_get( 'bug_monitor_run' ) ) {

            $f_add_monitor = gpc_get_bool( 'add_me_to_monitor', FALSE );

            if( $f_add_monitor ) {
                bug_monitor( $p_bug_id, auth_get_current_user_id() );
            }

            if( in_array( $p_bug_data->handler_id, bug_get_monitors( $p_bug_id ) ) ) {
                bug_unmonitor( $p_bug_id, $p_bug_data->handler_id );
            }
        }
    }

//    function tima_tracking_extendet() {
//
//        if( $t_bug->status == 40 ) { // Если переводим состояние из статуса В работе, в любой другой
//            // Получим историю изменений инцидента
//            // Найдем последнее состояние, когда инцидент был переведен в статус В работе
//            // Посчитаем время, которое инцидент провел в статусе В работе до нового статуса
//            // Запишем время в Учет времени
//
//            require_once( 'history_api.php' );
//
//            // Получим историю изменений инцидента
//            $arHistory          = history_get_events_array( $f_bug_id, null );
//            $arStatusInWorkDate = array();
//            // Найдем последнее состояние, когда инцидент был переведен в статус В работе
//            foreach( $arHistory as $arStatus ) {
//                if( $arStatus["note"] == 'Статус' && $arStatus["change"] == 'рассмотрен => В работе' ) {
//                    $arStatusInWorkDate[] = $arStatus["date"];
//                }
//            }
//
//            $d1                  = new \DateTime( array_pop( $arStatusInWorkDate ) );
//            $d2                  = new \DateTime( date( "Y-m-d H:i" ) );
//            $hours               = $d1->diff( $d2 );
//            $time_tracking_hours = $hours->format( '%m/%d/%h:%i' );
//
//            $time_tracking_hours = explode( "/", $time_tracking_hours );
//
//            $time_tracking_month         = $time_tracking_hours[0];
//            $time_tracking_days          = $time_tracking_hours[1];
//            $time_tracking_hours_minutes = explode( ":", $time_tracking_hours[2] );
//            $time_tracking_hours         = $time_tracking_hours_minutes[0];
//            $time_tracking_minutes       = $time_tracking_hours_minutes[1];
//
//            if( $time_tracking_hours > 5 ) {
//                $errors_show = 'style="display:block;"';
//                $errors_text = "Внимание! Затраченное время превышает 5ч!";
//            }
//
//            if( $time_tracking_days > 0 )
//                $time_tracking_hours = $time_tracking_days * 24;
//            $time_tracking       = $time_tracking_hours . ":" . $time_tracking_minutes;
//        }
//        else {
//            $time_tracking = "0:00";
//        }
//    }
//    function custom_function_override_print_bug_view_page_custom_buttons( $p_bug_id ) {
//
//            $t_bug = bug_get( $p_bug_id );
//
//            # make sure status is allowed of assign would cause auto-set-status
//            # workflow implementation
//            if( ON == config_get( 'auto_set_status_to_assigned' ) && !bug_check_workflow( $t_bug->status, config_get( 'bug_assigned_status' ) ) ) {
//
//                # make sure current user has access to modify bugs.
//                if( !access_has_bug_level( config_get( 'update_bug_assign_threshold', config_get( 'update_bug_threshold' ) ), $t_bug->id ) ) {
//                    return;
//                }
//
//                $t_current_user_id   = auth_get_current_user_id();
//                $t_new_status        = ( ON == config_get( 'auto_set_status_to_assigned' ) ) ? config_get( 'bug_assigned_status' ) : $t_bug->status;
//                $t_options           = array();
//                $t_default_assign_to = null;
//
//                if( ( $t_bug->handler_id != $t_current_user_id ) && access_has_bug_level( config_get( 'handle_bug_threshold' ), $t_bug->id, $t_current_user_id )
//                ) {
//                    $t_options[]         = array(
//                                              $t_current_user_id,
//                                              '[' . lang_get( 'myself' ) . ']',
//                    );
//                    $t_default_assign_to = $t_current_user_id;
//                }
//
//                if( ( $t_bug->handler_id != $t_bug->reporter_id ) && user_exists( $t_bug->reporter_id ) && access_has_bug_level( config_get( 'handle_bug_threshold' ), $t_bug->id, $t_bug->reporter_id )
//                ) {
//                    $t_options[] = array(
//                                              $t_bug->reporter_id,
//                                              '[' . lang_get( 'reporter' ) . ']',
//                    );
//
//                    if( $t_default_assign_to === null ) {
//                        $t_default_assign_to = $t_bug->reporter_id;
//                    }
//                }
//                
//                echo '<td class="center">';
//                echo "<form method=\"post\" action=\"bug_assign.php\">";
//                echo form_security_field( 'bug_assign' );
//
//                $t_button_text = lang_get( 'bug_assign_to_button' );
//                echo "<input type=\"submit\" class=\"button\" value=\"$t_button_text\" />";
//
//                echo " <select name=\"handler_id\">";
//
//                # space at beginning of line is important
//
//                $t_already_selected = false;
//
//                foreach( $t_options as $t_entry ) {
//                    $t_id      = string_attribute( $t_entry[0] );
//                    $t_caption = string_attribute( $t_entry[1] );
//
//                    # if current user and reporter can't be selected, then select the first
//                    # user in the list.
//                    if( $t_default_assign_to === null ) {
//                        $t_default_assign_to = $t_id;
//                    }
//
//                    echo '<option value="' . $t_id . '" ';
//
//                    if( ( $t_id == $t_default_assign_to ) && !$t_already_selected ) {
//                        check_selected( $t_id, $t_default_assign_to );
//                        $t_already_selected = true;
//                    }
//
//                    echo '>' . $t_caption . '</option>';
//                }
//
//                # allow un-assigning if already assigned.
//                if( $t_bug->handler_id != 0 ) {
//                    echo "<option value=\"0\"></option>";
//                }
//
//                # 0 means currently selected
//                print_assign_to_option_list( 0, $t_bug->project_id );
//                echo "</select>";
//
//                $t_bug_id = string_attribute( $t_bug->id );
//                echo "<input type=\"hidden\" name=\"bug_id\" value=\"$t_bug_id\" />\n";
//
//                echo "</form>\n";
//                echo '</td>';
//            } else {
//                return;
//            }
//        }
}
