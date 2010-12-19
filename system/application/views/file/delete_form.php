<div style="text-align:center">
  <?php echo form_open('file/delete/'.$id); ?>
		<p>
			<?php if($msg) echo $msg."<br />"; ?>
			Password:<input type="password" name="password" size="10" />
			<input type="submit" value="Delete" name="process" />
    </p>
  </form>
</div>
