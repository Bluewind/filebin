<?php
if (!empty($error)) {
	echo "<p class='alert alert-error'>";
	echo implode("<br />\n", $error);
	echo "</p>";
}

if ($hash) {
	echo "<p>Result (this hash uses a random salt, so it will be different each time you submit this form):<br />$hash</p>\n";
}
?>
<?php echo form_open('user/hash_password', array("class" => "form-horizontal")); ?>
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
			<button type="submit" class="btn btn-primary" name="process">Hash it</button>
		</div>
	</div>
</form>

