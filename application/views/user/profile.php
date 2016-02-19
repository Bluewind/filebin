<?php echo form_open("user/profile"); ?>

<div class="row">
	<div class="form-group col-lg-8 col-md-10">
		<label class="control-label col-lg-2 col-md-2" for="inputUsername">Username</label>
		<div class="col-lg-5 col-md-5">
			<input type="text" id="inputUsername" name="username" placeholder="Username" disabled="disabled" value="<?php echo $profile_data["username"]; ?>" class="form-control">
		</div>
	</div>
</div>

<?php if($profile_data["email"] !== null) { ?>
<div class="row">
	<div class="form-group col-lg-8 col-md-10">
		<label class="control-label col-lg-2 col-md-2" for="inputEmail">Email</label>
		<div class="col-lg-5 col-md-5">
			<input type="text" id="inputEmail" name="email" placeholder="Email" <?php if(!auth_driver_function_implemented("can_change_email")) { ?>disabled="disabled" <?php } ?>value="<?php echo $profile_data["email"]; ?>" class="form-control">
		</div>
	</div>
</div>
<?php } ?>

<div class="row">
	<div class="form-group col-lg-8 col-md-10">
		<label class="control-label col-lg-2 col-md-2" for="inputUploadIDLimits">Upload ID length limits</label>
		<div class="col-lg-5 col-md-5">
			<input type="text" id="inputUploadIDLimits" name="upload_id_limits" placeholder="number-number" value="<?php echo $profile_data["upload_id_limits"]; ?>" class="form-control">
			<span class="help-block">Values have to be between 3 and 64 inclusive. Please remember that longer IDs don't protect your pastes from being found if you post the link somewhere a search engine can see it.</span>
		</div>
	</div>
</div>

<div class="row">
	<div class="form-group col-lg-8 col-md-10">
		<div class="col-lg-offset-2 col-lg-5 col-md-offset-2 col-md-5">
			<button type="submit" class="btn btn-primary" name="process">Save changes</button>
		</div>
	</div>
</div>
</form>
