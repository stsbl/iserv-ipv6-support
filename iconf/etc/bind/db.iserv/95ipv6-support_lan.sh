#!/bin/bash

for i in $(netquery6 -gul -i "${ICONF_SCHEMA_P0:-invalid}" ip)
do
  echo -e "@\t\tAAAA\t$i"
  echo -e "wpad\t\tAAAA\t$i"
  echo
done
