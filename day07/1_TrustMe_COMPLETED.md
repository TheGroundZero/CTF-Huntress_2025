# Trust Me

| Category         | Author       |
| ---------------- | ------------ |
| 📦 Miscellaneous | John Hammond |

> C'mon bro, trust me! Just trust me!! Trust me bro!!!
> 
> The `TrustMe.exe` program on this Windows desktop "doesn't trust me?"
> 
> It says it will give me the flag, but only if I "have the permissions of Trusted Installer"...?
> 
> If you are using the VPN, you can RDP to this challenge with:
> 
> Username: `Administrator`
> Password: `h$4#82PSK0BUBaf7`

> **NOTE**
> 
> This virtual machine does not have Internet access.

- RDP into the machine and notice an executable on the Desktop.
- Run it
- Doesn´t trust us, we need to get Trusted Installed permission
- Download `RunAsTI` https://github.com/fafalone/RunAsTrustedInstaller/releases/tag/v2.3.2
- Map drive over RDP to copy RunAsTI
- Execute RunAsTI and select TrustMe as target
- Win

`flag{c6065b1f12395d526595e62cf1f4d82a}`