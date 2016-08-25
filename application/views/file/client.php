<h1>Client</h1>

<p>
	Development (git): <?php echo anchor("http://git.server-speed.net/users/flo/fb/"); ?><br />
	Latest release: <?php echo $client_link ? anchor($client_link) : "unknown"; ?><br />
	GPG sigs, older versions: <?php echo anchor("https://paste.xinu.at/data/client"); ?>
</p>

<h2>Linux</h2>
<p>
	Arch Linux: pacman -S fb-client<br />
	Debian: <?php echo anchor("https://paste.xinu.at/data/client/deb"); ?><br />
	Gentoo: Add <a href="https://git.holgersson.xyz/holgersson-overlay/tree/README">this overlay</a> and run <code>emerge -a fb-client</code><br />
	Slackware: <?php echo anchor("https://paste.xinu.at/data/client/slackware"); ?><br />
	OpenSUSE: <?php echo anchor("https://build.opensuse.org/package/show/home:mwilhelmy/fb-client"); ?>
</p>

<h2>OS X</h2>
<p>
	Get <a href="http://brew.sh">Homebrew</a> and run <code>brew install fb-client</code>.
</p>
