<h1>Client</h1>

<p>
	Development (git): <?php echo anchor("http://git.server-speed.net/users/flo/fb/"); ?><br />
	Latest release: <?php echo $client_link ? anchor($client_link) : "unknown"; ?><br />
	GPG sigs, older versions: <?php echo anchor("https://paste.xinu.at/data/client"); ?>
</p>

<h2>Linux</h2>
<p>
	Arch Linux: pacman -S fb-client<br />
	Gentoo: Add <a href="https://git.holgersson.xyz/holgersson-overlay/tree/README">this overlay</a> and run <code>emerge -a fb-client</code><br />
</p>

