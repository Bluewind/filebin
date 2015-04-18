<?php include 'nav_history.php'; ?>
<?php echo form_open("file/do_delete") ?>
    <div class="table-responsive">
        <table id="upload_history" class="table table-striped tablesorter {sortlist: [[4,1]]}">
            <thead>
                <tr>
                    <th class="{sorter: false}"><input type="checkbox" name="all-ids" id="history-all"></th>
                    <th>ID</th>
                    <th>Filename</th>
                    <th>Mimetype
                    <th>Date</th>
                    <th>Size</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($items as $key => $item): ?>
                    <tr>
                        <td><input type="checkbox" name="ids[<?php echo $item["id"] ?>]" value="<?php echo $item["id"] ?>" class="delete-history"></td>
                        <td><a href="<?php echo site_url("/".$item["id"]) ?>/"><?php echo $item["id"] ?></a></td>
                        <td class="wrap"><?php echo htmlspecialchars($item["filename"]); ?></td>
                        <td><?php echo $item["mimetype"] ?></td>
                        <td class="nowrap" data-sort-value="<?=$item["date"]; ?>"><?php echo date("r", $item["date"]); ?></td>
                        <td><?php echo $item["filesize"] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <input class="btn btn-danger" type="submit" value="Delete checked" name="process">
</form>

<p>Total sum of your distinct uploads: <?php echo $total_size; ?>.</p>
