<div class="pull-right">
	<?php echo form_open("file/do_delete/", array("id" => "delete_form", "style" => "display: inline")); ?>
		<button class="btn btn-danger" id="delete_button" style="display: none">Delete selected</button>
	</form>
	<button class="btn btn-default" id="toggle_delete_mode" style="display: inline">Delete mode</button>
</div>

<?php include 'nav_history.php'; ?>
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
