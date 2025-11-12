# Angler

| Category         | Author    |
| ---------------- | --------- |
| 📦 Miscellaneous | Tim Sword |

> These scribbles are impossible to read!
>
> `42 6c 6f 77 66 69 73 68`
> 
> Some crazy fisherman came by, dropped this note, and was muttering something in his drunken stupor, about his fishing pole and taking out... murlocs in Entra? and CyberChef!?
> 
> I don't get it. You're the expert here! Not me!

> **WARNING**
> 
> This challenge is designed for you to have a look around using enumeration tooling and emphasises thinking "outside the box", versus challenging your ability to 'pwn' the tenant. Please do not sabotage the challenge!
> 
> MFA is intended for this challenge. If you cannot sign in, try a different way.
> 
> This challenge uses flags that are not in the standard format of `flag{[MD5HASH]}`. You will find flags with the `flag{` prefix and `}` suffix, but a short alphanumeric string with some special characters wrapped inside the curly braces.

**What is the FINAL flag? This flag is unlike the others and ends with a `?` character.**

> **NOTE**
> 
> This challenge also includes some "bonus flags" along the way that are still worth some points.

- Submit the bonus flag that ends with the character `2`.
- Submit the bonus flag that ends with the character `d`.
- Submit the bonus flag that ends with the character `a`.
- Submit the bonus flag that ends with the character `m`.
- Submit the bonus flag that ends with the character `c`.

---

`42 6c 6f 77 66 69 73 68` decoded from HEX results in `Blowfish`

Decoding `scribbles.dat` from Blowfish using `42 6c 6f 77 66 69 73 68` for both key as IV, results in a text file with lots of 8-bit segments.

```
#scribbles2
01100100 00110011 00100000 01100100 00110011 00100000  #...
```

This binary decodes into a bunch of hexadecimal combinations.

```
#scribbles3
d3 d3 #...
```

Hex decoding this results in what appears to be garbage input.

```
ÓÓv #...
```

However, `d3 d3` reversed (`3d 3d`) hex decodes into `==`.
So reversing the hex output before hex decoding results in what appears to be base64 encoded data.

```
#scribbles4
cGhp #...
```

When then finally decodes in something that leads to a next phase:

```
#scribbles5
phisher@4rhdc6.onmicrosoft.com:PhishingAllTheTime19273!!

My sea is made of data, my shore a glowing screen, I cast my line with careful code, in a vast and global scene.
My lure is not a worm or fly, but a name you trust, a prize, My hook is hid within a link, disguised before your eyes.
I do not fish for flesh or fin, but for a private key, reeling in the secrets you mistakenly give to me.
Send to that which is found within the above, to the destination you have yet to reveal, and the secret you seek will reveal.
```

Let's try to login. But where?

Attempting to login on `https://portal.azure.com` initially works until we are blocked by a 2FA prompt.
At least we now know the username and password are correct.


__Solution discovered thanks to hints__

Apparently https://admin.microsoft.com doesn't ask you for a MFA token.

Browsing through the users doesn't appear to reveal much.
Between the Security Groups, however, we find `flag{mczxals2amxc}` which solves the `c flag`.

That group has an email address in the description: `nattyp@51tjxh.onmicrosoft.com`.
