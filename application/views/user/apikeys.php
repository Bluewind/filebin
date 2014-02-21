<h2>API keys</h2>
<div class="table-responsive">
	<table class="table table-striped">
		<thead>
			<tr>
				<th>#</th>
				<th>Key</th>
				<th style="width: 30%;">Comment</th>
				<th>Created on</th>
				<th>Access</th>
				<th></th>
			</tr>
		</thead>
		<tbody>
			<?php $i = 1; ?>
			<?php foreach($query as $key => $item): ?>
				<tr>
					<td><?php echo $i++; ?></td>
					<td><?php echo $item["key"]; ?></td>
					<td><?php echo htmlentities($item["comment"]); ?></td>
					<td><?php echo date("Y/m/d H:i", $item["created"]); ?></td>
					<td>
						<?php if ($item["access_level"] == "full"): ?>
							<span class="glyphicon glyphicon-warning-sign"></span>
						<?php endif; ?>
						<?php echo $item["access_level"]; ?>
					</td>
					<td>
						<?php echo form_open("user/delete_apikey", array("style" => "margin-bottom: 0")); ?>
							<?php echo form_hidden("key", $item["key"]); ?>
							<button class="btn btn-danger btn-xs" type="submit">Delete</input>
						</form>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>

<h3>Access levels:</h3>

<dl class="dl-horizontal">
	<dt>basic</dt>
	<dd>Allows uploading files.</dd>
	<dt>apikey</dt>
	<dd>Allows removing existing files and viewing the history. Includes <code>basic</code>.</dd>
	<dt>full</dt>
	<dd>Allows everything, including, but not limited to, creating and removing api keys, changing profile settings and creating invitation keys. Includes <code>apikey</code>.</dd>

<p>
	<?php echo form_open('user/create_apikey', array("class" => "form-inline")); ?>
	<input type="text" name="comment" placeholder="Comment" class="form-control" style="width: 200px;"/>
	<select name="access_level" class="form-control" style="width: 100px;">
		<option>basic</option>
		<option selected="selected">apikey</option>
		<option>full</option>
	</select>
	<input class="btn btn-primary" type="submit" value="Create a new key" name="process" />
</form>
</p>
