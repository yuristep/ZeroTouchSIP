# ZeroTouchSIP for FreePBX 17

> 用于 SIP 话机自动部署、设备清单管理以及 Yealink / Fanvil 终端集中管理的 FreePBX 模块。

**ZeroTouchSIP** (`rawname`: `zerotouchsip`) 是一个面向 FreePBX 17 的模块，提供 **SIP 话机自动部署**、**集中式终端管理** 和 **设备清单管理**。它主要面向 **Yealink** 和 **Fanvil** 话机，可帮助管理员在 FreePBX 界面中管理 provisioning URL、按网络划分的配置策略、SIP 参数、线路分配、BLF 按键以及远程配置刷新。

语言版本: **[English](../README.md)** | **[Russian](README_ru_RU.md)** | **[Chinese (Simplified)](README_zh_CN.md)**

Publisher: **YURI STEP. - Net Production -**  
License: **GPLv3+**

> 本文件是主 README 的简体中文版本。规范和主参考文档仍以英文版 `../README.md` 为准。

[![FreePBX 17](https://img.shields.io/badge/FreePBX-17-3E8EDE.svg)](https://www.freepbx.org/)
[![Category](https://img.shields.io/badge/Category-Connectivity-4CAF50.svg)](https://github.com/yuristep/ZeroTouchSIP)
[![License GPLv3+](https://img.shields.io/badge/License-GPLv3%2B-blue.svg)](https://www.gnu.org/licenses/gpl-3.0.txt)
[![Languages](https://img.shields.io/badge/Languages-en_US%20%7C%20ru_RU%20%7C%20zh_CN-orange.svg)](https://github.com/yuristep/ZeroTouchSIP)
[![Vendors](https://img.shields.io/badge/Vendors-Yealink%20%7C%20Fanvil-8E44AD.svg)](https://github.com/yuristep/ZeroTouchSIP)

## 目录

- [功能概览](#功能概览)
- [为什么选择 ZeroTouchSIP](#为什么选择-zerotouchsip)
- [架构](#架构)
- [项目结构](#项目结构)
- [界面截图](#界面截图)
- [支持的设备](#支持的设备)
- [快速开始](#快速开始)
- [管理界面](#管理界面)
- [数据库与升级](#数据库与升级)
- [电话列表搜索](#电话列表搜索)
- [Provisioning URL](#provisioning-url)
- [SIP Plug and Play (PnP)](#sip-plug-and-play-pnp)
- [DHCP 与自动部署](#dhcp-与自动部署)
- [Notify 与远程刷新](#notify-与远程刷新)
- [可编程按键类型](#可编程按键类型)
- [服务器文件位置](#服务器文件位置)
- [安全建议](#安全建议)
- [故障排查](#故障排查)
- [安装后验证](#安装后验证)
- [SEO 与搜索关键词](#seo-与搜索关键词)
- [相关文档](#相关文档)
- [许可证](#许可证)

---

## 功能概览

### 核心功能

- **FreePBX 话机清单管理**：MAC、型号、固件、最后 IP、部署状态、线路状态
- 面向 **Yealink** 与 **Fanvil** SIP 话机的 **零接触自动部署**
- 基于 **CIDR 子网** 的网络策略与部署配置
- **线路与 BLF 按键管理**
- 通过 **SIP NOTIFY / check-sync** 进行远程配置刷新
- 通过 `/zerotouchsip` 统一管理 **Provisioning URL**
- 在电话列表中支持 **文本、拨号模式、基础正则表达式** 搜索
- 根据分机号和型号自动生成电话名称
- 支持 **SIP Plug and Play (PnP)** 自动发现流程

### 技术特点

- 原生集成到 FreePBX 管理界面：`Connectivity -> ZeroTouchSIP`
- 使用 `install.php`、`upgrade.php`、`uninstall.php` 管理模块生命周期
- 通过 `_zts_provisioning` 集成 provisioning webroot
- 针对不同网络设置 SIP / NTP / 编解码器策略
- Yealink 与 Fanvil 设备模板
- 已加载列表的前端搜索，无需重新执行 SQL 过滤
- 采用 service / repository / validator 分层结构

---

## 为什么选择 ZeroTouchSIP

如果你正在寻找以下能力：

- **FreePBX 话机自动部署模块**
- **FreePBX 的 Yealink provisioning 方案**
- **Fanvil 自动部署方案**
- **集中式 SIP 话机管理**
- **基于 DHCP Option 66 的话机上线流程**

那么 ZeroTouchSIP 就是为这类场景设计的。

典型使用场景：

- 酒店、民宿、公寓项目中的房间电话批量部署
- 办公楼、分支机构的大规模 IP 电话部署
- 从手工配置迁移到集中 provisioning
- 需要通过 SIP NOTIFY 远程刷新配置

---

## 架构

### 模块层次

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

**核心原则**：用一个 FreePBX 模块统一承载 **话机清单**、**自动部署**、**网络配置策略**、**SIP PnP** 与 **运维操作**。

---

## 项目结构

```text
zerotouchsip/
├── assets/                         # FreePBX 前端 JS/CSS
│   └── js/
│       ├── zts-list-pagination.js
│       ├── zts-list-search.js
│       ├── zts-linekeys-editor.js
│       └── zts-lines-editor.js
├── bin/                            # CLI 工具
│   └── sip-pnp-listen.php
├── docs/                           # 文档与截图
│   └── screenshots/
├── i18n/                           # gettext 本地化
├── includes/                       # PHP services / repositories / validators
│   └── Zts/
├── provisioning/                   # 部署入口与模板
│   ├── boot.php
│   ├── config.php
│   ├── common.php
│   ├── router.php
│   ├── fanvil/
│   └── yealink/
├── views/                          # FreePBX 管理界面模板
│   ├── zts_phones.php
│   ├── zts_phones_edit.php
│   ├── zts_networks.php
│   ├── zts_networks_edit.php
│   └── zts_general.php
├── install.php
├── upgrade.php
├── uninstall.php
├── module.xml
├── page.zerotouchsip.php
└── README.md
```

---

## 界面截图

截图来自真实的 FreePBX 17 环境，所有敏感信息均已做脱敏处理。

### Phones - list

![Phones list - FreePBX SIP phone inventory](screenshots/phones_list.png)

### Phones - edit

![Phones edit - provisioning, lines and programmable keys](screenshots/phones_edit.png)

### Networks - list

![Networks list - subnet-based provisioning profiles](screenshots/networks_list.png)

### Networks - edit

![Networks edit - SIP server and provisioning settings](screenshots/networks_edit.png)

### General Settings

![General settings - provisioning URL and diagnostics](screenshots/general_edit.png)

---

## 支持的设备

### Yealink 按键类型

- **T3x**: T31, T31G, T31P, T33G, T33P, T34W
- **T4x**: T42G/S/U, T46G/S/U, T48G/S/U
- **T5x**: T52S/W, T53/W, T54S/W, T56A, T57W, T58A/W

### Fanvil

- **H2U-V2**
- **H5**
- **H6W**
- 以及通过 `fanvil` profile heuristic 兼容的型号

---

## 快速开始

### 1. 安装模块

#### 从 GitHub 安装

```bash
cd /var/www/html/admin/modules
git clone https://github.com/yuristep/ZeroTouchSIP.git zerotouchsip
fwconsole ma install zerotouchsip
fwconsole chown
```

也可以下载发布包，例如 `zerotouchsip-v17.0.0.tgz`，并解压到 `admin/modules/zerotouchsip/`。

#### 开发环境复制目录安装

```bash
cp -a zerotouchsip /var/www/html/admin/modules/zerotouchsip
fwconsole ma install zerotouchsip
fwconsole chown
```

### 2. 在 FreePBX 中打开模块

#### Connectivity -> ZeroTouchSIP

直接访问 URL：

```text
/admin/config.php?display=zerotouchsip&zerotouchsip_form=phones_list
```

### 3. 配置自动部署

1. 在 **Networks** 中添加一个或多个网络
2. 设置 **Provisioning Protocol**
3. 设置 **Provisioning Username / Password**
4. 设置 **SIP Server Address**
5. 将电话或 DHCP Option 66 指向 `/zerotouchsip`

### 4. 添加或自动发现话机

- 手动创建电话记录，或
- 让设备先访问 provisioning URL，再在 **Phones** 中分配线路

---

## 管理界面

| 标签 | 作用 |
| --- | --- |
| `Phones` | 电话列表、部署状态、notify 操作、线路分配、命名 |
| `Networks` | 基于 CIDR 的网络、认证、SIP/NTP/网络策略 |
| `General` | Provisioning URL、SIP PnP、密码、诊断与默认值 |

入口参数：`display=zerotouchsip`，表单参数为 `zerotouchsip_form=<form_name>`。

---

## 数据库与升级

ZeroTouchSIP 使用 **`zts_*`** 数据表。初始结构由 `install.php` 创建，版本升级由 `upgrade.php` 负责。

```bash
fwconsole ma upgrade zerotouchsip
# 或在复制文件之后：
fwconsole ma installlocal zerotouchsip
```

检查表是否存在：

```bash
mysql asterisk -e "SHOW TABLES LIKE 'zts_%';"
```

Provisioning webroot:

- shared module dir: `admin/modules/_zts_provisioning/`
- public URL: `/zerotouchsip`

---

## 电话列表搜索

电话列表上方的 **Search** 字段支持 **无需刷新页面** 的实时筛选，可按分机号、MAC、型号、拨号模式等进行查找。

### 普通文本搜索

文本会以不区分大小写的子串方式匹配到电话名称、MAC、vendor、型号、固件、线路、最后 IP、PJSIP 状态等字段。

| 查询 | 说明 |
| --- | --- |
| `fanvil` | 查找 Fanvil 电话 |
| `192.168` | 匹配包含该 IP 段的行 |
| `1001` | 匹配包含 `1001` 的任意行 |

### FreePBX 拨号模式搜索

当输入看起来像拨号模式时，搜索会应用到分配给电话线路的 **extension** 上。

| 模式 | 含义 |
| --- | --- |
| `X` | 任意数字 `0-9` |
| `Z` | 任意数字 `1-9` |
| `N` | 任意数字 `2-9` |
| `.` | 任意字符序列 (`.*`) |
| `[123]` | 集合中的一个字符 |
| `[0-9]` | 范围内的一个数字 |

前导 `_` 会被忽略，例如 `_6XX` 等价于 `6XX`。

### 显式正则表达式

| 格式 | 示例 |
| --- | --- |
| slash form | `/^610[0-9]{2}$/` |
| `re:` 前缀 | `re:610[45][0-9]{2}` |

语法遵循 **JavaScript RegExp**。如果表达式无效，则会回退到普通文本匹配。

---

## Provisioning URL

在 FreePBX 服务器上，`/zerotouchsip` 会指向 `_zts_provisioning` 目录。

| 路径 | 作用 |
| --- | --- |
| `https://<pbx>/zerotouchsip` | DHCP、手工配置以及生成配置文件的根 URL |

### 主要 URL 结构

| 用途 | URL |
| --- | --- |
| Root provisioning | `https://your-server/zerotouchsip` |
| Boot (Yealink) | `https://your-server/zerotouchsip/boot.php?mac={MAC}` |
| Common CFG | `https://your-server/zerotouchsip/common.php?model={XX}` |
| MAC CFG | `https://your-server/zerotouchsip/config.php?mac={MAC}` |
| Fanvil model CFG | `https://your-server/zerotouchsip/F0V2UV200000.cfg` |

在 **General Settings** 中会显示可直接复制的 provisioning URL。

---

## SIP Plug and Play (PnP)

ZeroTouchSIP 支持 SIP Plug and Play 自动发现流程。

话机会向 multicast **224.0.1.75:5060** 发送 **SUBSCRIBE**，PBX 返回带有 provisioning URL 的 **NOTIFY**。

| 参数 | 默认值 |
| --- | --- |
| Multicast address | `224.0.1.75` |
| Port | `5060` |
| Transport | `UDP` |
| Interval | `1 hour` |

手工启动监听器：

```bash
php /var/www/html/admin/modules/zerotouchsip/bin/sip-pnp-listen.php --debug
```

生产环境中：

```bash
systemctl enable --now zerotouchsip-sip-pnp
```

同时支持带 HMAC 的一次性 PnP 安全链接。

---

## DHCP 与自动部署

### 1. 配置网络

#### ZeroTouchSIP -> Networks -> Add/Edit

- **CIDR**：电话所在子网，例如 `192.168.10.0/24`
- **Provisioning Protocol**：HTTP 或 HTTPS
- **Provisioning Username / Password**：Basic Auth 凭据
- **SIP Server Address**：电话应连接的 FQDN 或 IP

### 2. DHCP Option 66

#### URL 中不包含账号密码

```text
https://pbx.example.com/zerotouchsip
```

#### URL 中包含 Basic Auth

```text
https://provuser:StrongPassword@pbx.example.com/zerotouchsip
```

#### DHCP 配置示例

##### ISC DHCP / Kea

```conf
option tftp-server-name "https://provuser:SecretPass@pbx.example.com/zerotouchsip";
```

##### MikroTik

```text
https://provuser:SecretPass@pbx.example.com/zerotouchsip
```

##### dnsmasq

```conf
dhcp-option=66,https://provuser:SecretPass@pbx.example.com/zerotouchsip
```

##### Windows Server DHCP

```text
https://provuser:SecretPass@pbx.example.com/zerotouchsip
```

### 3. 在 Yealink 上手工配置

1. `Menu -> Settings -> Advanced`
2. `Auto Provision -> Server URL` -> `https://pbx.example.com/zerotouchsip`
3. `Server Type` -> HTTP/HTTPS
4. 填写用户名与密码
5. 执行 **Auto Provision Now**

### 4. 自动发现到清单中

当设备首次成功获取配置后，电话可自动出现在清单中，然后由管理员为其分配分机、线路与 BLF。

---

## Notify 与远程刷新

远程刷新配置的方法：

1. **GUI**：电话列表中的 `Notify` 按钮
2. **CLI**：

```bash
asterisk -rx "pjsip send notify yealink-check-cfg endpoint 1001"
```

对于 Fanvil，通常使用不重启的 check-sync；必要时还可配合额外 HTTP 调用。

---

## 可编程按键类型

### Yealink

| Type | 作用 |
| --- | --- |
| `15` | Line |
| `16` | BLF |
| `13` | Speed Dial |
| `11` | DTMF |
| `14` | Intercom |
| `10` | Call Park |
| `27` | Group Pickup |

---

## 服务器文件位置

| 组件 | 路径 |
| --- | --- |
| 模块代码 | `/var/www/html/admin/modules/zerotouchsip/` |
| Search JS asset | `/admin/assets/zerotouchsip/js/zts-list-search.js` |
| CLI tools | `modules/zerotouchsip/bin/` |
| Public provisioning URL | `/var/www/html/zerotouchsip` |
| Shared provisioning files | `/var/www/html/admin/modules/_zts_provisioning/` |
| Provisioning sources | `provisioning/` |
| Generated configs | `.../_zts_provisioning/configs/` |
| Provisioning log | `/var/log/httpd/zerotouchsip-provision.log` 或系统对应的 Apache 日志路径 |

如模块升级后需要，可将 `router.php`、`fanvil_common.php` 与 `.htaccess` 从 `provisioning/` 同步到 `_zts_provisioning/`。

---

## 安全建议

- 为生产和实验环境分别使用不同的 provisioning 凭据
- 不要公开 `.env`、配置导出和原始 provisioning traces
- 尽量为设备使用 HTTPS
- 修改默认的 provisioning 用户名和密码
- 在 `Networks` 中通过 CIDR 限制访问范围
- 定期更新 FreePBX、Asterisk 与 Web 服务器

---

## 故障排查

### 电话无法自动部署

1. 检查 URL：`curl -sI -u user:pass https://pbx/zerotouchsip/`
2. 确认数据库中的 MAC 为 12 位十六进制且不含分隔符
3. 检查 `Networks` 中的 CIDR 与认证设置
4. 查看电话本机日志
5. 检查 Apache / httpd 错误日志

### Fanvil `F0V2UV200000.cfg` 返回 404

1. 检查 `_zts_provisioning` 中的 `.htaccess` 和 `router.php`
2. 确认 `/var/www/` 开启 `AllowOverride All`
3. 执行：

```bash
curl -sI http://127.0.0.1/zerotouchsip/F0V2UV200000.cfg
```

### 电话无法注册到 SIP

1. 检查 **SIP Server Address**
2. 检查分机账号密码
3. 检查 NAT / firewall
4. 执行：

```bash
asterisk -rx "pjsip show endpoint 1001"
```

### BLF 不工作

1. 电话必须已经注册
2. 按键类型应为 `16`
3. `Value` 字段中必须是分机号

### 搜索不工作

1. 执行 `Ctrl+F5`
2. 检查 `zts-list-search.js` 是否返回 HTTP 200
3. 如果是 404，执行：

```bash
fwconsole ma install zerotouchsip
```

---

## 安装后验证

```bash
# 检查表
mysql asterisk -e "SHOW TABLES LIKE 'zts_%';"

# 重新注册模块 / asset symlink
fwconsole ma installlocal zerotouchsip
fwconsole chown

# 检查 provisioning 根路径
curl -sI https://pbx.example.com/zerotouchsip

# 检查 SIP PnP listener
php /var/www/html/admin/modules/zerotouchsip/bin/sip-pnp-listen.php --debug
```

---

## SEO 与搜索关键词

这个模块适用于以下搜索场景：

- **FreePBX provisioning module**
- **FreePBX Yealink auto provisioning**
- **FreePBX Fanvil provisioning**
- **SIP phone inventory FreePBX**
- **PBX DHCP Option 66**
- **IP phone provisioning PHP**
- **FreePBX endpoint management**

发布文章、论坛帖或发行说明时，可使用以下表述：

- "ZeroTouchSIP for FreePBX 17"
- "automatic provisioning for Yealink and Fanvil phones"
- "centralized SIP phone management in FreePBX"
- "DHCP Option 66 provisioning with FreePBX"
- "FreePBX module for hotel and office IP phones"

---

## 相关文档

- `module.xml` - FreePBX 模块元数据
- `install.php` - 初始安装与建表逻辑
- `upgrade.php` - 模块升级迁移逻辑
- `SECURITY.md` - 安全流程
- `CHANGELOG.md` - 发布历史
- `docs/PRODUCTION_AUDIT.md` - 生产环境审计说明
- `docs/screenshots/` - 公布的界面截图

---

## 许可证

GNU General Public License v3.0 or later

---

## 致谢

基于 FreePBX 17 的 Yealink Phones 模块（Techie Networks Inc）提供的思路。  
同时受到 Polycom Phones 模块（Excalibur Partners）的启发。

---

## 项目状态

- **Architecture**: 面向 FreePBX 17 的模块化 PHP 架构
- **Provisioning**: 可用的 HTTP/HTTPS 自动部署流程
- **Vendors**: 主要面向 Yealink 与 Fanvil
- **Admin UI**: 包含 phones、networks、general settings
- **Search UX**: 支持文本、拨号模式和正则搜索
- **Deployment use case**: 适用于办公、酒店与托管 PBX 场景

**可以用于部署评估、测试和持续开发。**
