#!/bin/bash

. /usr/lib/iserv/cfg

if [ "$UseDHCPv6" ] && [ "$DHCP" ] && netquery6 -gulq
then
  echo 'Test "generate duid"'
  echo '  [ -s "/var/lib/iserv/ipv6-support/duid" ]'
  echo '  ---'
  echo '  dhcpy6d --generate-duid > /var/lib/iserv/ipv6-support/duid'
  echo
  echo 'Check /etc/default/dhcpy6d'
  echo 'Check /etc/dhcpy6d.conf'
  echo 'Start dhcpy6d dhcpy6d'
else
  echo 'Remove /etc/dhcpy6d.conf'
  echo
  echo 'Stop dhcpy6d dhcpy6d'
fi
echo

