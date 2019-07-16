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

my $dhcp_handle = path "/var/lib/iserv/config/ipv6-dhcp-interfaces.list";
my %dhcp_interfaces = map { $_ => 1 } $dhcp_handle->lines_utf8;
my $static_network_handle = path "/etc/network/interfaces.d/ipv6";
my @lines_static_network = $static_network_handle->lines_utf8;

for my $file (glob "/var/lib/iserv/ipv6-support/ula/*.uln")
{
  my $nic = basename $file, ".uln";
  next if not exists $link_local_ips->{$nic};
  next if exists $dhcp_interfaces{$nic};
  grep { /^iface $nic inet6 dhcp$/ } @lines_static_network and next;

  my $handle = path $file;
  my @lines = $handle->lines_utf8;
  my $prefix = ((shift @lines) =~ s/::$//gr);
  my $glue = "";
  # only add glue between prefix and suffix if suffix doesn't already have
  # double points (suffix starting with zero)
  $glue = ":" unless $link_local_ips->{$nic} =~ /^::/;

  print "auto $nic\n";
  print "iface $nic inet6 static\n";
  print "        address " . $prefix . $glue . $link_local_ips->{$nic} . "\n";
  print "        netmask 64\n";
  print "\n";
}
