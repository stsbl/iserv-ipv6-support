#!/bin/bash

. /usr/lib/iserv/cfg

if dpkg-query -Wf '${Status}' iserv-mdm 2>/dev/null | grep -qE '^(install|hold) ok (unpacked|installed)$'
then
  cat <<EOT
# ipv6-support: Redirect MDM API to IPv4 as module does not fully
# support IPv6 yet
set \$ipv6_support_mdm_redirect_checks "";
if (\$host != "ipv4.$Domain") {
    set \$ipv6_support_mdm_redirect_checks "\${ipv6_support_mdm_redirect_checks}1";
}
if (\$request_uri = "$PortalBasePath/public/mdm/ios/api") {
    set \$ipv6_support_mdm_redirect_checks "\${ipv6_support_mdm_redirect_checks}1";
}

if (\$ipv6_support_mdm_redirect_checks = "11") {
    return 307 \$scheme://ipv4.$Domain\$request_uri;
    break;
}

EOT
fi

exit 0
