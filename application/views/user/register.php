<?php if (!empty($error)) {
	echo "<p class='alert alert-danger'>";
	echo implode("<br />\n", $error);
	echo "</p>";
} ?>
<?php echo form_open('user/register/'.$key, array("class" => "form-horizontal")); ?>
<div class="row">
	<div class="form-group col-lg-8 col-md-10">
		<label class="control-label col-lg-2 col-md-2" for="inputUsername">Username</label>
		<div class="col-lg-5 col-md-5">
			<input type="text" id="inputUsername" name="username" placeholder="Username" value="<?php echo $values["username"]; ?>" class="form-control">
		</div>
	</div>
</div>

<div class="row">
	<div class="form-group col-lg-8 col-md-10">
		<label class="control-label col-lg-2 col-md-2" for="inputEmail">Email</label>
		<div class="col-lg-5 col-md-5">
			<input type="text" id="inputEmail" name="email" placeholder="Email" value="<?php echo $values["email"]; ?>" class="form-control">
		</div>
	</div>
</div>

<div class="row">
	<div class="form-group col-lg-8 col-md-10">
		<label class="control-label col-lg-2 col-md-2" for="inputPassword">Password</label>
		<div class="col-lg-5 col-md-5">
			<input type="password" id="inputPassword" name="password" placeholder="Password" class="form-control">
		</div>
	</div>
</div>

<div class="row">
	<div class="form-group col-lg-8 col-md-10">
		<label class="control-label col-lg-2 col-md-2" for="inputPassword">Confirm password</label>
		<div class="col-lg-5 col-md-5">
			<input type="password" id="inputPasswordConfirm" name="password_confirm" placeholder="Password confirmation" class="form-control">
		</div>
	</div>
</div>

<div class="row">
	<div class="form-group col-lg-8 col-md-10">
		<div class="col-lg-offset-2 col-lg-5 col-md-offset-2 col-md-5">
			<button type="submit" class="btn btn-primary" name="process">Register</button>
		</div>
	</div>
</div>
</form>

