#!/bin/bash

. /usr/lib/iserv/cfg

echo
for i in $(netquery6 --global --lan --format ip)
do
  echo -e "@\t\tAAAA\t$i"
  echo -e "wpad\t\tAAAA\t$i"
  echo -e "$(hostname -s)\t\tAAAA\t$i"
done

