<?php if (!empty($error)) {
	echo "<p class='alert alert-error'>";
	echo implode("<br />\n", $error);
	echo "</p>";
} ?>
<?php echo form_open('user/register/'.$key, array("class" => "form-horizontal")); ?>
	<div class="control-group">
		<label class="control-label" for="inputUsername">Username</label>
		<div class="controls">
			<input type="text" id="inputUsername" name="username" placeholder="Username" value="<?php echo $values["username"]; ?>">
		</div>
	</div>

	<div class="control-group">
		<label class="control-label" for="inputEmail">Email</label>
		<div class="controls">
			<input type="text" id="inputEmail" name="email" placeholder="Email" value="<?php echo $values["email"]; ?>">
		</div>
	</div>

	<div class="control-group">
		<label class="control-label" for="inputPassword">Password</label>
		<div class="controls">
			<input type="password" id="inputPassword" name="password" placeholder="Password">
		</div>
	</div>

	<div class="control-group">
		<label class="control-label" for="inputPassword">Confirm password</label>
		<div class="controls">
			<input type="password" id="inputPasswordConfirm" name="password_confirm" placeholder="Password confirmation">
		</div>
	</div>

	<div class="control-group">
		<div class="controls">
			<button type="submit" class="btn btn-primary" name="process">Register</button>
		</div>
	</div>
</form>

