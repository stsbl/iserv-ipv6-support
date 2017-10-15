#!/bin/sh

. /usr/lib/iserv/cfg

echo 'Check /etc/init.d/isc-dhcp-server6'
echo 'Check /etc/default/isc-dhcp-server6'
echo 'ChPerm 0755 root:root /etc/init.d/isc-dhcp-server6'

if [ "$DHCP" ] && netquery6 --global --lan --quiet
then
  echo 'Touch /var/lib/iserv/config/dhcpd6.conf.hosts'
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

