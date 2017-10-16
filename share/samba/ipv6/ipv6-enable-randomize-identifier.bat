@echo off

netsh interface ipv6 set privacy state=enabled store=active
netsh interface ipv6 set privacy state=enabled store=persistent
netsh interface ipv6 set global randomizeidentifiers=enabled store=active
netsh interface ipv6 set global randomizeidentifiers=enabled store=persistent
