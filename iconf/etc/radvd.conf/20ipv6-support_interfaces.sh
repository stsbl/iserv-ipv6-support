#!/bin/bash

. /usr/lib/iserv/cfg

for i in $(netquery6 --global --wan --format nic | uniq)
do
  echo "interface $i {"
  echo "  AdvSendAdvert on;"
  echo "  AdvDefaultLifetime 0;"
  echo
  echo "  MinRtrAdvInterval 3;"
  echo "  MaxRtrAdvInterval 10;"
  echo
  for h in $(netquery6 --global --format "nic prefix/length" | grep -v $i | awk '{ print $2 }')
  do
    echo "  route $h {"
    echo "  };"
  done
  echo "};"
  echo
done

if [[ $(netquery6 --global --format nic --lan | uniq | wc -l) -gt 0 ]]
then
  for i in $(netquery6 --global --format nic --lan | uniq)
  do
    echo "interface $i {"
    echo "  AdvSendAdvert on;"
    echo
    echo "  AdvManagedFlag $(if [ "$UseDHCPv6" ]; then echo "on"; else echo "off"; fi);"
    echo "  AdvOtherConfigFlag $(if [ "$UseDHCPv6" ]; then echo "on"; else echo "off"; fi);"
    echo
    echo "  MinRtrAdvInterval 3;"
    echo "  MaxRtrAdvInterval 10;"
    echo
    for h in $(netquery6 --global --lan --format "nic prefix/length" | uniq | grep -E "^$i\s" | awk '{ print $2 }')
    do
      echo "  prefix $h {"
      echo "    AdvOnLink on;"
      echo "    AdvAutonomous $(if [ "$UseDHCPv6" ]; then echo "off"; else echo "on"; fi);"
      echo "    AdvRouterAddr on;"
      echo "  };"
      echo
      echo "  RDNSS $(netquery6 --global --lan --format "nic ip" | grep -E "^$i\s" | awk '{ print $2 }') {"
      echo "    AdvRDNSSLifetime 300;"
      echo "  };"
    done
    echo "};"
    echo
  done
fi
