<h2>API keys</h2>
<table class="table table-striped">
	<thead>
		<tr>
			<th>#</th>
			<th>Key</th>
			<th style="width: 30%;">Comment</th>
			<th>Created on</th>
			<th></th>
		</tr>
	</thead>
	<tbody>
		<?php $i = 1; ?>
		<?php foreach($query as $key => $item): ?>
			<tr>
				<td><?php echo $i++; ?></td>
				<td><?php echo $item["key"]; ?></td>
				<td><?php echo $item["comment"]; ?></td>
				<td><?php echo date("Y/m/d H:i", $item["created"]); ?></td>
				<td>
					<?php echo form_open("user/delete_apikey", array("style" => "margin-bottom: 0")); ?>
						<?php echo form_hidden("key", $item["key"]); ?>
						<button class="btn btn-danger btn-mini" type="submit">Delete</input>
					</form>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>

<p>
	<?php echo form_open('user/create_apikey', array("class" => "form-horizontal")); ?>
		<input type="text" name="comment" placeholder="Comment" />
		<input class="btn btn-primary" type="submit" value="Create a new key" name="process" />
	</form>
</p>
