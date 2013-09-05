<?php if (!empty($error)) {
	echo "<p class='alert alert-danger'>";
	echo implode("<br />\n", $error);
	echo "</p>";
} ?>
<?php echo form_open('user/reset_password/'.$key, array("class" => "form-horizontal")); ?>
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
			<button type="submit" class="btn btn-primary" name="process">Change password</button>
		</div>
	</div>
</form>

