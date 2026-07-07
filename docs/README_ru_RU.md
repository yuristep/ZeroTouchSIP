# ZeroTouchSIP для FreePBX 17

> Модуль FreePBX для автопровижининга SIP-телефонов, инвентаря устройств и централизованного управления телефонами Yealink и Fanvil.

**ZeroTouchSIP** (`rawname`: `zerotouchsip`) - это модуль FreePBX 17 для **автоматического провижининга SIP-телефонов**, **централизованного управления устройствами** и **учёта телефонного парка** на базе **Yealink** и **Fanvil**. Модуль помогает администратору управлять provisioning URL, сетевыми профилями, SIP-настройками, назначением линий, BLF-клавишами и удалённым обновлением конфигурации через интерфейс FreePBX.

Языковые версии: **[English](../README.md)** | **[Russian](README_ru_RU.md)** | **[Chinese (Simplified)](README_zh_CN.md)**

Издатель: **YURI STEP. - Net Production -**  
Лицензия: **GPLv3+**

> Этот файл является русскоязычной версией основной документации. Базовой и канонической версией README считается английская: `../README.md`.

[![FreePBX 17](https://img.shields.io/badge/FreePBX-17-3E8EDE.svg)](https://www.freepbx.org/)
[![Категория](https://img.shields.io/badge/Категория-Connectivity-4CAF50.svg)](https://github.com/yuristep/ZeroTouchSIP)
[![License GPLv3+](https://img.shields.io/badge/License-GPLv3%2B-blue.svg)](https://www.gnu.org/licenses/gpl-3.0.txt)
[![Языки](https://img.shields.io/badge/Языки-en_US%20%7C%20ru_RU%20%7C%20zh_CN-orange.svg)](https://github.com/yuristep/ZeroTouchSIP)
[![Vendors](https://img.shields.io/badge/Vendors-Yealink%20%7C%20Fanvil-8E44AD.svg)](https://github.com/yuristep/ZeroTouchSIP)

## Оглавление

- [Возможности](#возможности)
- [Зачем нужен ZeroTouchSIP](#зачем-нужен-zerotouchsip)
- [Архитектура](#архитектура)
- [Структура проекта](#структура-проекта)
- [Скриншоты](#скриншоты)
- [Поддерживаемые телефоны](#поддерживаемые-телефоны)
- [Быстрый старт](#быстрый-старт)
- [Интерфейс администратора](#интерфейс-администратора)
- [База данных и обновление](#база-данных-и-обновление)
- [Поиск в списке телефонов](#поиск-в-списке-телефонов)
- [Provisioning URL](#provisioning-url)
- [SIP Plug and Play (PnP)](#sip-plug-and-play-pnp)
- [DHCP и автопровижининг](#dhcp-и-автопровижининг)
- [Notify и удалённое обновление конфигурации](#notify-и-удалённое-обновление-конфигурации)
- [Типы программируемых клавиш](#типы-программируемых-клавиш)
- [Расположение файлов на сервере](#расположение-файлов-на-сервере)
- [Рекомендации по безопасности](#рекомендации-по-безопасности)
- [Устранение неполадок](#устранение-неполадок)
- [Проверка после установки](#проверка-после-установки)
- [SEO и поисковая видимость](#seo-и-поисковая-видимость)
- [Документация](#документация)
- [Лицензия](#лицензия)

---

## Возможности

### Основные функции

- **Инвентарь телефонов** в FreePBX: MAC, модель, прошивка, Last IP, состояние провижининга и линий
- **Zero-touch provisioning** для SIP-телефонов **Yealink** и **Fanvil** по HTTP/HTTPS
- **Сетевые provisioning-профили** по **CIDR-подсетям**
- **Управление линиями и BLF** с программируемыми клавишами
- **SIP NOTIFY / check-sync** для удалённого обновления конфигурации
- **Управление provisioning URL** через `/zerotouchsip`
- **Поиск по списку телефонов**: текст, dial patterns и базовые regexp
- **Автоматическое имя телефона** на основе extension и модели
- **SIP Plug and Play (PnP)** для поддерживаемых сценариев автообнаружения

### Технические особенности

- нативная админка FreePBX: `Connectivity -> ZeroTouchSIP`
- стандартный жизненный цикл модуля через `install.php`, `upgrade.php`, `uninstall.php`
- интеграция provisioning webroot через `_zts_provisioning`
- настройки SIP / NTP / кодеков по разным сетям
- шаблоны провижининга для Fanvil и Yealink
- клиентский поиск без SQL-фильтрации загруженных строк
- сервисная архитектура с repositories, validators и service classes

---

## Зачем нужен ZeroTouchSIP

Если вам нужен **модуль провижининга телефонов для FreePBX**, решение для **Yealink provisioning**, **Fanvil auto provisioning** или централизованное управление SIP-телефонами в FreePBX 17, ZeroTouchSIP закрывает именно этот сценарий.

Типовые кейсы:

- гостиницы и апарт-отели с большим числом комнатных телефонов
- офисы и филиалы с массовым вводом IP-телефонов
- переход с ручной настройки аппаратов на централизованный provisioning
- использование DHCP Option 66
- удалённое обновление конфигурации через SIP NOTIFY

---

## Архитектура

### Слои модуля

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

**Ключевой принцип**: один модуль FreePBX объединяет **инвентарь телефонов**, **провижининг**, **сетевые профили**, **SIP PnP** и **операции обслуживания**.

---

## Структура проекта

```text
zerotouchsip/
├── assets/                         # JS/CSS для интерфейса FreePBX
│   └── js/
│       ├── zts-list-pagination.js
│       ├── zts-list-search.js
│       ├── zts-linekeys-editor.js
│       └── zts-lines-editor.js
├── bin/                            # CLI-утилиты
│   └── sip-pnp-listen.php
├── docs/                           # Документация и скриншоты
│   └── screenshots/
├── i18n/                           # Локализации gettext
├── includes/                       # PHP services / repositories / validators
│   └── Zts/
├── provisioning/                   # Точки входа и шаблоны provisioning
│   ├── boot.php
│   ├── config.php
│   ├── common.php
│   ├── router.php
│   ├── fanvil/
│   └── yealink/
├── views/                          # Шаблоны админки FreePBX
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

## Скриншоты

Скриншоты сделаны на реальной системе FreePBX 17. Все чувствительные данные в опубликованных изображениях замаскированы.

### Phones - список

![Phones list - inventory of SIP phones in FreePBX](screenshots/phones_list.png)

### Phones - редактирование

![Phones edit - provisioning, lines and programmable keys](screenshots/phones_edit.png)

### Networks - список

![Networks list - subnet-based provisioning profiles](screenshots/networks_list.png)

### Networks - редактирование

![Networks edit - SIP server and provisioning settings](screenshots/networks_edit.png)

### General Settings

![General settings - provisioning URL and diagnostics](screenshots/general_edit.png)

---

## Поддерживаемые телефоны

### Типы клавиш Yealink

- **T3x**: T31, T31G, T31P, T33G, T33P, T34W
- **T4x**: T42G/S/U, T46G/S/U, T48G/S/U
- **T5x**: T52S/W, T53/W, T54S/W, T56A, T57W, T58A/W

### Fanvil

- **H2U-V2**
- **H5**
- **H6W**
- совместимые устройства через профиль `fanvil`

---

## Быстрый старт

### 1. Установка модуля

#### Из GitHub

```bash
cd /var/www/html/admin/modules
git clone https://github.com/yuristep/ZeroTouchSIP.git zerotouchsip
fwconsole ma install zerotouchsip
fwconsole chown
```

Либо скачайте tarball релиза, например `zerotouchsip-v17.0.0.tgz`, и распакуйте его в `admin/modules/zerotouchsip/`.

#### Разработка / копирование каталога

```bash
cp -a zerotouchsip /var/www/html/admin/modules/zerotouchsip
fwconsole ma install zerotouchsip
fwconsole chown
```

### 2. Открытие модуля в FreePBX

#### Connectivity -> ZeroTouchSIP

Прямой URL:

```text
/admin/config.php?display=zerotouchsip&zerotouchsip_form=phones_list
```

### 3. Настройка провижининга

1. Добавьте одну или несколько сетей в **Networks**
2. Укажите **Provisioning Protocol**
3. Укажите **Provisioning Username / Password**
4. Укажите **SIP Server Address**
5. Направьте телефоны или DHCP Option 66 на `/zerotouchsip`

### 4. Добавление или автообнаружение телефонов

- создайте запись телефона вручную, или
- разрешите устройствам обращаться к provisioning URL и затем назначьте линии в **Phones**

---

## Интерфейс администратора

| Вкладка | Назначение |
| --- | --- |
| `Phones` | Список телефонов, состояние provisioning, notify, назначение линий и имён |
| `Networks` | CIDR-сети, provisioning auth, политики SIP/NTP/сети |
| `General` | Provisioning URL, SIP PnP, пароли, диагностика, общие настройки |

Точка входа: `display=zerotouchsip` и параметр `zerotouchsip_form=<form_name>`.

---

## База данных и обновление

ZeroTouchSIP использует таблицы **`zts_*`**. Схема создаётся через `install.php`, а обновления применяются через `upgrade.php`.

```bash
fwconsole ma upgrade zerotouchsip
# или после копирования файлов:
fwconsole ma installlocal zerotouchsip
```

Проверка таблиц:

```bash
mysql asterisk -e "SHOW TABLES LIKE 'zts_%';"
```

Provisioning webroot:

- shared module dir: `admin/modules/_zts_provisioning/`
- public URL: `/zerotouchsip`

---

## Поиск в списке телефонов

Поле **Search** над таблицей телефонов фильтрует строки **без перезагрузки страницы**. Поиск удобен для инвентаря SIP-телефонов в FreePBX, когда нужно искать по extension, MAC, модели или dial pattern.

### Обычный текст

Текст ищется как подстрока без учёта регистра по имени телефона, MAC, vendor, модели, прошивке, линиям, Last IP, PJSIP-статусу и другим полям строки.

| Запрос | Что находит |
| --- | --- |
| `fanvil` | телефоны Fanvil |
| `192.168` | IP-адреса и совпадения по строке |
| `1001` | любые строки, содержащие `1001` |

### Dial Pattern для FreePBX

Если запрос похож на dial pattern, поиск применяется к **extension**, назначенным линиям телефона.

| Шаблон | Значение |
| --- | --- |
| `X` | любая цифра `0-9` |
| `Z` | любая цифра `1-9` |
| `N` | любая цифра `2-9` |
| `.` | любая последовательность (`.*`) |
| `[123]` | один символ из набора |
| `[0-9]` | одна цифра из диапазона |

Ведущий `_` игнорируется: `_6XX` эквивалентен `6XX`.

### Явные регулярные выражения

| Формат | Пример |
| --- | --- |
| slash form | `/^610[0-9]{2}$/` |
| префикс `re:` | `re:610[45][0-9]{2}` |

Синтаксис соответствует **JavaScript RegExp**. Если выражение некорректно, поиск откатывается к обычному текстовому сравнению.

---

## Provisioning URL

На сервере FreePBX создаётся симлинк `/zerotouchsip` на каталог `_zts_provisioning`.

| Путь | Назначение |
| --- | --- |
| `https://<pbx>/zerotouchsip` | корневой provisioning URL для DHCP, ручной настройки и генерируемых конфигов |

### Основная структура URL

| Назначение | URL |
| --- | --- |
| Root provisioning | `https://your-server/zerotouchsip` |
| Boot (Yealink) | `https://your-server/zerotouchsip/boot.php?mac={MAC}` |
| Common CFG | `https://your-server/zerotouchsip/common.php?model={XX}` |
| MAC CFG | `https://your-server/zerotouchsip/config.php?mac={MAC}` |
| Fanvil model CFG | `https://your-server/zerotouchsip/F0V2UV200000.cfg` |

В **General Settings** отображается готовый provisioning URL для DHCP и ручной настройки телефонов.

---

## SIP Plug and Play (PnP)

ZeroTouchSIP поддерживает SIP Plug and Play сценарий автообнаружения provisioning URL.

Телефон отправляет **SUBSCRIBE** на multicast **224.0.1.75:5060**, а PBX отвечает **NOTIFY** с URL профиля.

| Параметр | Значение по умолчанию |
| --- | --- |
| Multicast address | `224.0.1.75` |
| Port | `5060` |
| Transport | `UDP` |
| Interval | `1 hour` |

Ручной запуск listener:

```bash
php /var/www/html/admin/modules/zerotouchsip/bin/sip-pnp-listen.php --debug
```

Для production:

```bash
systemctl enable --now zerotouchsip-sip-pnp
```

Также поддерживаются одноразовые PnP-ссылки с HMAC-защитой.

---

## DHCP и автопровижининг

### 1. Настройка сети

#### ZeroTouchSIP -> Networks -> Add/Edit

- **CIDR**: подсеть телефонов, например `192.168.10.0/24`
- **Provisioning Protocol**: HTTP или HTTPS
- **Provisioning Username / Password**: учётные данные Basic Auth
- **SIP Server Address**: FQDN или IP, который должны использовать телефоны

### 2. DHCP Option 66

#### Без логина в URL

```text
https://pbx.example.com/zerotouchsip
```

#### С Basic Auth в URL

```text
https://provuser:StrongPassword@pbx.example.com/zerotouchsip
```

#### Примеры DHCP

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

### 3. Ручная настройка Yealink

1. `Menu -> Settings -> Advanced`
2. `Auto Provision -> Server URL` -> `https://pbx.example.com/zerotouchsip`
3. `Server Type` -> HTTP/HTTPS
4. Укажите username / password
5. Запустите **Auto Provision Now**

### 4. Автообнаружение в инвентаре

После первого успешного запроса конфигурации телефон может появиться в списке. После этого администратор назначает extension, линии и BLF.

---

## Notify и удалённое обновление конфигурации

Для удалённого обновления конфигурации используйте:

1. **GUI**: кнопка `Notify` в списке телефонов
2. **CLI**:

```bash
asterisk -rx "pjsip send notify yealink-check-cfg endpoint 1001"
```

Для Fanvil используется check-sync без reboot; при необходимости возможен дополнительный HTTP-вызов.

---

## Типы программируемых клавиш

### Yealink

| Type | Назначение |
| --- | --- |
| `15` | Line |
| `16` | BLF |
| `13` | Speed Dial |
| `11` | DTMF |
| `14` | Intercom |
| `10` | Call Park |
| `27` | Group Pickup |

---

## Расположение файлов на сервере

| Компонент | Путь |
| --- | --- |
| Код модуля | `/var/www/html/admin/modules/zerotouchsip/` |
| Search JS asset | `/admin/assets/zerotouchsip/js/zts-list-search.js` |
| CLI tools | `modules/zerotouchsip/bin/` |
| Public provisioning URL | `/var/www/html/zerotouchsip` |
| Shared provisioning files | `/var/www/html/admin/modules/_zts_provisioning/` |
| Provisioning sources | `provisioning/` |
| Generated configs | `.../_zts_provisioning/configs/` |
| Provisioning log | `/var/log/httpd/zerotouchsip-provision.log` или distro-specific Apache path |

После обновления модуля при необходимости синхронизируйте `router.php`, `fanvil_common.php` и `.htaccess` из `provisioning/` в `_zts_provisioning/`.

---

## Рекомендации по безопасности

- храните provisioning credentials отдельно для production и lab
- не публикуйте `.env`, дампы конфигов и raw provisioning traces
- используйте HTTPS для телефонов, где это возможно
- смените дефолтные provisioning usernames и passwords
- ограничивайте доступ по сети через CIDR в `Networks`
- регулярно обновляйте FreePBX, Asterisk и веб-сервер

---

## Устранение неполадок

### Телефон не провижинится

1. Проверьте URL: `curl -sI -u user:pass https://pbx/zerotouchsip/`
2. Убедитесь, что MAC в БД хранится как 12 hex-символов без разделителей
3. Проверьте совпадение CIDR и auth в `Networks`
4. Посмотрите логи телефона
5. Проверьте Apache / httpd error logs

### 404 на Fanvil `F0V2UV200000.cfg`

1. Проверьте `.htaccess` и `router.php` в `_zts_provisioning`
2. Убедитесь, что для `/var/www/` разрешён `AllowOverride All`
3. Выполните:

```bash
curl -sI http://127.0.0.1/zerotouchsip/F0V2UV200000.cfg
```

### Телефон не регистрируется на SIP

1. Проверьте **SIP Server Address**
2. Проверьте учётные данные extension
3. Проверьте NAT / firewall
4. Выполните:

```bash
asterisk -rx "pjsip show endpoint 1001"
```

### BLF не работает

1. Телефон должен быть зарегистрирован
2. Тип клавиши должен быть `16`
3. В `Value` должен быть extension

### Поиск не работает

1. Выполните `Ctrl+F5`
2. Проверьте, что `zts-list-search.js` отдаётся с HTTP 200
3. Если 404, выполните:

```bash
fwconsole ma install zerotouchsip
```

---

## Проверка после установки

```bash
# Проверка таблиц
mysql asterisk -e "SHOW TABLES LIKE 'zts_%';"

# Повторная регистрация модуля / asset symlinks
fwconsole ma installlocal zerotouchsip
fwconsole chown

# Проверка provisioning root
curl -sI https://pbx.example.com/zerotouchsip

# Проверка SIP PnP listener
php /var/www/html/admin/modules/zerotouchsip/bin/sip-pnp-listen.php --debug
```

---

## SEO и поисковая видимость

Этот модуль релевантен для запросов:

- **FreePBX provisioning module**
- **FreePBX Yealink auto provisioning**
- **FreePBX Fanvil provisioning**
- **SIP phone inventory FreePBX**
- **PBX DHCP Option 66**
- **IP phone provisioning PHP**
- **FreePBX endpoint management**

Полезные формулировки для статей, заметок о релизе и форумов:

- "ZeroTouchSIP for FreePBX 17"
- "automatic provisioning for Yealink and Fanvil phones"
- "centralized SIP phone management in FreePBX"
- "DHCP Option 66 provisioning with FreePBX"
- "FreePBX module for hotel and office IP phones"

---

## Документация

Дополнительные файлы проекта:

- `module.xml` - метаданные модуля FreePBX
- `install.php` - начальная установка и схема
- `upgrade.php` - миграции модуля
- `SECURITY.md` - security process
- `CHANGELOG.md` - история релизов
- `docs/PRODUCTION_AUDIT.md` - production notes
- `docs/screenshots/` - опубликованные скриншоты

---

## Лицензия

GNU General Public License v3.0 or later

---

## Благодарности

Основано на модуле Yealink Phones (Techie Networks Inc) для FreePBX 17.  
Вдохновлено модулем Polycom Phones (Excalibur Partners).

---

## Статус проекта

- **Architecture**: модульная PHP-архитектура для FreePBX 17
- **Provisioning**: рабочий HTTP/HTTPS provisioning pipeline
- **Vendors**: фокус на Yealink и Fanvil
- **Admin UI**: вкладки phones, networks, general settings
- **Search UX**: поддержка текста, dial pattern и regexp
- **Deployment use case**: подходит для офисных, гостиничных и managed PBX сценариев

**Готов к внедрению, оценке и дальнейшему развитию.**
