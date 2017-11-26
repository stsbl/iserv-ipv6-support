#!/bin/bash

. /usr/lib/iserv/cfg

for i in $(netquery6 --global --lan --format ip; netquery6 --uniquelocal --format ip;)
do
  echo -e "@\t\tAAAA\t$i"
  echo -e "wpad\t\tAAAA\t$i"
  echo
done
