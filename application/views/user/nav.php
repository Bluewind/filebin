<?php if(auth_driver_function_implemented("can_register_new_users")) { ?>
<li><a href="<?php echo site_url("user/invite") ?>"><span class="glyphicon glyphicon-plus"></span> Invite</a></li>
<?php } ?>

<li><a href="<?php echo site_url("user/profile") ?>"><span class="glyphicon glyphicon-user"></span> Profile</a></li>
<li><a href="<?php echo site_url("user/apikeys") ?>"><span class="glyphicon glyphicon-tags"></span> API keys</a></li>

<?php if(auth_driver_function_implemented("can_reset_password")) { ?>
<li><a href="<?php echo site_url("user/reset_password") ?>"><span class="glyphicon glyphicon-lock"></span> Change password</a></li>
<?php } ?>

