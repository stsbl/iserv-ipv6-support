#!/bin/bash

. /usr/lib/iserv/cfg

echo 'Touch /var/lib/dhcp/dhcpd6.leases'
echo 'Check /etc/init.d/isc-dhcp-server6'
echo 'Check /etc/default/isc-dhcp-server6'
echo 'ChPerm 0755 root:root /etc/init.d/isc-dhcp-server6'

if [ $UseDHCPv6 ] && [ "$DHCP" ] && netquery6 -gulq
then
  echo 'Test "generate duid"'
  echo '  [ -s "/var/lib/iserv/ipv6-support/duid" ]'
  echo '  ---'
  echo '  dhcpy6d --generate-duid > /var/lib/iserv/ipv6-support/duid'
  echo
  echo 'Check /etc/default/dhcpy6d'
  echo 'Check /etc/dhcpy6d.conf'
  echo '# Cannot check if process is alive :('
  echo '#Start dhcpy6d dhcpy6d'
  echo 'Start dhcpy6d'
else
  echo 'Remove /etc/dhcpy6d.conf'
  echo
  echo 'Stop dhcpy6d'
fi
echo

