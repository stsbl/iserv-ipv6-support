#!/bin/sh

if [ -f "/var/lib/dpkg/info/iserv-pxe.list" ]
then
  echo 'option dhcp6.user-class code 15 = string;'
  echo 'option dhcp6.bootfile-url code 59 = string;'
  echo 'option dhcp6.client-arch-type code 61 = array of unsigned integer 16;'
  echo
fi
