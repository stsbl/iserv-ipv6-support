CREATE TABLE host_dhcp6 (
  ip              INET            NOT NULL,
  mac             TEXT            NOT NULL CHECK (mac ~ '^([0-9a-f]{2}:){5}[0-9a-f]{2}$'),
  active          BOOLEAN         NOT NULL DEFAULT true,
  date            TIMESTAMPTZ(0)  NOT NULL DEFAULT now(),
  UNIQUE (ip, mac)
);

CREATE TABLE host_ipv6 (
 id               SERIAL          PRIMARY KEY,
 host_id          INT             NOT NULL REFERENCES hosts(id),
 ipv6_iid         INET            NOT NULL,
 interface        TEXT            NOT NULL
);

GRANT SELECT, INSERT, UPDATE, DELETE ON host_ipv6 TO symfony;
GRANT SELECT, USAGE ON host_ipv6_id_seq TO symfony;
