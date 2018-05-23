/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


if (jQuery("#monitoring").length > 0) {
    jQuery("#monitoring").attr("hidden", "true");
}

jQuery("form[action='manage_proj_user_add.php']").attr("action", jQuery("#manage_proj_user_add_var").attr("value"));
jQuery("form[action='manage_proj_user_remove.php']").attr("action", jQuery("#manage_proj_user_remove_var").attr("value"));

jQuery("form[action='manage_user_proj_add.php']").attr("action", jQuery("#manage_user_proj_add_var").attr("value"));
jQuery("form[action='manage_user_proj_delete.php']").attr("action", jQuery("#manage_user_proj_delete_var").attr("value"));
