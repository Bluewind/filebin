<?php if(auth_driver_function_implemented("can_register_new_users")) { ?>
<li><a href="<?php echo site_url("user/invite") ?>"><i class="icon-plus icon-black"></i> Invite</a></li>
<?php } ?>

<li><a href="<?php echo site_url("user/profile") ?>"><i class="icon-user icon-black"></i> Profile</a></li>

<?php if(auth_driver_function_implemented("can_reset_password")) { ?>
<li><a href="<?php echo site_url("user/reset_password") ?>"><i class="icon-lock icon-black"></i> Change password</a></li>
<?php } ?>

