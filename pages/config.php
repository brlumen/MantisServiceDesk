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

auth_reauthenticate( );
access_ensure_global_level( config_get( 'manage_plugin_threshold' ) );

html_page_top( plugin_lang_get( 'name' ) );

print_manage_menu( );

?>

<br />
<form action="<?php echo plugin_page( 'config_edit' )?>" method="post">
<?php echo form_security_field( 'service_desk_config_edit' ) ?>
<table align="center" class="width50" cellspacing="1">

<tr>
	<td class="form-title" colspan="3">
		<?php echo plugin_lang_get( 'name' ) . ': ' .  plugin_lang_get( 'config' )?>
	</td>
</tr>

<tr <?php echo helper_alternate_class( )?>>
	<td class="category" width="60%">
		<?php echo plugin_lang_get( 'process_disable_project' )?>
	</td>
	<td class="center" width="20%">
		<label><input type="radio" name="process_disable_project" value="1" <?php echo( TRUE == plugin_config_get( 'process_disable_project' ) ) ? 'checked="checked" ' : ''?>/>
			<?php echo plugin_lang_get( 'enabled' )?></label>
	</td>
	<td class="center" width="20%">
		<label><input type="radio" name="process_disable_project" value="0" <?php echo( FALSE == plugin_config_get( 'process_disable_project' ) ) ? 'checked="checked" ' : ''?>/>
			<?php echo plugin_lang_get( 'disabled' )?></label>
	</td>
</tr>

<tr>
	<td class="center" colspan="3">
		<input type="submit" class="button" value="<?php echo lang_get( 'change_configuration' )?>" />
	</td>
</tr>

</table>
</form>

<?php
html_page_bottom();
