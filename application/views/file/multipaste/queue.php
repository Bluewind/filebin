<div class="multipasteQueue">
	<?php echo form_open("file/multipaste/form_submit", ["data-ajax_url" => site_url("file/multipaste/ajax_submit")]); ?>
		<div class="items"><!--
			<?php foreach ($items as $item) {?>
				--><div data-id="<?php echo $item['id']; ?>">
					<input type="hidden" name="ids[<?php echo $item['id']; ?>]" value="<?php echo $item['id']; ?>">
					<div class='item'>
						<?php if (isset($item['thumbnail'])) { ?>
							<img
								src="<?php echo $item['thumbnail']; ?>"
								title="<?php echo $item['title']; ?>"
								data-content="<?php echo $item['tooltip']; ?>">
						<?php } else { ?>
							<div>
								<?php echo $item['title']; ?><br>
								<?php echo $item['tooltip']; ?>
							</div>
						<?php } ?>
					</div>
					<button class='multipaste_queue_delete btn-danger btn btn-xs'>Remove</button>
				</div><!--
			<?php } ?>
		--></div>
		<button type="submit" class="btn btn-default" name="process" value="save">
			<div class="ajaxFeedback" style="display: none">
				<span class="glyphicon glyphicon-refresh spinning"></span>
			</div>
			Only save queue order
		</button>
		<button type="submit" class="btn btn-primary" name="process" value="create">Create multipaste</button>
	</form>
</div>
