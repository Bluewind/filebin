<div class="row">
	<div class="col-sm-12">
		<h1>Account deletion</h1>
		<p>
			Here you can permanently delete your account on this FileBin installation.<br>
			<b>WARNING: All your data will be irrevocably deleted.</b>
		</p>
	</div>
</div>

<?php echo form_open("user/delete_account"); ?>
	<div class="row">
		<div class="form-group col-lg-8 col-md-10">
			<label class="control-label col-lg-2 col-md-2" for="inputPassword">Password</label>
			<div class="col-lg-5 col-md-5">
				<input type="password" id="inputPassword" name="password" placeholder="Password" class="form-control">
			</div>
		</div>
	</div>
	<div class='row'>
		<div class="form-group col-lg-8 col-md-10">
			<div class="col-lg-offset-2 col-lg-5 col-md-offset-2 col-md-5">
				<button type="submit" name="delete" class="form-control btn-danger">Delete my account (<?php echo htmlentities($username); ?>)</button>
			</div>
		</div>
	</div>
</form>
