s/Do not accept IPv6 Router Advertisements/Accept IPv6 Router Advertisements/g
s/echo 0 > \/proc\/sys\/net\/ipv6\/conf\/"\$IFACE"\/accept_ra/echo 2 > \/proc\/sys\/net\/ipv6\/conf\/"$IFACE"\/accept_ra/g

