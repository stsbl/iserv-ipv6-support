#!/usr/bin/perl -CSDAL

use warnings;
use strict;
use IServ::Conf;

my %activate_dhcp = map { $_ => 1 } @{ $conf->{DHCP} };

my $global_ips = {}; 

for my $row (split /\n/, qx(netquery6 -lg "nic\tip"))
{
  my ($nic, $ip) = split /\t/, $row;
  $global_ips->{$nic} = $ip;
}

for my $row (split /\n/, qx(netquery6 -lu "nic\tip\tprefix"))
{
  my ($nic, $ip, $prefix) = split /\t/, $row;
  next if not exists $activate_dhcp{$nic};

  print "[class_default_$nic]\n";
  print "addresses = $nic\n";
  print "interface = $nic\n";
  print "nameserver = $ip";
  if (exists $global_ips->{$nic})
  {
    print " $global_ips->{$nic}";
  }
  print "\n";
  print "filter_mac = .*\n";
  print "\n";

  print "[address_$nic]\n";
  print "# Choosing EUI-64-based addresses.\n";
  print "category = eui64\n";
  print "# ULA-type address pattern.\n";
  print "pattern = $prefix\$eui64\$\n";
  print "\n"
}

