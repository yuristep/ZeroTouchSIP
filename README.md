# ZeroTouchSIP for FreePBX 17

> FreePBX module for SIP phone provisioning, phone inventory, and centralized management of Yealink and Fanvil devices.

[![FreePBX 17](https://img.shields.io/badge/FreePBX-17-3E8EDE.svg)](https://www.freepbx.org/)
[![Module Category](https://img.shields.io/badge/Category-Connectivity-4CAF50.svg)](https://github.com/yuristep/ZeroTouchSIP)
[![License GPLv3+](https://img.shields.io/badge/License-GPLv3%2B-blue.svg)](https://www.gnu.org/licenses/gpl-3.0.txt)
[![Languages](https://img.shields.io/badge/Languages-en_US%20%7C%20ru_RU%20%7C%20zh_CN-orange.svg)](https://github.com/yuristep/ZeroTouchSIP)
[![Vendors](https://img.shields.io/badge/Vendors-Yealink%20%7C%20Fanvil-8E44AD.svg)](https://github.com/yuristep/ZeroTouchSIP)

**ZeroTouchSIP** (`rawname`: `zerotouchsip`) is a FreePBX 17 module for **automatic provisioning of SIP phones**, **centralized phone management**, and **inventory control** for **Yealink** and **Fanvil** endpoints. The module helps administrators manage provisioning URLs, network-based profiles, SIP settings, line assignments, BLF keys, and remote configuration refresh from the FreePBX web interface.

This README is written both for technical users and for discoverability in search engines around topics like:

- **FreePBX phone provisioning**
- **Yealink provisioning for FreePBX**
- **Fanvil auto provisioning**
- **SIP phone management module**
- **FreePBX endpoint provisioning**
- **DHCP Option 66 for IP phones**

Publisher: **YURI STEP. - Net Production -**  
License: **GPLv3+**

Language versions: **[English](README.md)** | **[Russian](docs/README_ru_RU.md)** | **[Chinese (Simplified)](docs/README_zh_CN.md)**

## Table of Contents

- [Features](#features)
- [Why ZeroTouchSIP](#why-zerotouchsip)
- [Architecture](#architecture)
- [Project Structure](#project-structure)
- [Screenshots](#screenshots)
- [Supported Phones](#supported-phones)
- [Quick Start](#quick-start)
- [Admin Interface](#admin-interface)
- [Database and Upgrade Flow](#database-and-upgrade-flow)
- [Phone Search in Phones List](#phone-search-in-phones-list)
- [Provisioning URL](#provisioning-url)
- [SIP Plug and Play (PnP)](#sip-plug-and-play-pnp)
- [DHCP and Auto Provisioning](#dhcp-and-auto-provisioning)
- [Notify and Remote Refresh](#notify-and-remote-refresh)
- [Programmable Key Types](#programmable-key-types)
- [Server File Locations](#server-file-locations)
- [Security Recommendations](#security-recommendations)
- [Troubleshooting](#troubleshooting)
- [Testing and Validation](#testing-and-validation)
- [SEO Keywords and Discovery](#seo-keywords-and-discovery)
- [Documentation](#documentation)
- [License](#license)

---

## Features

### Core Features

- **Phone inventory for FreePBX**: MAC, model, firmware, last IP, provisioning state, and line status
- **Zero-touch provisioning** for **Yealink** and **Fanvil** SIP phones over HTTP/HTTPS
- **Network-aware provisioning profiles** by **CIDR subnet**
- **Line and BLF management** with programmable keys
- **SIP NOTIFY / check-sync** support for remote configuration refresh
- **Provisioning URL management** through `/zerotouchsip`
- **Client-side phone search** with text, dial-pattern matching, and basic regular expressions
- **Automatic phone naming** based on assigned extension and model
- **SIP Plug and Play (PnP)** listener workflow for supported provisioning scenarios

### Technical Highlights

- **FreePBX-native admin UI** under `Connectivity -> ZeroTouchSIP`
- **Module install / upgrade flow** via `install.php`, `upgrade.php`, `uninstall.php`
- **Provisioning webroot integration** through `_zts_provisioning`
- **Network-specific SIP / NTP / codec settings**
- **Fanvil and Yealink provisioning templates**
- **Search without SQL filtering** on already loaded rows for faster UI interaction
- **Separated service / repository / validator structure** in PHP code

---

## Why ZeroTouchSIP

If you are searching for a **FreePBX module for Yealink provisioning**, a **Fanvil provisioning solution**, or a way to centralize **SIP phone provisioning on FreePBX 17**, ZeroTouchSIP is designed exactly for that use case.

Typical scenarios:

- hotel or office rollout with many room phones
- branch deployment where phones must receive config automatically
- migration from manual handset setup to centralized provisioning
- DHCP Option 66 based onboarding
- remote config refresh with SIP NOTIFY

---

## Architecture

### Module Layers

```text
┌──────────────────────────────────────────────────────────────┐
│  ADMIN UI        Phones │ Networks │ General Settings       │
├──────────────────────────────────────────────────────────────┤
│  SERVICES        Device edit │ Networks │ Notify │ PnP      │
├──────────────────────────────────────────────────────────────┤
│  DATA            zts_* tables │ repositories │ validators   │
├──────────────────────────────────────────────────────────────┤
│  PROVISIONING    boot.php │ config.php │ router.php │ cfg    │
├──────────────────────────────────────────────────────────────┤
│  PHONE VENDORS   Yealink templates │ Fanvil templates        │
└──────────────────────────────────────────────────────────────┘
```

**Key principle**: a single FreePBX module combines **phone inventory**, **provisioning**, **network profiles**, **SIP PnP**, and **maintenance operations**.

---

## Project Structure

```text
zerotouchsip/
├── assets/                         # JS/CSS for FreePBX admin UI
│   └── js/
│       ├── zts-list-pagination.js
│       ├── zts-list-search.js
│       ├── zts-linekeys-editor.js
│       └── zts-lines-editor.js
├── bin/                            # CLI helpers
│   └── sip-pnp-listen.php
├── docs/                           # Documentation and screenshots
│   └── screenshots/
├── i18n/                           # Locales and gettext files
├── includes/                       # PHP services, repositories, validators
│   └── Zts/
├── provisioning/                   # Provisioning entrypoints and templates
│   ├── boot.php
│   ├── config.php
│   ├── common.php
│   ├── router.php
│   └── fanvil/
│   └── yealink/
├── views/                          # FreePBX admin views
│   ├── zts_phones.php
│   ├── zts_phones_edit.php
│   ├── zts_networks.php
│   ├── zts_networks_edit.php
│   └── zts_general.php
├── Backup.php
├── Restore.php
├── install.php
├── upgrade.php
├── uninstall.php
├── module.xml
├── page.zerotouchsip.php
└── README.md
```

---

## Screenshots

The screenshots were captured from a real FreePBX 17 system. Sensitive data in the published images has been redacted.

### Phones - list

![Phones list - FreePBX SIP phone inventory with MAC, model, lines and PJSIP status](docs/screenshots/phones_list.png)

### Phones - edit

![Phones edit - FreePBX phone provisioning, line mapping and programmable keys](docs/screenshots/phones_edit.png)

### Networks - list

![Networks list - subnet-based provisioning profiles for FreePBX phones](docs/screenshots/networks_list.png)

### Networks - edit

![Networks edit - SIP server, provisioning auth and network settings for IP phones](docs/screenshots/networks_edit.png)

### General Settings

![General settings - provisioning URL, SIP PnP and provisioning diagnostics](docs/screenshots/general_edit.png)

---

## Supported Phones

### Yealink

- **T3x**: T31, T31G, T31P, T33G, T33P, T34W
- **T4x**: T42G/S/U, T46G/S/U, T48G/S/U
- **T5x**: T52S/W, T53/W, T54S/W, T56A, T57W, T58A/W

### Fanvil

- **H2U-V2**
- **H5**
- **H6W**
- compatible devices via `fanvil` provisioning profile heuristics

---

## Quick Start

### 1. Install the module

#### From GitHub

```bash
cd /var/www/html/admin/modules
git clone https://github.com/yuristep/ZeroTouchSIP.git zerotouchsip
fwconsole ma install zerotouchsip
fwconsole chown
```

Alternatively, download a release tarball such as `zerotouchsip-v17.0.0.tgz` and extract it into `admin/modules/zerotouchsip/`.

#### Development copy

```bash
cp -a zerotouchsip /var/www/html/admin/modules/zerotouchsip
fwconsole ma install zerotouchsip
fwconsole chown
```

### 2. Open the module in FreePBX

#### Connectivity -> ZeroTouchSIP

Direct URL:

```text
/admin/config.php?display=zerotouchsip&zerotouchsip_form=phones_list
```

### 3. Configure provisioning

1. Add one or more networks in **Networks**
2. Set **Provisioning Protocol**
3. Set **Provisioning Username / Password**
4. Set **SIP Server Address**
5. Point phones or DHCP Option 66 to `/zerotouchsip`

### 4. Add or discover phones

- manually create a phone record, or
- let phones hit provisioning URL and then assign lines in **Phones**

---

## Admin Interface

| Tab | Purpose |
| --- | --- |
| `Phones` | Phone list, provisioning state, notify actions, line assignment, naming |
| `Networks` | CIDR-based networks, provisioning auth, SIP/NTP/network policies |
| `General` | Provisioning URL, SIP PnP, passwords, diagnostics, defaults |

Admin entrypoint: `display=zerotouchsip` with `zerotouchsip_form=<form_name>`.

---

## Database and Upgrade Flow

ZeroTouchSIP stores its data in **`zts_*`** tables. Current schema is created from `install.php`; upgrades are applied by `upgrade.php`.

Typical upgrade flow:

```bash
fwconsole ma upgrade zerotouchsip
# or after file copy:
fwconsole ma installlocal zerotouchsip
```

Verify schema on the PBX:

```bash
mysql asterisk -e "SHOW TABLES LIKE 'zts_%';"
```

Provisioning webroot:

- shared module dir: `admin/modules/_zts_provisioning/`
- public URL: `/zerotouchsip`

---

## Phone Search in Phones List

The **Search** field above the phones table filters rows **without reloading the page**. This is useful for FreePBX SIP phone inventory workflows, especially when searching by extension, MAC address, model, or dial pattern.

### Plain Text Search

Plain text is matched as a case-insensitive substring across row fields such as phone name, MAC, vendor, model, firmware, assigned lines, last IP, PJSIP status, and more.

| Query | Matches |
| --- | --- |
| `fanvil` | Fanvil phones |
| `192.168` | IP-related row data |
| `1001` | Any row containing `1001` |

### FreePBX Dial Pattern Search

If the query looks like a dial pattern, the search is applied to the **extensions** assigned to the phone lines.

| Pattern | Meaning |
| --- | --- |
| `X` | Any digit `0-9` |
| `Z` | Any digit `1-9` |
| `N` | Any digit `2-9` |
| `.` | Any sequence (`.*`) |
| `[123]` | One character from the set |
| `[0-9]` | One digit from range |

A leading `_` is ignored: `_6XX` is treated the same as `6XX`.

Examples:

| Query | Meaning |
| --- | --- |
| `6[123][01][0-9]` | Four-digit extension pattern |
| `6XX` | `6` plus any two digits |
| `NXXNXXXXXX` | NANP-style pattern |
| `610[45]XX` | Matches `6104..` and `6105..` |

### Explicit Regular Expressions

| Format | Example |
| --- | --- |
| Slash form | `/^610[0-9]{2}$/` |
| `re:` prefix | `re:610[45][0-9]{2}` |

The syntax follows **JavaScript RegExp**. If the expression is invalid, the search falls back to normal text matching.

---

## Provisioning URL

On the FreePBX server, a `/zerotouchsip` symlink is created for the `_zts_provisioning` directory.

| Path | Role |
| --- | --- |
| `https://<pbx>/zerotouchsip` | Root provisioning URL for DHCP, manual setup and generated configs |

This is the main URL to use when searching for:

- **FreePBX auto provisioning URL**
- **Yealink provisioning URL**
- **Fanvil provisioning server URL**
- **DHCP Option 66 URL for IP phones**

### Primary URL Structure

| Purpose | URL |
| --- | --- |
| Root provisioning | `https://your-server/zerotouchsip` |
| Boot (Yealink) | `https://your-server/zerotouchsip/boot.php?mac={MAC}` |
| Common CFG | `https://your-server/zerotouchsip/common.php?model={XX}` |
| MAC CFG | `https://your-server/zerotouchsip/config.php?mac={MAC}` |
| Fanvil model CFG | `https://your-server/zerotouchsip/F0V2UV200000.cfg` |

The **General Settings** page shows the ready-to-use provisioning URL for DHCP configuration or manual phone setup.

---

## SIP Plug and Play (PnP)

ZeroTouchSIP supports a SIP Plug and Play workflow similar to provisioning discovery approaches used in PBX deployments.

The phone sends a **SUBSCRIBE** request to multicast **224.0.1.75:5060**, and the PBX responds with a **NOTIFY** message containing the provisioning URL.

### Default PnP values

| Parameter | Default |
| --- | --- |
| Multicast address | `224.0.1.75` |
| Port | `5060` |
| Transport | `UDP` |
| Interval | `1 hour` |

Run listener manually:

```bash
php /var/www/html/admin/modules/zerotouchsip/bin/sip-pnp-listen.php --debug
```

Production note:

```bash
systemctl enable --now zerotouchsip-sip-pnp
```

Additional one-time HMAC-protected PnP links are also supported.

---

## DHCP and Auto Provisioning

### 1. Configure a network in ZeroTouchSIP

#### ZeroTouchSIP -> Networks -> Add/Edit

- **CIDR**: subnet of phones, for example `192.168.10.0/24`
- **Provisioning Protocol**: HTTP or HTTPS
- **Provisioning Username / Password**: Basic Auth credentials
- **SIP Server Address**: FQDN or IP the phone should use

### 2. DHCP Option 66

#### Without credentials in URL

```text
https://pbx.example.com/zerotouchsip
```

#### With Basic Auth in URL

```text
https://provuser:StrongPassword@pbx.example.com/zerotouchsip
```

#### DHCP examples

#### ISC DHCP / Kea

```conf
option tftp-server-name "https://provuser:SecretPass@pbx.example.com/zerotouchsip";
```

#### MikroTik

```text
https://provuser:SecretPass@pbx.example.com/zerotouchsip
```

#### dnsmasq

```conf
dhcp-option=66,https://provuser:SecretPass@pbx.example.com/zerotouchsip
```

#### Windows Server DHCP

```text
https://provuser:SecretPass@pbx.example.com/zerotouchsip
```

### 3. Manual setup on Yealink

1. `Menu -> Settings -> Advanced`
2. `Auto Provision -> Server URL` -> `https://pbx.example.com/zerotouchsip`
3. `Server Type` -> HTTP/HTTPS
4. Set username / password
5. Run **Auto Provision Now**

### 4. Auto-discovery into inventory

After the first successful configuration request, a phone can appear in the inventory list, after which the administrator can assign extensions, lines, and BLF keys.

---

## Notify and Remote Refresh

Use the following methods for remote configuration refresh:

1. **GUI**: the `Notify` button in the phones list
2. **CLI**:

```bash
asterisk -rx "pjsip send notify yealink-check-cfg endpoint 1001"
```

For Fanvil devices, a check-sync flow without reboot is used; an additional HTTP call may also be used when needed.

---

## Programmable Key Types

### Yealink Key Types

| Type | Purpose |
| --- | --- |
| `15` | Line |
| `16` | BLF |
| `13` | Speed Dial |
| `11` | DTMF |
| `14` | Intercom |
| `10` | Call Park |
| `27` | Group Pickup |

---

## Server File Locations

| Component | Path |
| --- | --- |
| Module code | `/var/www/html/admin/modules/zerotouchsip/` |
| Search JS asset | `/admin/assets/zerotouchsip/js/zts-list-search.js` |
| CLI tools | `modules/zerotouchsip/bin/` |
| Public provisioning URL | `/var/www/html/zerotouchsip` |
| Shared provisioning files | `/var/www/html/admin/modules/_zts_provisioning/` |
| Provisioning sources | `provisioning/` |
| Generated configs | `.../_zts_provisioning/configs/` |
| Provisioning log | `/var/log/httpd/zerotouchsip-provision.log` or distro-specific Apache path |

After a module update, synchronize `router.php`, `fanvil_common.php`, and `.htaccess` from `provisioning/` to `_zts_provisioning/` if required.

---

## Security Recommendations

- keep provisioning credentials separate for production and lab environments
- do not publish `.env`, configuration dumps, or raw provisioning traces
- use HTTPS for phones whenever possible
- change default provisioning usernames and passwords
- restrict network access by CIDR in `Networks`
- regularly update FreePBX, Asterisk, and the web server

---

## Troubleshooting

### Phone does not provision

1. Check the URL: `curl -sI -u user:pass https://pbx/zerotouchsip/`
2. Make sure the MAC address in the database is stored as 12 hex characters without separators
3. Verify CIDR matching and auth settings in `Networks`
4. Review the phone logs
5. Check Apache / httpd error logs

### 404 on Fanvil `F0V2UV200000.cfg`

1. Check `.htaccess` and `router.php` in `_zts_provisioning`
2. Make sure `AllowOverride All` is enabled for `/var/www/`
3. Verify:

```bash
curl -sI http://127.0.0.1/zerotouchsip/F0V2UV200000.cfg
```

### Phone does not register to SIP

1. Check **SIP Server Address**
2. Check the extension credentials
3. Check NAT / firewall settings
4. Run:

```bash
asterisk -rx "pjsip show endpoint 1001"
```

### BLF does not work

1. The phone must be registered
2. The key type must be `16`
3. The `Value` field must contain an extension

### Search does not work

1. Press `Ctrl+F5`
2. Verify that `zts-list-search.js` returns HTTP 200
3. If it returns 404, run:

```bash
fwconsole ma install zerotouchsip
```

---

## Testing and Validation

Quick validation steps after installation or upgrade:

```bash
# Check tables
mysql asterisk -e "SHOW TABLES LIKE 'zts_%';"

# Re-register module / asset symlinks
fwconsole ma installlocal zerotouchsip
fwconsole chown

# Check provisioning root
curl -sI https://pbx.example.com/zerotouchsip

# Check SIP PnP listener
php /var/www/html/admin/modules/zerotouchsip/bin/sip-pnp-listen.php --debug
```

---

## SEO Keywords and Discovery

For visibility in search engines and developer communities, this module is relevant to searches such as:

- **FreePBX provisioning module**
- **FreePBX Yealink auto provisioning**
- **FreePBX Fanvil provisioning**
- **SIP phone inventory FreePBX**
- **PBX DHCP Option 66**
- **IP phone provisioning PHP**
- **FreePBX endpoint management**

If you publish articles, release notes, or forum posts about ZeroTouchSIP, useful phrases include:

- "ZeroTouchSIP for FreePBX 17"
- "automatic provisioning for Yealink and Fanvil phones"
- "centralized SIP phone management in FreePBX"
- "DHCP Option 66 provisioning with FreePBX"
- "FreePBX module for hotel and office IP phones"

---

## Documentation

Additional project files:

- `module.xml` - FreePBX module metadata
- `install.php` - schema and initial install
- `upgrade.php` - module migration logic
- `SECURITY.md` - security process
- `CHANGELOG.md` - release history
- `docs/PRODUCTION_AUDIT.md` - production notes
- `docs/screenshots/` - published screenshots

---

## License

GNU General Public License v3.0 or later

---

## Credits

Based on the Yealink Phones module (Techie Networks Inc) for FreePBX 17.  
Inspired by the Polycom Phones module (Excalibur Partners).

---

## Project Status

- **Architecture**: modular PHP module for FreePBX 17
- **Provisioning**: working HTTP/HTTPS provisioning pipeline
- **Vendors**: focused on Yealink and Fanvil
- **Admin UI**: phones, networks, general settings
- **Search UX**: text, dial pattern, regexp support
- **Deployment use case**: suitable for office, hospitality, and managed PBX scenarios

**Ready for deployment, evaluation, and further development.**
