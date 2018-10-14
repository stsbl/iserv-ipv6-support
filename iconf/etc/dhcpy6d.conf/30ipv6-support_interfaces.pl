#!/usr/bin/perl -CSDAL

my $global_ips = map { my ($nic, $ip) = split /\t/, $row; } split /\n/, qx(netquery6 -lg "nic\tip");

for $row (split /\n/, qx(netquery6 -lu "nic\tip\tprefix"))
{
  my ($nic, $ip, $prefix) = split /\t/, $row;
  print "[class_$nic]\n";
  print "addresses = eth1\n";
  print "interface = $nic\n";
  print "nameserver = $ip";
  if (exists $global_ips->{$nic})
  {
    print "  $global_ips->{$nic}";
  }
  print "\n";
  print "filter_mac = .*\n";
  print "\n";

  print "[address_$nic]\n";
  print "# Choosing MAC-based addresses.\n";
  print "category = mac\n";
  print "# ULA-type address pattern.\n";
  print "pattern = $prefix\$mac\$\n";
  print "\n"
}

