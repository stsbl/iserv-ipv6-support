#!/usr/bin/perl -CSDAL

use warnings;
use strict;
use File::Basename;
use Path::Tiny;

my $link_local_ips = {};

for my $row (split /\n/, qx(netquery6 -lp "nic\tsuffix"))
{
  my ($nic, $suffix) = split /\t/, $row;
  $link_local_ips->{$nic} = $suffix;
}

for my $file (glob "/var/lib/iserv/ipv6-support/ula/*.uln")
{
  my $nic = basename $file, ".uln";
  next if not exists $link_local_ips->{$nic};

  my $handle = path $file;
  my @lines = $handle->lines_utf8;
  my $prefix = ((shift @lines) =~ s/::$//gr);

  print "auto $nic\n";
  print "iface $nic inet6 static\n";
  print "        address " . $prefix . $link_local_ips->{$nic} . "\n";
  print "        netmask 64\n";
  print "\n";
}
