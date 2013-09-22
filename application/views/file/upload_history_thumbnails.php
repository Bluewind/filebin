<div class="pull-right">
	<?php echo form_open("file/do_delete/", array("id" => "delete_form", "style" => "display: inline")); ?>
		<button class="btn btn-danger" id="delete_button" style="display: none">Delete selected</button>
	</form>
	<button class="btn btn-default" id="toggle_delete_mode" style="display: inline">Delete mode</button>
</div>

<?php include 'nav_history.php'; ?>

<!-- Comment markers background: http://stackoverflow.com/a/14776780/953022 -->
<div class="upload_history_thumbnails"><!--
	<?php foreach($query as $key => $item): ?>
		--><a href="<?php echo site_url("/".$item["id"]); ?>" title="<?php echo htmlentities($item["filename"]); ?>" data-content="<?php echo htmlentities($item["tooltip"]); ?>" data-id="<?php echo $item["id"]; ?>"><img class="thumb" src="<?php echo site_url("file/thumbnail/".$item["id"]); ?>"></a><!--
	<?php endforeach; ?>
	-->
</div>

<div class="row-fluid">
	<div class="span12 alert alert-block alert-info">
		<h4 class="alert-heading">Notice!</h4>
		<p>
		Currently only jpeg, png and gif images are displayed here. If you are
		looking for something else, please switch to the
		<a href="<?php echo site_url("file/upload_history"); ?>">list view</a>
		which contains your complete history.
		</p>
	</div>
</div>
