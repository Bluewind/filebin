<div class="alert alert-block">
	<p>
		<b>Watch out!</b>
	</p>
	<p>
		You are free to invite anyone you want to, but please keep in
		mind that if this person violates the rules and is banned, your
		account will also be disabled.
	</p>
</div>

<h2>Unused invitation keys</h2>
<table class="table table-striped">
	<thead>
		<tr>
			<th>#</th>
			<th style="width: 70%;">Key</th>
			<th>Created on</th>
		</tr>
	</thead>
	<tbody>
		<?php $i = 1; ?>
		<?php foreach($query as $key => $item): ?>
			<tr>
				<td><?php echo $i++; ?></td>
				<td><?php echo anchor("user/register/".$item["key"], $item["key"]) ?></td>
				<td><?php echo date("Y/m/d H:i", $item["date"]) ?></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>

<p>
	<?php echo form_open('user/create_invitation_key'); ?>
	  <input class="btn btn-primary btn-large" type="submit" value="Create a new key" name="process" />
	</form>
</p>
