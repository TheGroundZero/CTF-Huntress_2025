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