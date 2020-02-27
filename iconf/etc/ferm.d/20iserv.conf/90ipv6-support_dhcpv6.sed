s/proto udp dport 547 jump input_dhcpv6_server;/proto udp interface ($DHCPInterfaces) dport 547 jump input_dhcpv6_server;\
      proto udp dport 547 jump REJECT;/g
