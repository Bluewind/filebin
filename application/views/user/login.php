<?php 
if (isset($login_error)) { ?>
	<div class="alert alert-danger">The entered credentials are invalid.</div>
<?php } ?>

<?php echo form_open("user/login?redirect_uri=$redirect_uri", array("class" => "form-horizontal login-page")); ?>
	<div class="form-group">
		<label class="control-label" for="inputUsername">Username</label>
		<div class="controls">
			<input type="text" id="inputUsername" name="username" placeholder="Username" class="form-control">
		</div>
	</div>

	<div class="form-group">
		<label class="control-label" for="inputPassword">Password</label>
		<div class="controls">
			<input type="password" id="inputPassword" name="password" placeholder="Password" class="form-control">
		</div>
	</div>

	<div class="form-group">
		<div class="controls">
			<button type="submit" class="btn btn-primary" name="process">Login</button>
		</div>
	</div>
</form>
