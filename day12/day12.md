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
> 
> **WARNING**
> 
> This challenge is designed for you to have a look around using enumeration tooling and emphasises thinking "outside the box", versus challenging your ability to 'pwn' the tenant. Please do not sabotage the challenge!
> 
> MFA is intended for this challenge. If you cannot sign in, try a different way.
> 
> This challenge uses flags that are not in the standard format of `flag{[MD5HASH]}`. You will find flags with the `flag{` prefix and `}` suffix, but a short alphanumeric string with some special characters wrapped inside the curly braces.
> 
> **NOTE**
> 
> This challenge also includes some "bonus flags" along the way that are still worth some points.


`42 6c 6f 77 66 69 73 68` decoded from HEX results in `Blowfish`

Decoding `scribbles.dat` from Blowfish using `42 6c 6f 77 66 69 73 68` for both key as IV, results in a text file with lots of 8-bit segments.

