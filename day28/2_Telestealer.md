# Telestealer

| Category   | Author      |
| ---------- | ----------- |
| 🐞 Malware | Ben Folland |

> Our threat intelligence team reported that Ben's data is actively being sold on the dark web. During the incident response, the SOC identified a suspicious JavaScript file within Ben's Downloads folder.
> 
> Can you recover the stolen data?

> **NOTE**
> 
> The password to the ZIP archive is `telestealer`

**NOTE**  
The challenge was updated after launch, that's why I have a v1 and v2 here.

- Download is a JavaScript with a bunch of base64 encoded data which gets passed along to Powershell.
- Base64 decoded text contains base64 encoded parameters `$key`, `$iv` which are used to decode `$cipher`. The output gets written to an exe which then is then executed.
- Checking strings within the exe with e.g. FLOSS reveals some interesting bits revealing we're dealing with an infostealer:

```
***********************************************************
*                                                         *
*                                                         *
*                          _    ___   ___  ___ ___ ___    *
*      Best               | |  / _ \ / __|/ __| __| _ \   *
*                         | |_| (_) | (_ | (_ | _||   /   *
*      Private  - - - ->  |____\___/ \___|\___|___|_|_\   *
*                                                         *
*                                                         *
*                                                         *
***********************************************************
```

```
==========PC INFO==========
Client Name:
FullDate: 
IP: 
Country: 
==========PC INFO==========
@st3al3rb3n_bot
-4862820035
===============
```

```
https://api.telegram.org/bot
/sendDocument?chat_id=
&caption=
```
- We likely need to figure out how it communicates with Telegram
- Let's detonate it in a VM with `mitmproxy` in-between.