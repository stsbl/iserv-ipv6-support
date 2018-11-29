#!/usr/bin/perl -CSDAL

use warnings;
use strict;
use IServ::Conf;

my %activate_dhcp = map { $_ => 1 } @{ $conf->{DHCP} };

my $global_ips = {};
my $global_prefixes = {};

for my $row (split /\n/, qx(netquery6 -lg "nic\tip\tprefix"))
{
  my ($nic, $ip, $prefix) = split /\t/, $row;
  $global_ips->{$nic} = $ip;
  $global_prefixes->{$nic} = $prefix;
}

for my $row (split /\n/, qx(netquery6 -lu "nic\tip\tprefix"))
{
  my ($nic, $ip, $prefix) = split /\t/, $row;
  next if not exists $activate_dhcp{$nic};

  print "[class_default_$nic]\n";
  print "addresses = $nic temp_$nic";
  print " global_$nic global_temp_$nic" if exists $global_prefixes->{$nic};
  print "\n";
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
  print "ia_type = na\n";
  print "\n";

  print "[address_temp_$nic]\n";
  print "# Choosing random addresses.\n";
  print "category = random\n";
  print "# ULA-type address pattern.\n";
  print "pattern = $prefix\$random64\$\n";
  print "ia_type = ta\n";
  print "\n";

  if (exists $global_prefixes->{$nic})
  {
    my $prefix = $global_prefixes->{$nic};
	print "[address_global_$nic]\n";
	print "# Choosing EUI-64-based addresses.\n";
	print "category = eui64\n";
	print "# ULA-type address pattern.\n";
	print "pattern = $prefix\$eui64\$\n";
    print "ia_type = na\n";
	print "\n";

    print "[address_global_temp_$nic]\n";
    print "# Choosing random addresses.\n";
    print "category = random\n";
    print "# ULA-type address pattern.\n";
    print "pattern = $prefix\$random64\$\n";
    print "ia_type = ta\n";
    print "\n";
  }

}

