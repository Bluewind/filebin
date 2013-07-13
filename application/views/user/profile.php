<?php echo form_open("user/save_profile", array("class" => "form-horizontal")); ?>

	<div class="control-group">
		<label class="control-label" for="inputUsername">Username</label>
		<div class="controls">
			<input type="text" id="inputUsername" name="username" placeholder="Username" disabled="disabled" value="<?php echo $profile_data["username"]; ?>">
		</div>
	</div>

	<?php if(auth_driver_function_implemented("get_email")) { ?>
	<div class="control-group">
		<label class="control-label" for="inputEmail">Email</label>
		<div class="controls">
			<input type="text" id="inputEmail" name="email" placeholder="Email" disabled="disabled" value="<?php echo $profile_data["email"]; ?>">
		</div>
	</div>
	<?php } ?>

	<div class="control-group">
		<label class="control-label" for="inputUploadIDLimits">Upload ID length limits</label>
		<div class="controls">
			<input type="text" id="inputUploadIDLimits" name="upload_id_limits" placeholder="number-number" value="<?php echo $profile_data["upload_id_limits"]; ?>">
			<span class="help-block">Values have to be between 3 and 64 inclusive. Please remember that longer IDs don't protect your pastes from being found if you post the link somewhere a search enginge can see it.</span>
		</div>
	</div>

	<div class="control-group">
		<div class="controls">
			<button type="submit" class="btn btn-primary" name="process">Update</button>
		</div>
	</div>

</form>
