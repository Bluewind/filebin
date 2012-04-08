#!/usr/bin/perl
use warnings;
use strict;

open INPUT, "<", "geshi.css" or die $!;

my $last="";

while (<INPUT>) {
	my $line = $_;
	my ($lang, $rest) = split / /;
	$lang =~ s/\.//;
	$lang =~ s/^_+//;
	chomp $lang;

	next if ($lang eq "");

	if ($lang ne $last) {
		$last = $lang;
		unlink "paste-$lang.css";
		print STDERR "processing $lang\n";
	}
	open OUT, ">>", "paste-$lang.css";
	print OUT "$line";
	close OUT;
}
