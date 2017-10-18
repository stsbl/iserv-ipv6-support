#!/bin/bash

. /usr/lib/iserv/cfg

if [ $Activation ]
then
  # allow only activated computers
  if [ -s /etc/squid/proxyonly_ips6 ]
  then
    echo "# Show proxy info page for intercepted http requests"
    echo "  acl proxyonly6 src \"/etc/squid/proxyonly_ips6\""
    echo "  acl intercepted localport 80"
    echo "  http_access deny proxyonly6 intercepted"
    echo "  icp_access deny proxyonly6 intercepted"
    echo -n "  deny_info http://${Servername}/idesk/inet/noproxy.php proxyonly6 "
    echo "intercepted"
    echo
  fi
  echo "# Allow activated computers"
  echo "  acl active6 src \"/etc/squidguard/active_ips6\""
  echo "  http_access allow active6"
  echo "  icp_access allow active6"
  echo
fi

