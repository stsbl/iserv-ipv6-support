#!/usr/bin/perl -CSDAL

use warnings;
use strict;
use File::Basename;
use Path::Tiny;
use Stsbl::IServ::Net::IPv6;

my $link_local_ips = {};

for my $row (split /\n/, qx(netquery6 -lp "nic\tlength"))
{
  my ($nic, $length) = split /\t/, $row;
  next unless $length eq 64;
  my $iid = qx(/usr/lib/iserv/ipv6_iid $nic);
  chomp $iid;
  $link_local_ips->{$nic} = $iid;
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
  print "auto $nic\n";
  print "iface $nic inet6 static\n";
  print "	address " . join_prefix_and_suffix($prefix, 64, $link_local_ips->{$nic}, 64)  . "/64\n";
  print "	autoconf 1\n";
  print "\n";
}
