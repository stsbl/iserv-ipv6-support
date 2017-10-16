#!/usr/bin/perl -CSDAL
use warnings;
use strict;
use IServ::Conf;

my %r;
for (split /\n/, qx(netquery6 --global --lan --format "ip prefix length nic"))
{
  my ($ip, $prefix, $length, $nic) = split;
  $r{$nic} .= "  subnet6 $prefix/$length {\n";
  $r{$nic} .= "    default-lease-time 86400;\n";
  $r{$nic} .= "    preferred-lifetime 86400;\n";
  $r{$nic} .= "\n";
  $r{$nic} .= "    option dhcp6.name-servers $ip;\n";
  $r{$nic} .= "    #option netbios-name-servers $ip;\n";
  $r{$nic} .= "    #option netbios-node-type 8;\n";
  $r{$nic} .= "    option dhcp6.sntp-servers $ip;\n";
  # some network cards don't work in the opsi bootimage with MTU 1500
  $r{$nic} .= "    option interface-mtu 1492;\n";

  my $range_prefix = substr($prefix, 0, -2);

  $r{$nic} .= "    range6 $range_prefix:f:f:0:0 $range_prefix:f:f:f:f;\n";
  $r{$nic} .= "  }\n\n";
}

my %f;
for (@{$conf->{DHCP}})
{
  if ($_ eq "*")
  {
    %f = %r;
  }
  elsif ($r{$_})
  {
    $f{$_} = $r{$_};
  }
  else
  {
    warn "Skipping unknown nic: $_\n";
  }
}

printf "shared-network $_ {\n\n$r{$_}}\n\n" for sort keys %f;

