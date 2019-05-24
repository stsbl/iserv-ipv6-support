#!/bin/bash

# Determine default IP or fallback
DEFAULT_IP="$(netquery -p ip | head -1 | grep . || echo "127.0.0.1" )"
DEFAULT_IPv6=("$(netquery6 -g ip | head -n 1)" "$(netquery6 -u ip | head -n 1)")
# Determine IP on interface ICONF_SCHEMA_P0
IPv4="$(netquery if ip | grep -E "^${ICONF_SCHEMA_P0:-invalid} " | awk '{print $2}' | head -n 1 | grep . || echo "$DEFAULT_IP" )"

for i in $(netquery6 -gu -i "${ICONF_SCHEMA_P0:-invalid}" ip | grep . || echo "${DEFAULT_IPv6[@]}")
do
  echo -e "@\t\tAAAA\t$i"
  echo -e "wpad\t\tAAAA\t$i"
  echo -e "$(hostname -s)\t\tAAAA\t$i"
  echo -e "ipv6\t\tAAAA\t$i"
done

echo -e "ipv4\t\tA\t$IPv4"
echo
