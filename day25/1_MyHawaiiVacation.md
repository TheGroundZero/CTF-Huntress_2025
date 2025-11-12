# My Hawaii Vacation

| Category    | Author       |
| ----------- | ------------ |
| 🐞 Malware | John Hammond |

> Oh jeeeez... I was on Booking.com trying to reserve my Hawaii vacation.
> 
> Once I tried verifying my ID, suddenly I got all these emails saying that my password was changed for a ton of different websites!! What is happening!?!
> 
> I had a `flag.txt` on my desktop, but that's probably not important...
> 
> Anyway, I still can't even finish booking my flight to Hawaii!! Here is the site I was on... can you get this thing to work!??!

> **CAUTION**
> 
> This is the Malware category, and as such, includes malware. Please be sure to analyze these files within an isolated virtual machine.

- Visiting the host, we see a "Booking" page.
- Trying to validate our booking, we're asked to download a "verification" exe.
- Using FLOSS to extract strings we get a few usefull values:

```
$Lua: Lua 5.1 Copyright (C) 1994-2006 Lua.org, PUC-Rio $
$Authors: R. Ierusalimschy, L. H. de Figueiredo & W. Celes $
$URL: www.lua.org $
```

