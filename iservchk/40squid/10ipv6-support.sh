#!/bin/bash

if netquery6 -gulq
then
  cat <<EOT
MkDir 0750 proxy:proxy /var/spool/squid-block
MkDir 0755 root:root /etc/squid-block
Check /etc/squid-block/squid-block.conf
Remove /etc/init.d/squid-block

Test 'squid block cache directory'
  [ -d /var/spool/squid-block/00 ]
  ---
  service squid-block stop; /usr/sbin/squid -f /etc/squid-block/squid-block.conf -z

# Ensure that /run/squid-block.pid corresponds to the PID of the (squid-1) process.
# This can break if two iservchk instances restart squid at the same time. Only
# the first instance will continue running, but all other started instances
# will clobber the PID file, which breaks squid3 -k check:
#   squid: ERROR: Could not send signal 0 to process 4172: (3) No such process
Test "pidfile corresponds to the correct squid-block instance"
  # a non-existing pidfile means squid isn't running; Start will fix that
  [ -f /run/squid-block.pid ] || exit 0

  if [ "\$(iservdebianrelease)" = "stretch" ]
  then
    pid=\$(pidof "(squid-1)" | tr ' ' '\n' | grep -oE "\$(cat /run/squid-block.pid)")
  else
    pid=\$(pidof "squid-block")
  fi

  pidfile="\$(cat /run/squid-block.pid)"
  [ "\$pid" = "\$pidfile" ] || exit 1
  ---
  # kill running instance
  killall squid-block
  # wait for it to shutdown
  for i in \$(seq 1 300)
  do
    killall -q -s 0 squid || break
    sleep 0.1
  done
  if killall -q -s 0 squid
  then
    echo "timeout while waiting for squid-block to shutdown" >&2
    exit 1
  fi
  rm -f /run/squid-block.pid

Test "squid-block swap.state too big"
  ! [ -e /var/spool/squid-block/swap.state ] ||
    ! find /var/spool/squid-block/swap.state -size +500M | grep -q .
  ---
  /etc/init.d/squid-block stop
  rm -f /var/spool/squid-block/swap.state
  mv /var/spool/squid-block /var/spool/squid-block.old
  mkdir /var/spool/squid-block
  chown --reference /var/spool-block/squid.old /var/spool/squid
  chmod --reference /var/spool-block/squid.old /var/spool/squid
  squid -z -f /etc/squid-block/squid-block.conf 2> /dev/null
  /etc/init.d/squid-block start
  nohup rm -rf /var/spool/squid-block.old

Test "squid-block service"
  systemctl is-enabled -q squid-block.service
  ---
  systemctl enable squid-block.service

# Intentionally does not use iservchk command as squid-block normally is
# started along with squid.service
Test "start squid-block"
  [[ \$(systemctl is-active squid-block.service) = active ]]
  ---
  systemctl start squid-block.service

EOT
else
  echo "Stop squid-block"
  echo
fi
