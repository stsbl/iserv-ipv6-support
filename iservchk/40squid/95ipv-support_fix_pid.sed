s/pidof '(squid-1)'/pidof '(squid-1)' | grep -oE $(cat \/run\/squid.pid) | awk '{ print $1 }'/g
