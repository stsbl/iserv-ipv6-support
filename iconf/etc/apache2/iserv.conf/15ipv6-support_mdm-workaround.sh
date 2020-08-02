#!/bin/bash

. /usr/lib/iserv/cfg

if dpkg-query -Wf '${Status}' iserv-mdm 2>/dev/null | grep -qE '^(install|hold) ok (unpacked|installed)$'
then
  echo "# ipv6-support: Redirect MDM API to IPv4 as module does not fully"
  echo "# support IPv6 yet"
  echo "  RewriteCond %{HTTP_HOST} !^ipv4\.${Domain//\./\\.}$"
  echo "  RewriteRule ^/iserv/public/mdm/ios/api$ https://ipv4.$Domain%{REQUEST_URI} [R=307,END]"
  echo
fi

exit 0
