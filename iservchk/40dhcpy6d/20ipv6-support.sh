#!/bin/bash

. /usr/lib/iserv/cfg

if [ $UseDHCPv6 ] && [[ ${#DHCP} -gt 0 ]] && netquery6 -gulq
then
  echo 'Test "generate duid"'
  echo '  [ -s "/var/lib/iserv/ipv6-support/duid" ]'
  echo '  ---'
  echo '  dhcpy6d --generate-duid > /var/lib/iserv/ipv6-support/duid'
  echo
  echo '# There seems to be multiple instances of dhcpy6d sometimes leading to'
  echo '# log spam (see https://github.com/HenriWahl/dhcpy6d/issues/20)'
  echo 'Test "single instance running"'
  echo '  [ "$(pgrep -c dhcpy6d)" -lt 2 ]'
  echo '  ---'
  echo '  service stop dhcpy6d'
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

