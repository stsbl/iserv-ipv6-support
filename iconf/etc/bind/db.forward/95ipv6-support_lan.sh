#!/bin/bash

# Determine default IP or fallback
DEFAULT_IP="$(netquery -p ip | head -1 | grep . || echo "127.0.0.1")"
DEFAULT_IPv6=("$(netquery6 -gl ip | head -n 1 | grep . || echo -n "")" "$(netquery6 -ul ip | head -n 1 | grep . || ([ "$ICONF_SCHEMA_P0" = "fallback" ] && echo "::1") || echo -n "")")
# Determine IP on interface ICONF_SCHEMA_P0
IPv4="$(netquery if ip | grep -E "^${ICONF_SCHEMA_P0:-invalid} " | awk '{print $2}' | head -n 1 | grep . || ([ "$ICONF_SCHEMA_P0" = "fallback" ] && echo "$DEFAULT_IP") || echo -n "" )"

for i in $(netquery6 -gu -i "${ICONF_SCHEMA_P0:-invalid}" ip | grep . || { echo "${DEFAULT_IPv6[@]}" | sort -u; })
do
  [ -n "$i" ] || continue
  echo -e "@\t\tAAAA\t$i"
  echo -e "$(hostname -s)\t\tAAAA\t$i"
  echo -e "ipv6\t\tAAAA\t$i"
done

if [ -n "$IPv4" ]
then
  echo -e "ipv4\t\tA\t$IPv4"
  echo
fi
