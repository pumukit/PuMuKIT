# PHP 7.2 security patches

PHP 7.2 is EOL upstream. These patches are the security backports maintained by
Ondřej Surý for the `php7.2` package on deb.sury.org, applied on top of the
vanilla `php-7.2.34.tar.xz` by `../../Dockerfile`.

Source: `php7.2_7.2.34-70+debsuryorg12.1+php+1.debian.tar.xz`
from https://packages.sury.org/php/pool/main/p/php7.2/ (`debian/patches/`).

It includes the backports from PHP 8.2.36 (7.2.34-69 in `debian/changelog`):
CVE-2026-129538, CVE-2026-129496, CVE-2026-129289, CVE-2026-129292,
CVE-2026-129291, CVE-2026-128090, CVE-2026-126714, CVE-2026-115560,
CVE-2026-15353, CVE-2026-129506, CVE-2026-128064 and CVE-2026-127960.

`series` is Surý's `debian/patches/series` without the patches that are
Debian packaging or not relevant for this Alpine build:

| Excluded               | Reason                                                        |
|------------------------|---------------------------------------------------------------|
| 0001-0051              | Debian packaging (libtool, paths, system tzdata, apache, pkg-config, m68k/hurd, FPM numeric UID/GID) |
| 0052-0055              | Debian FPM tests / phpize packaging                           |
| 0061, 0062, 0253       | libenchant 2 (enchant is not built)                           |
| 0063, 0064, 0067, 0080 | Packaging credits / README                                    |
| 0085, 0086, 0087, 0254 | OpenSSL 3 / newer ICU compatibility (not in Alpine 3.10)      |
| 0116                   | Changes the default OPcache optimization level                |
| 0141, 0142             | glibc readdir_r / LiteSpeed SAPI                              |
| 0143, 0257             | DNS version telemetry (not a fix)                             |
| 0220-0248              | Debian test suite adjustments (0225 also changes gd behaviour) |
| 0255, 0256             | configure.ac / imap config.m4 changes (configure is not regenerated) |

Since 7.2.34-66 Surý renumbered the patches and added his own backport of
"Avoid state corruption via bailout" (CVE-2026-109618/CVE-2026-109619,
0160 + 0162), which replaces our previous hand-made 0159 adaptation.

To update with a newer Surý revision: download the new `debian.tar.xz`, copy
the new patches appended to its `series` (skipping any packaging-only ones)
and add them here in the same order. Check that they still apply with
`git apply --exclude='*/tests/*'` on `php-7.2.34`.
