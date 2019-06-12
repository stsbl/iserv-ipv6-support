#!/bin/bash

DEFAULT_IPv6=("$(netquery6 -gl ip | head -n 1 | grep . || echo "::1")" "$(netquery6 -ul ip | head -n 1 | grep . || echo "::1")")

for i in $(netquery6 -gu -i "${ICONF_SCHEMA_P0:-invalid}" ip | grep . || { echo "${DEFAULT_IPv6[@]}" | sort -u; })
do
  echo -e "@\t\tAAAA\t$i"
  echo
done
