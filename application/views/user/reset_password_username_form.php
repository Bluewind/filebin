<?php echo form_open('user/reset_password', array("class" => "form-horizontal")); ?>
	<div class="control-group">
		<label class="control-label" for="inputUsername">Username</label>
		<div class="controls">
			<input type="text" id="inputUsername" name="username" placeholder="Username" value="<?php echo isset($username) ? $username : ""; ?>">
		</div>
	</div>

	<div class="control-group">
		<div class="controls">
			<button type="submit" class="btn btn-primary" name="process">Send mail</button>
		</div>
	</div>
</form>

