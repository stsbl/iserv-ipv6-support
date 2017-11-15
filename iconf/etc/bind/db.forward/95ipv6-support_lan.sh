#!/bin/bash

. /usr/lib/iserv/cfg

echo
for i in $(netquery6 --global --lan --format ip; netquery6 --uniquelocal --format ip;)
do
  echo -e "@\t\tAAAA\t$i"
  echo -e "wpad\t\tAAAA\t$i"
  echo -e "$(hostname -s)\t\tAAAA\t$i"
  echo -e "ipv6\t\tAAAA\t$i"
done

for i in $(netquery -p ip)
do
  echo -e "ipv4\t\tA\t$i"
done

echo
