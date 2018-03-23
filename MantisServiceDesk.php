<?php

# Copyright (c) 2017 Grigoriy Ermolaev (igflocal@gmail.com)
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

        $this->name = 'MantisServiceDesk';
        $this->description = '';

        $this->version = '0.0.1';
        $this->requires = array(
            'MantisCore' => '1.2.12',
            'jQuery' => '1.11.1'
        );

        $this->author = 'Grigoriy Ermolaev';
        $this->contact = 'igflocal@gmail.com';
        $this->url = 'http://github.com/mantisbt-plugins/MantisServiceDesk';
    }

    function schema() {

        return array(
            // version 0.0.1
            array( "CreateTableSQL", array( plugin_table( "events" ), "
					id INT(10) NOTNULL AUTOINCREMENT PRIMARY,
                                        project_id INT(10) NOTNULL,
                                        name VARCHAR(255) NOTNULL,
                                        tasks VARCHAR(2000) NOTNULL,
                                        date_event INT(10) UNSIGNED NOTNULL DEFAULT 1,
                                        hour_start INT(2) NOTNULL,
                                        minutes_start INT(2) NOTNULL,
                                        hour_finish INT(2) NOTNULL,
                                        minutes_finish INT(2) NOTNULL,
                                        activity CHAR(1) NOTNULL,
                                        author_id INT(10),
                                        date_changed INT(2),
                                        changed_user_id INT(10)
				" ) ),
            array( "CreateTableSQL", array( plugin_table( "relationship" ), "
                                        event_id INT(10) NOTNULL,
                                        bug_id INT(10) NOTNULL)
                                " ) ),
            // version 0.2.0
            array( "AddColumnSQL", array( plugin_table( "events" ), "
                                        date_submited INT(10) UNSIGNED NOTNULL DEFAULT 1,
                                        date_start_event INT(10) UNSIGNED NOTNULL DEFAULT 1,
                                        date_end_event INT(10) UNSIGNED NOTNULL DEFAULT 1
				" ) )
        );
    }

//    public function upgrade( $p_schema ) {
//        $tetw = 'qwswq';
//        return TRUE;
//    }

    function config() {
        return array(
            'startStepDays' => 0, // Начало рабочей недели
            'countStepDays' => 10, // Количество дней для отображения
//            'arWeekdaysName' => array( "Пн", "Вт", "Ср", "Чт", "Пт" ),
            'arWeekdaysName' => array( "Пн", "Вт", "Ср", "Чт", "Пт", "Сб", "Вс" ),
            'timeDayStart' => 9, // Рабочий день начинается в 9:00
            'timeDayFinish' => 18, // Рабочий день заканчивается в 18:00
            'stepDayMinutesCount' => 4, // Количество шагов интервала
            'stepDayMinutesCountForClock' => 2,
            'manage_calendar_threshold' => DEVELOPER,
            'calendar_view_threshold' => DEVELOPER,
        );
    }

    public function errors() {
        return array(
//            'InvalidPeriodString' => 'Invalid period string. Please use something like "4 days", "6 months" or "1 year"',
        );
    }

    function init() {
        require_once 'core/event_data_api.php';
        require_once 'core/calendar_date_api.php';
        require_once 'core/calendar_helper.php';
        require_once 'core/sorted_api.php';
        require_once 'core/calendar_filter_api.php';
        require_once 'core/calendar_event_layer.php';
        require_once 'core/calendar_view_layers.php';
        require_once 'core/form_event_add.php';
        require_once 'core/form_event_view.php';
        require_once 'core/form_event_edit.php';

        define( 'ERROR_EVENT_NOT_FOUND', 5100 );
    }

    function hooks() {
        $hooks = array(
            'EVENT_LAYOUT_RESOURCES' => 'resources',
            'EVENT_MENU_MAIN_FRONT' => 'menu_main_front',
//            'EVENT_VIEW_BUG_DETAILS' => 'html_print_calendar',
        );
        return $hooks;
    }

    function resources() {
        return
                '<link rel="stylesheet" type="text/css" href="' . plugin_file( 'calendar.css' ) . '"></link>';
    }

    function menu_main_front() {
        if( access_has_project_level( plugin_config_get( 'manage_calendar_threshold' ) ) ) {
            return array( '<a href="' . plugin_page( 'calendar_user_page' ) . '">' . plugin_lang_get( 'menu_main_front' ) . '</a>', );
        }
    }

    function html_print_calendar( $p_first_option, $p_bug_id ) {
        $t_project_id = gpc_get_int( 'project_id', helper_get_current_project() );
        $t_subprojects_id = project_hierarchy_get_all_subprojects( $t_project_id );
        $t_curent_bug_id = $p_bug_id;

        $t_bug_rows = filter_get_bug_rows( $f_page_number, $t_per_page, $t_page_count, $t_bug_count, null, null, null, true );
        $t_event_rows = get_events_row_from_bug_id( $t_curent_bug_id );

        $t_compare_events = compare_events_and_bugs( $t_event_rows, $t_bug_rows );

        $t_events_sorted_by_date = sorted_events_by_date( $t_compare_events );

        $t_event_sorted = sorted_events_by_date_by_time( $t_events_sorted_by_date );

        if( $t_event_sorted ) {
            $tempZ = array_keys( $t_event_sorted );

            //$startStepDays              = date("N")-1;
            //    $tempD = ;
            $tempW = $tempZ[count( $tempZ ) - 1];
            $tempE = $tempZ[0];
            $tempDate = ($tempE - $tempW) / 60 / 60 / 24;
            //    $tempDate = ($tempW - $tempE) / 60 / 60 / 24;
            $startStepDays = date( "N", $tempW ) - 1;
            $countStepDays = $tempDate + 1;
            $numberWeekFromDate = date( "W", $tempW );
        } else {
            $startStepDays = date( "N" ) - 1;
            $countStepDays = 1;
        }

        $arWeekdaysName = array( "Пн", "Вт", "Ср", "Чт", "Пт", "Сб", "Вс" );

        // Массив дней из номера недели
        $arDate = array_days_of_number_week( $startStepDays, $countStepDays, $arWeekdaysName, $numberWeekFromDate );


        echo '<tr class=calendar-area align="center"><td align="center" colspan="6">';
//        echo '<tr ', helper_alternate_class(), '><td align="center" colspan="6">';
//        echo '<tr class=category align="center"><td align="center" colspan="6">';
//        echo '</table>';

        print_html_calendar_form( $t_project_id, $t_subprojects_id, $startStepDays, $countStepDays, $arWeekdaysName, $arDate, $t_compare_events, $t_curent_bug_id, 9, 18, 1, true );

//        echo "<div class=\"calendar-area\">";
//        echo "<ul class=\"control-panel-bug\">";

        if( access_has_project_level( plugin_config_get( 'manage_calendar_threshold' ) ) ) {

            html_calendar_form_event_view_print();
            html_calendar_form_event_add_print( $t_bug_rows, 9, 18, 1 );
            html_calendar_form_event_edit_print( $t_bug_rows, 9, 18, 1 );
//
            echo "<div class=\"calendar-area\">";
            echo "<ul class=\"control-panel-bug\">";

            echo "<li><a href=\"javascript://\" id=\"calendar-form-add-event-show\">Добавить событие</a></li>";
            echo "<li><a target=\"_blank\" href=\"" . plugin_page( 'calendar_user_page' ) . "\" id=\"calendar-form-redirect-to-calendar\">Перейти в каледарь</a></li>";

            echo "</ul>";
            echo "</div>";
            echo "<script type=\"text/javascript\" src=\"" . plugin_file( '__script.js' ) . "\"></script>";
        }





//        echo '<table class="width100" cellspacing="1">';
////        echo '<tr class=spacer width=15%><td colspan=4></td></tr>';
//        echo '<tr class=row-2><td class=category>dkjfwef</td><td colspan=5>erververv</td></tr>';

        echo '</td></tr>';
    }

}
