#!/bin/sh

. /usr/lib/iserv/cfg

echo 'Touch /var/lib/dhcp/dhcpd6.leases'
echo 'Check /etc/init.d/isc-dhcp-server6'
echo 'Check /etc/default/isc-dhcp-server6'
echo 'ChPerm 0755 root:root /etc/init.d/isc-dhcp-server6'

if [ "$UseDHCPv6" ] && [ "$DHCP" ] && (netquery6 --global --lan --quiet || netquery6 --uniquelocal --lan --quiet)
then
  for i in $(netquery6 --global --lan --format nic)
  do
    echo 'Touch /var/lib/iserv/config/dhcpd6.conf.'$i'.hosts'
  done
  echo 'Touch /etc/dhcp/dhcpd6.conf.local'
  echo 'Check /etc/dhcp/dhcpd6.conf'
  echo
  echo 'Start isc-dhcp-server6 dhcpd6'
else
  echo 'Remove /etc/dhcp/dhcpd6.conf'
  echo
  echo 'Stop isc-dhcp-server6 dhcpd6'
fi
echo

