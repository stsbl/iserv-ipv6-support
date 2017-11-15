#!/bin/sh

if [ -e /etc/squid/goldlist.conf ]
then
  echo "# Allow sites listed in the goldlist"
  echo "  include /etc/squid/goldlist.conf"
  echo
fi

