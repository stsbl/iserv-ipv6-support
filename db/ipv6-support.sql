CREATE TABLE host_dhcp6 (
        ip              INET            NOT NULL,
        mac             TEXT            NOT NULL
                                        CHECK (mac ~
                                              '^([0-9a-f]{2}:){5}[0-9a-f]{2}$'),
        active          BOOLEAN         NOT NULL        DEFAULT true,
        date            TIMESTAMPTZ(0)  NOT NULL        DEFAULT now(),
        UNIQUE (ip, mac)
);
