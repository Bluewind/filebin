<?php 
if (isset($login_error)) { ?>
	<div class="alert alert-error">The entered credentials are invalid.</div>
<?php } ?>

<?php echo form_open('user/login', array("class" => "form-horizontal")); ?>
	<div class="control-group">
		<label class="control-label" for="inputUsername">Username</label>
		<div class="controls">
			<input type="text" id="inputUsername" name="username" placeholder="Username">
		</div>
	</div>

	<div class="control-group">
		<label class="control-label" for="inputPassword">Password</label>
		<div class="controls">
			<input type="password" id="inputPassword" name="password" placeholder="Password">
		</div>
	</div>

	<div class="control-group">
		<div class="controls">
			<button type="submit" class="btn btn-primary" name="process">Login</button>
		</div>
	</div>
</form>
