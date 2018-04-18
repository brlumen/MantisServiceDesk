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

class MantisServiceDeskPlugin extends MantisPlugin {

    function register() {

        $this->name = plugin_lang_get( 'name');
        $this->description = plugin_lang_get( 'description');
        $this->page = 'config';

        $this->version = '1.2.0';
        $this->requires = array(
            'MantisCore' => '1.2.12',
            'jQuery' => '1.11.1'
        );

        $this->author = 'Grigoriy Ermolaev';
        $this->contact = 'igflocal@gmail.com';
        $this->url = 'http://github.com/mantisbt-plugins/MantisServiceDesk';
    }

   /**
	 * Default plugin configuration.
	 */
	function config() {
		return array(
			'process_disable_project'		=> FALSE,
		);
	}

    public function errors() {
        return array(

        );
    }

    function init() {

    }

    function hooks() {
        $hooks = array(
            'EVENT_MENU_ISSUE' => 'change_issue',
            'EVENT_UPDATE_BUG' => 'check_issue',
            'EVENT_MANAGE_PROJECT_UPDATE' => 'subprojects_change_status_enabled',
        );
        return $hooks;
    }

    function check_issue( $type_event, $t_bug_data, $f_bug_id ) {
        if( trim( $_REQUEST["bugnote_text"] ) == null && $_REQUEST["status"] != 90 && $_REQUEST["status"] != null ) {
            error_parameters( plugin_lang_get( 'error_empty_field' ) );
            trigger_error( ERROR_EMPTY_FIELD, ERROR );
        }
    }

    function change_issue($f_type_issue, $f_bug_id) {
     
        $tpl_bug = bug_get( $f_bug_id, true );
        $t_fields = config_get( 'bug_view_page_fields' );
	$t_fields = columns_filter_disabled( $t_fields );
        $tpl_show_status = in_array( 'status', $t_fields );
        $tpl_status = $tpl_show_status ? string_display_line( get_enum_element( 'status', $tpl_bug->status ) ) : '';

        $current_user_id = auth_get_current_user_id();
        $assigned_user_id = $tpl_bug->handler_id;
        $status_id = $tpl_bug->status;
        $status_color = get_status_color( $status_id );
        $status_text = $tpl_status;

        $arStatus_enum_string = explode( ",", lang_get( 'status_enum_string' ) );
        foreach( $arStatus_enum_string as $value ) {
            $value = explode( ":", $value );
            $arStatus_enum[$value[0]] = $value[1];
        }

        if( ($current_user_id == $assigned_user_id && $status_id == 10 /* Новый */) && $_REQUEST["new_status"] == null || ( $current_user_id == $assigned_user_id && $status_id == 50 /* Назначен */ && $_REQUEST["new_status"] == null ) ) {
//            $tpl_bug->status = 30;     // Рассмотрен
//            $status_color = "#ffcd85";   // Оранжевый
//            $status_text = $arStatus_enum[30]; // Рассмотрен
//            $tpl_bug->update();
//            html_meta_redirect( 'view.php?id='.$f_bug_id );
              
            html_meta_redirect( 'bug_change_status_page.php?id='.$f_bug_id.'&new_status=30' );
        }
    }
    
    function subprojects_change_status_enabled( $p_event, $p_project_id ) {

        $t_subprojects = project_hierarchy_get_all_subprojects( $p_project_id, plugin_config_get( 'process_disable_project' ) );

        foreach( $t_subprojects as $t_subproject ) {

            $t_name                 = (string) project_get_field( $t_subproject, 'name' );
            $t_description          = (string) project_get_field( $t_subproject, 'description' );
            $t_status               = (int) project_get_field( $t_subproject, 'status' );
            $t_view_state           = (int) project_get_field( $t_subproject, 'view_state' );
            $t_file_path            = (string) project_get_field( $t_subproject, 'file_path' );
            $f_enabled              = gpc_get_bool( 'enabled' );
            $t_inherit_global       = (boolean) project_get_field( $t_subproject, 'inherit_global' );

            # Don't reveal the absolute path to non-administrators for security reasons
            if( is_blank( $t_file_path ) && current_user_is_administrator() ) {
                $t_file_path = config_get( 'absolute_path_default_upload_folder' );
            }

            project_update( $t_subproject, $t_name, $t_description, $t_status, $t_view_state, $t_file_path, $f_enabled, $t_inherit_global );
            event_signal( 'EVENT_MANAGE_PROJECT_UPDATE', array( $t_subproject ) );
        }
    }

}
