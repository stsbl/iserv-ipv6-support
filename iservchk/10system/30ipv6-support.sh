#!/bin/sh

if [ "$(iservdebianrelease)" = "jessie" ]
then
  echo "Check /etc/logrotate.d/squid-block"
else
  echo "Remove /etc/logrotate.d/squid-block"
fi
echo
