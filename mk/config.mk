LOCALE_DISABLE_POOTLE_DOWNLOAD=1

TEST_TARGETS += ipv6_config_tests

.PHONY: ipv6_config_tests
ipv6_config_tests:
	prove -r tests
