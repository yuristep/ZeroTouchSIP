#!/usr/bin/env python3
"""Replace device-specific values in Fanvil baseline templates with {PLACEHOLDER} tokens."""

from __future__ import annotations

import re
from pathlib import Path

BASE = Path(__file__).resolve().parent

SIP_FIELDS = [
    ("Phone Number", "PHONE_NUMBER"),
    ("Display Name", "DISPLAY_NAME"),
    ("Sip Name", "SIP_NAME"),
    ("Register Addr", "REGISTER_ADDR"),
    ("Register Port", "REGISTER_PORT"),
    ("Register User", "REGISTER_USER"),
    ("Register Pswd", "REGISTER_PSWD"),
    ("Register TTL", "REGISTER_TTL"),
    ("Need Reg On", "NEED_REG_ON"),
    ("Backup Addr", "BACKUP_ADDR"),
    ("Backup Port", "BACKUP_PORT"),
    ("Backup Transport", "BACKUP_TRANSPORT"),
    ("Backup TTL", "BACKUP_TTL"),
    ("Backup Need Reg On", "BACKUP_NEED_REG_ON"),
    ("Enable Reg", "ENABLE_REG"),
    ("VoiceCodecMap", "VOICE_CODEC_MAP"),
    ("Transport", "TRANSPORT"),
    ("Local Domain", "LOCAL_DOMAIN"),
    ("Hotline Num", "HOTLINE_NUM"),
    ("Enable Hotline", "HOTLINE_ENABLE"),
    ("WarmLine Time", "WARMLINE_TIME"),
    ("Proxy User", "PROXY_USER"),
    ("Proxy Pswd", "PROXY_PSWD"),
]

WIFI_ITEM_FIELDS = [
    ("WIFI Name", "NAME"),
    ("WIFI SSID", "SSID"),
    ("Secure Mode", "SECURE_MODE"),
    ("WIFI Encryption", "ENCRYPTION"),
    ("WIFI User Name", "USERNAME"),
    ("WIFI Password", "PASSWORD"),
]

GLOBAL_REPLACEMENTS = [
    (re.compile(r"^(WIFI Enable\s+:).*$"), r"\1{WIFI_ENABLE}"),
    (re.compile(r"^(WIFI Country Code\s+:).*$"), r"\1{WIFI_COUNTRY_CODE}"),
    (re.compile(r"^(DHCP Hostname\s+:).*$"), r"\1{DHCP_HOSTNAME}"),
    (re.compile(r"^(LCD Title\s+:).*$"), r"\1{DEVICE_NAME}"),
    (re.compile(r"^(Backlight Off Time\s+:).*$"), r"\1{BACKLIGHT_TIME}"),
    (re.compile(r"^(Default Language\s+:).*$"), r"\1{FANVIL_LANG}"),
    (re.compile(r"^(Language\s+:).*$"), r"\1{FANVIL_LANG}"),
    (re.compile(r"^(Audio Codec Sets\s+:).*$"), r"\1{AUDIO_CODEC_SETS}"),
    (re.compile(r"^(SNTP Server\s+:).*$"), r"\1{NTP_SERVER1}"),
    (re.compile(r"^(Second SNTP Server\s+:).*$"), r"\1{NTP_SERVER2}"),
    (re.compile(r"^(Time Zone\s+:).*$"), r"\1{TIME_ZONE}"),
    (re.compile(r"^(Time Zone Name\s+:).*$"), r"\1{TIME_ZONE_NAME}"),
    (re.compile(r"^(Enable DST\s+:).*$"), r"\1{DST_ENABLE}"),
    (re.compile(r"^(Notify Reboot\s+:).*$"), r"\1{NOTIFY_REBOOT}"),
    (re.compile(r"^(PNP Enable\s+:).*$"), r"\1{PNP_ENABLE}"),
    (re.compile(r"^(PNP IP\s+:).*$"), r"\1{PNP_IP}"),
    (re.compile(r"^(PNP Port\s+:).*$"), r"\1{PNP_PORT}"),
    (re.compile(r"^(PNP Transport\s+:).*$"), r"\1{PNP_TRANSPORT}"),
    (re.compile(r"^(PNP Interval\s+:).*$"), r"\1{PNP_INTERVAL}"),
]

MMI_PATTERNS = [
    (re.compile(r"^(Account(\d+) Name\s+:).*$"), r"\1{MMI_ACCOUNT\2_NAME}"),
    (re.compile(r"^(###Account(\d+) Password\s+:).*$"), r"\1{MMI_ACCOUNT\2_PASSWORD}"),
    (re.compile(r"^(Account(\d+) Password\s+:).*$"), r"\1{MMI_ACCOUNT\2_PASSWORD}"),
    (re.compile(r"^(Account(\d+) Level\s+:).*$"), r"\1{MMI_ACCOUNT\2_LEVEL}"),
]

FKEY_DSS = re.compile(r"^(Fkey(\d+) (Type|Value|Title)\s+:).*$")
FKEY_PHONE = re.compile(r"^(Fkey(\d+) (Type|Value|Title)\s+).*$")
SOFTFKEY = re.compile(r"^(SoftFkey(\d+) (Type|Value|Title)\s+).*$")

# H2/H6: SoftDss mirrors LINEKEY_* (OEM). W611: Fkey1 static, Fkey2+ SOFTDSS_*.
SOFTDSS_LINEKEY_FILES = frozenset({
    "h2_default_user_config.txt",
    "h6_default_user_config.txt",
})


def transform_line(line: str, section: str | None = None, filename: str = "") -> str:
    if line.startswith("###"):
        return line

    for pat, repl in GLOBAL_REPLACEMENTS:
        if pat.match(line):
            return pat.sub(repl, line)

    for pat, repl in MMI_PATTERNS:
        if pat.match(line):
            return pat.sub(repl, line)

    m = re.match(r"^Item(\d+) ", line)
    if m:
        item_num = m.group(1)
        for field_label, field_key in WIFI_ITEM_FIELDS:
            pat = re.compile(rf"^(Item{item_num} {re.escape(field_label)}\s+:).*$")
            if pat.match(line):
                return pat.sub(rf"\1{{WIFI_ITEM{item_num}_{field_key}}}", line)

    for line_num in (1, 2):
        prefix = f"SIP{line_num} "
        for field_label, field_key in SIP_FIELDS:
            for lead in ("", "###"):
                pat = re.compile(
                    rf"^({re.escape(lead + prefix + field_label)}\s+:).*$"
                )
                if pat.match(line):
                    return pat.sub(rf"\1{{SIP{line_num}_{field_key}}}", line)

    if section == "dsskey2":
        return line

    m = FKEY_DSS.match(line)
    if m:
        n, field = m.group(2), m.group(3).upper()
        if section == "sidekey" and n == "1":
            return line
        if section == "sidekey":
            return f"{m.group(1)}{{SIDEKEY_{n}_{field}}}"
        if section == "softdss":
            if filename in SOFTDSS_LINEKEY_FILES:
                return f"{m.group(1)}{{LINEKEY_{n}_{field}}}"
            if filename == "w611_default_user_config.txt" and n == "1":
                return line
            return f"{m.group(1)}{{SOFTDSS_{n}_{field}}}"
        return f"{m.group(1)}{{LINEKEY_{n}_{field}}}"

    m = FKEY_PHONE.match(line)
    if m:
        n, field = m.group(2), m.group(3).upper()
        return f"{m.group(1)}:{{FKEY{n}_{field}}}"

    m = SOFTFKEY.match(line)
    if m:
        n, field = m.group(2), m.group(3).upper()
        return f"{m.group(1)}:{{SOFTFKEY{n}_{field}}}"

    return line


def transform_file(path: Path) -> None:
    raw = path.read_bytes()
    text = raw.decode("utf-8", errors="replace").replace("\r\n", "\n").replace("\r", "\n")
    lines = text.split("\n")
    out = []
    section = None
    for line in lines:
        if line.startswith("<<VOIP CONFIG FILE>>"):
            out.append("<<VOIP CONFIG FILE>>Version:{CONFIG_VERSION}")
            continue
        if "--Sidekey Config" in line:
            section = "sidekey"
        elif "--Dsskey Config1--" in line:
            section = "dsskey1"
        elif "--Dsskey Config2--" in line:
            section = "dsskey2"
        elif "--SoftDss Config--" in line:
            section = "softdss"
        elif "--Function Key--" in line:
            section = "phone_fkey"
        out.append(transform_line(line, section, path.name))
    path.write_bytes(("\n".join(out) + "\n").encode("utf-8"))
    print(f"updated {path.name} ({len(out)} lines)")


def main() -> None:
    for name in (
        "h2_default_user_config.txt",
        "h5_default_user_config.txt",
        "h6_default_user_config.txt",
        "w611_default_user_config.txt",
    ):
        transform_file(BASE / name)


if __name__ == "__main__":
    main()
