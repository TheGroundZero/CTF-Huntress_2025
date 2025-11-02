# Puzzle Pieces Redux

| Category     | Author     |
| ------------ | ---------- |
| 🔍 Forensics | @Nordgaren |


> Well, I accidentally put my important data into a bunch of executables... just don't ask, okay?
> 
> It was fine... until my cat Sasha stepped on my keyboard and messed everything up! OH NOoOoO00!!!!!111
>
> Can you help me recover my important data?

- Each file is a Windows executable. The `file` command returns `PE32+ executable (console) x86-64, for MS Windows` for all of them.
- Looking into strings, e.g. with FLOSS, reveals these are build with Rust as well.
- Executing each file in `Wine` results in some snippets to be returned

```sh
$ for f in $(ls *.bin); do echo -en "$f:\t\t"; wine $f; done
07c8b8cb6a9.bin:        5abfa
1a1962fc.bin:           f9f73
20a.bin:	            d85d5
3511c0a625.bin:         16f73
5eb6e6c8.bin:           49f8b
64b.bin:		        e6817
6676585.bin:		    02}
7c8394d4b6b0.bin:       d9c1a
99fa27fd897.bin:	    23c
a6ffddda.bin:		    88a2d
a891a220.bin:		    flag{
abc9.bin:               48979
c931.bin:               5f93f
d2def806d493f.bin:	    9bfc2
db887b5440.bin:		    be7a1
e75147c1b1b9406.bin:	f18ba
```

- Notice how that we have a `flag{` and `02}` here. Looks like start and end of flag string.
- We need to combine these outputs somehow to end up with a `flag{<MD5 string>}` somehow...
- Look at the challenge description: "Sasha(sh) will help us", "`OH NOoOoO00!!!!!111`"
- Let's grab the sha256 hashes of each binary
- Notice the trailing `0` at the end of each hash. They seem to reflect some sort of ordering.

```sh
$ sha256sum *.bin
45951368223b60ee10f964785f96251fbfd2988af1c7cbb66bd27570ed000000  07c8b8cb6a9.bin
3a389838f872c04ee98b56b47b026c56e9e1bf9a791d33f07991d72c8eb20083  1a1962fc.bin
7f0c897e241ac92d0c4c9ecf680cf8c570c72cc2a1a99ab50ce218c518fd0000  20a.bin
79ec81fe08fded6518d296f50e9d9ef1524ea0c95d88b94b376834351ee53485  3511c0a625.bin
dec0721f3014e22cb1b121f065adaa6debf070c4dc86d4446cb3d6cb87300000  5eb6e6c8.bin
3bd187f44e284ff90986ed67104a53cff73fed14a55902a343a86a2108e7f000  64b.bin
aecc3b8b3b871ac034c60ddb7c0698105bcf4c768603a0b4f64e3a1100000000  6676585.bin
b8f23f0b8cb91161a8a757dc74d7f89d634f2bb50455233425105e44511150a5  7c8394d4b6b0.bin
598ef46397a9dbf5fe468543022f72a8014f7d0b9448058955f896914fa53f57  99fa27fd897.bin
27f1c4dad4c5e5bc3369adde78dc739121acd64a9549587f9f82a83b520f8704  a6ffddda.bin
ee1520fbe2b1dc1bb85321ddc602aa043f7728440e48524fb1b67e1b272822e0  a891a220.bin
0fd014cc10ca48f4c65e9be49914aa0c7e24a19c561801541185473e8a08f9a4  abc9.bin
d81c9372e8fe20e0917bfee218a8e9c78bdb4a41ad2f234b1d28865aa1eb7669  c931.bin
0b8b764a058b59bcb7868e3c402119b50ca02e6fb6eb98deec4821efcd82c29b  d2def806d493f.bin
016f23e8ac531cec3da547a0e0bc732b4ce96d26306c83b38ec14f7bd2a9e700  db887b5440.bin
3a9d1b97597e38008e13e2ba64667667bb1a6cdc43b905a826d91496b0000000  e75147c1b1b9406.bin
```

The correct order is:

```
ee15[...]7e1b272822e0    a891a220.bin:          flag{
016f[...]4f7bd2a9e700    db887b5440.bin:        be7a1
3bd1[...]6a2108e7f000    64b.bin:               e6817
7f0c[...]18c518fd0000    20a.bin:               d85d5
dec0[...]d6cb87300000    5eb6e6c8.bin:          49f8b
4595[...]7570ed000000    07c8b8cb6a9.bin:       5abfa
3a9d[...]1496b0000000    e75147c1b1b9406.bin:   f18ba
aecc[...]3a1100000000    6676585.bin:           02}
```