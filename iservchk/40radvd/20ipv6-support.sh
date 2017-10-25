#!/bin/bash

# check if we have routes to announce
if [ "$(netquery6 --global --lan --format nic | uniq | wc -l)" -gt 0 ]
then
  echo "Check /etc/radvd.conf"
  echo "Start radvd"
else
  echo "Remove /etc/radvd.conf"
  echo "Stop radvd"
fi
