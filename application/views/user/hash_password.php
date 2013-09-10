<?php
if (!empty($error)) {
	echo "<p class='alert alert-danger'>";
	echo implode("<br />\n", $error);
	echo "</p>";
}

if ($hash) {
	echo "<p>Result (this hash uses a random salt, so it will be different each time you submit this form):<br />$hash</p>\n";
}
?>
<?php echo form_open('user/hash_password', array("class" => "form-horizontal")); ?>
	<div class="row">
	    <div class="form-group col-lg-10">
		    <label class="control-label col-lg-2" for="inputPassword">Password</label>
		    <div class="col-lg-5">
			    <input type="password" id="inputPassword" name="password" placeholder="Password" class="form-control">
		    </div>
	    </div>
	</div>

	<div class="row">
	    <div class="form-group col-lg-10">
		<label class="control-label col-lg-2" for="inputPassword">Confirm password</label>
		<div class="col-lg-5">
			<input type="password" id="inputPasswordConfirm" name="password_confirm" placeholder="Password confirmation" class="form-control">
		</div>
	    </div>
	</div>

	<div class="row">
	    <div class="form-group col-lg-10">
		<div class="col-lg-offset-2 col-lg-5">
			<button type="submit" class="btn btn-primary" name="process">Hash it</button>
		</div>
	</div>
</form>

