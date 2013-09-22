<?php echo form_open('user/reset_password', array("class" => "form-horizontal")); ?>
<div class="row">
	<div class="form-group col-lg-8 col-md-10">
		<label class="control-label col-lg-2 col-md-2" for="inputUsername">Username</label>
		<div class="col-lg-5 col-md-5">
			<input type="text" id="inputUsername" name="username" placeholder="Username" value="<?php echo isset($username) ? $username : ""; ?>" class="form-control">
		</div>
	</div>
</div>

<div class="row">
	<div class="form-group col-lg-8 col-md-10">
		<div class="col-lg-offset-2 col-lg-5 col-md-offset-2 col-md-5">
			<button type="submit" class="btn btn-primary" name="process">Send mail</button>
		</div>
	</div>
</div>
</form>

