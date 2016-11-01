<div class="nav-history">
	<div class="container">
		<div class="pull-right">
			<?php echo form_open("file/handle_history_submit/", array("id" => "submit_form", "style" => "display: inline")); ?>
				<button type="submit" class="btn btn-danger" style="display: none" name='process' value='delete'>Delete selected</button>
				<button type="submit" class="btn btn-primary" style="display: none" name='process' value='multipaste'>Add selected to multipaste queue</button>
			</form>
			<button class="btn btn-default" id="toggle_select_mode" style="display: inline">Select mode</button>
		</div>

	<?php include 'nav_history.php'; ?>
	</div>
</div>
<?php include 'fragments/thumbnail.php'; ?>

<div class="row-fluid">
	<div class="span12 alert alert-block alert-info">
		<h4 class="alert-heading">Notice!</h4>
		<p>
		Currently only images and pdf files are displayed here. If you are
		looking for something else, please switch to the
		<a href="<?php echo site_url("file/upload_history"); ?>">list view</a>
		which contains your complete history.
		</p>
	</div>
</div>
