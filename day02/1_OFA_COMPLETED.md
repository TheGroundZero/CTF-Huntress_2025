# OFA

| Category   | Author                  |
| ---------- | ----------------------- |
| 👶 Warmups | Matt Kiely (HuskyHacks) |

> Two factors? In this economy??!!

1. Launch instance and visit it in your browser
2. Make sure to log traffic
3. Login with any(?) credential like `admin:admin`
4. Notice a cookie is being set
    ```
    Set-Cookie: session=eyJvdHAiOiIxMDMyNDgiLCJ1c2VybmFtZSI6ImFkbWluIn0.aPGQhw.fp2rHsN4JX7-JAbX9wYehQFCcLw; HttpOnly; Path=/
    ```
5. base64 decode cookie value
6. Find OTP key in decoded values
    ```
    {"otp":"103248","username":"admin"}
    ```
7. Login with this OTP token
8. Grab flag

`flag{013cb9b123afec26b572af5087364081}`