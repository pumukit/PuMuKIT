# PHP 7.2 security patches

PHP 7.2 is EOL upstream. These patches are the security backports maintained by
Ondřej Surý for the `php7.2` package on deb.sury.org, applied on top of the
vanilla `php-7.2.34.tar.xz` by `../../Dockerfile`.

Source: `php7.2_7.2.34-66+0~20261021.122+debian12~1.gbpe0bcd4.debian.tar.xz`
from https://packages.sury.org/php/pool/main/p/php7.2/ (`debian/patches/`).

`series` is Surý's `debian/patches/series` without the patches that are
Debian packaging or not relevant for this Alpine build:

| Excluded               | Reason                                                        |
|------------------------|---------------------------------------------------------------|
| 0001-0050              | Debian packaging (libtool, paths, system tzdata, apache, pkg-config, m68k/hurd) |
| 0052-0056              | Debian FPM tests / phpize packaging                           |
| 0062, 0063             | libenchant 2 (enchant is not built)                           |
| 0064, 0065, 0068, 0081 | Packaging credits / README                                    |
| 0086, 0087, 0088       | OpenSSL 3 / newer ICU compatibility (not in Alpine 3.10)      |
| 0117                   | Changes the default OPcache optimization level                |
| 0142, 0143             | glibc readdir_r / LiteSpeed SAPI                              |
| 0144                   | DNS version telemetry (not a fix)                             |

To update with a newer Surý revision: download the new `debian.tar.xz`, copy
the new patches appended to its `series` (skipping any packaging-only ones)
and add them here in the same order.
