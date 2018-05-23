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

auth_reauthenticate();
access_ensure_global_level( config_get( 'manage_plugin_threshold' ) );

layout_page_header( plugin_lang_get( 'name' ) );

layout_page_begin( 'manage_overview_page.php' );

print_manage_menu();
?>

<div class="col-md-12 col-xs-12">
    <div class="space-10"></div>
    <div class="form-container">
        <form action="<?php echo plugin_page( 'config_edit' ) ?>" method="post">
            <?php echo form_security_field( 'service_desk_config_edit' ) ?>
            <div class="widget-box widget-color-blue2">
                <div class="widget-header widget-header-small">
                    <h4 class="widget-title lighter">
                        <i class="ace-icon fa fa-cubes"></i>
                        <?php echo plugin_lang_get( 'name' ) . ': ' . plugin_lang_get( 'config' ) ?>
                    </h4>
                </div>

                <div class="widget-body">
                    <div class="widget-main no-padding">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-condensed table-hover">
                                <colgroup>
                                    <col style="width:25%" />
                                    <col style="width:25%" />
                                    <col style="width:25%" />
                                </colgroup>

                                <tr <?php echo helper_alternate_class() ?>>
                                    <td class="category" width="60%">
                                        <?php echo plugin_lang_get( 'process_disable_project' ) ?>
                                    </td>
                                    <td class="center" width="20%">
                                        <label><input type="radio" name="process_disable_project" value="1" <?php echo( TRUE == plugin_config_get( 'process_disable_project' ) ) ? 'checked="checked" ' : '' ?>/>
                                            <?php echo plugin_lang_get( 'enabled' ) ?></label>
                                    </td>
                                    <td class="center" width="20%">
                                        <label><input type="radio" name="process_disable_project" value="0" <?php echo( FALSE == plugin_config_get( 'process_disable_project' ) ) ? 'checked="checked" ' : '' ?>/>
                                            <?php echo plugin_lang_get( 'disabled' ) ?></label>
                                    </td>
                                </tr>



                                <tr <?php echo helper_alternate_class() ?>>
                                    <td class="category" width="60%">
                                        <?php echo plugin_lang_get( 'check_comments' ) ?>
                                    </td>
                                    <td class="center" width="20%">
                                        <label><input type="radio" name="check_comments" value="1" <?php echo( TRUE == plugin_config_get( 'check_comments' ) ) ? 'checked="checked" ' : '' ?>/>
                                            <?php echo plugin_lang_get( 'enabled' ) ?></label>
                                    </td>
                                    <td class="center" width="20%">
                                        <label><input type="radio" name="check_comments" value="0" <?php echo( FALSE == plugin_config_get( 'check_comments' ) ) ? 'checked="checked" ' : '' ?>/>
                                            <?php echo plugin_lang_get( 'disabled' ) ?></label>
                                    </td>
                                </tr>

                                <tr <?php echo helper_alternate_class() ?> >
                                    <td class="category">
                                        <?php echo plugin_lang_get( 'status_to' ) ?>
                                    </td>
                                    <td style="vertical-align: top">
                                        <?php
                                        $t_temp = MantisEnum::getAssocArrayIndexedByValues( lang_get( 'status_enum_string' ) );
                                        $tt     = plugin_config_get( 'bug_status_array' );

                                        foreach( MantisEnum::getAssocArrayIndexedByValues( config_get( 'status_enum_string' ) ) as $t_index_enum => $t_status_enum ) {
                                            ?>

                                            <label><input type="checkbox" name="bug_status_array[]" value="<?php echo $t_index_enum ?>" <?php echo( TRUE == ( $tt == null ? FALSE : in_array( $t_index_enum, $tt ) ) ) ? 'checked="checked" ' : '' ?>/><?php echo $t_temp[$t_index_enum] ?></label>
                                            <br>

                                        <?php } ?>
                                    </td>
                                    <td style="vertical-align: top">
                                        <?php
                                        $t_temp = MantisEnum::getAssocArrayIndexedByValues( lang_get( 'status_enum_string' ) );
                                        $tt     = plugin_config_get( 'bug_status' );

                                        foreach( MantisEnum::getAssocArrayIndexedByValues( config_get( 'status_enum_string' ) ) as $t_index_enum => $t_status_enum ) {
                                            ?>

                                            <label><input type="radio" name="bug_status" value="<?php echo $t_index_enum ?>" <?php echo $tt == $t_index_enum ? 'checked="checked" ' : '' ?>/><?php echo $t_temp[$t_index_enum] ?></label>
                                            <br>

                                        <?php } ?>
                                    </td>
                                </tr>




                                <tr <?php echo helper_alternate_class() ?> >
                                    <td class="category">
                                        <?php echo plugin_lang_get( 'status_block' ) ?>
                                    </td>
                                    <td colspan="1" style="vertical-align: top">
                                        <?php
                                        $t_temp = MantisEnum::getAssocArrayIndexedByValues( lang_get( 'status_enum_string' ) );
                                        $tt     = plugin_config_get( 'bug_status_block_assignation_array' );

                                        foreach( MantisEnum::getAssocArrayIndexedByValues( config_get( 'status_enum_string' ) ) as $t_index_enum => $t_status_enum ) {
                                            ?>

                                            <label><input type="checkbox" name="bug_status_block_assignation_array[]" value="<?php echo $t_index_enum ?>" <?php echo( TRUE == ( $tt == null ? FALSE : in_array( $t_index_enum, $tt ) ) ) ? 'checked="checked" ' : '' ?>/><?php echo $t_temp[$t_index_enum] ?></label>
                                            <br>

                                        <?php } ?>
                                    </td>
                                    <td style="vertical-align: top"></td>
                                </tr>

                                <tr <?php echo helper_alternate_class() ?>>
                                    <td class="category" width="60%">
                                        <?php echo plugin_lang_get( 'bug_monitor_run_title' ) ?>
                                    </td>
                                    <td class="center" width="20%">
                                        <label><input type="radio" name="bug_monitor_run" value="1" <?php echo( TRUE == plugin_config_get( 'bug_monitor_run' ) ) ? 'checked="checked" ' : '' ?>/>
                                            <?php echo plugin_lang_get( 'enabled' ) ?></label>
                                    </td>
                                    <td class="center" width="20%">
                                        <label><input type="radio" name="bug_monitor_run" value="0" <?php echo( FALSE == plugin_config_get( 'bug_monitor_run' ) ) ? 'checked="checked" ' : '' ?>/>
                                            <?php echo plugin_lang_get( 'disabled' ) ?></label>
                                    </td>
                                </tr>

<!--        <tr <?php echo helper_alternate_class() ?>>
            <td class="category" width="60%">
                                <?php echo plugin_lang_get( 'file_upload_multiple_title' ) ?>
            </td>
            <td class="center" width="20%">
                <label><input type="radio" name="file_upload_multiple" value="1" <?php echo( TRUE == plugin_config_get( 'file_upload_multiple' ) ) ? 'checked="checked" ' : '' ?>/>
                                <?php echo plugin_lang_get( 'enabled' ) ?></label>
            </td>
            <td class="center" width="20%">
                <label><input type="radio" name="file_upload_multiple" value="0" <?php echo( FALSE == plugin_config_get( 'file_upload_multiple' ) ) ? 'checked="checked" ' : '' ?>/>
                                <?php echo plugin_lang_get( 'disabled' ) ?></label>
            </td>
        </tr>-->

                                <tr <?php echo helper_alternate_class() ?>>
                                    <td class="category" width="60%">
                                        <?php echo plugin_lang_get( 'projects_id_event_report_access' ) ?>

                                    </td>
                                    <td colspan="2" width="20%">
                                        <select name="project_id_report_access[]" size="10" multiple="multiple">
                                            <?php
                                            print_project_option_list( plugin_config_get( 'projects_id_event_report_access' ), FALSE );
                                            ?>
                                        </select>
                                    </td>
                                </tr>

                                <tr>
                                    <td class="center" colspan="3">
                                        <input type="submit" class="button" value="<?php echo lang_get( 'change_configuration' ) ?>" />
                                    </td>
                                </tr>

                            </table>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php
if( !function_exists( 'html_page_bottom' ) ) {
    layout_page_end();
} else {
    html_page_bottom();
}
