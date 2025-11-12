# Trashcan

| Category     | Author       |
| ------------ | ------------ |
| 🔍 Forensics | John Hammond |

> Have you ever done forensics on the Recycle Bin? It's... a bit of a mess. Looks like the threat actor pulled some tricks to hide data here though.
>
> The metadata might not be what it should be. Can you find a flag?

Running `file` on the contents of Trashcan, we can identify 2 groups.

1. `ASCII text` files with filenames starting with `$I` that all contain the same text:
   > When did I throw this out!?!?
2. `Matlab v4 mat-file (little endian)` files starting with `$R` containing:
   > C:\Users\flag\Desktop\flag.txt


Doing some research on Windows trash files reveals that the `$I` files contain the metadata of deleted files while the `$R` files contain the actual content.
A tool like `RBCmd` (https://github.com/EricZimmerman/RBCmd) can analyse these files for us.

```
RBCmd version 1.6.1.0

Author: Eric Zimmerman (saericzimmerman@gmail.com)
https://github.com/EricZimmerman/RBCmd

Command line: -d trashcan

Looking for files in trashcan

Found 117 files. Processing...

Source file: trashcan\$I01XCGF.txt

Version: 2 (Windows 10/11)
File size: 49 (49B)
File name: C:\Users\flag\Desktop\flag.txt
Deleted on: 1642-12-14 09:40:03

Source file: trashcan\$I08ZI07.txt

Version: 2 (Windows 10/11)
File size: 50 (50B)
File name: C:\Users\flag\Desktop\flag.txt
Deleted on: 1642-12-14 09:40:04
```

Looking at the file size, we can see some variations.
`grep "File size" rbc_log.txt | cut -d: -f2 | cut -d'(' -f 1`

Could this be decimal encoding of characters?

```
12d5elgd80b286ld7}22003e795571a130b981{e1005leb867dee9g8abge{e55726381ae}d21
85821e12125d2
15e6}ffb{5
b5d6e1df82d2576
```

What if we sort based on deletion timestamp?

Reformat the RBCmd log a little by removing the RBCmd header, turning it more in a csv-like format with one line for each file, and moving the timestamp to the start so we can more easily sort.
If we then grab the file sizes and decimal decode them, we get `ffflllaaaggg{{{111ddd222bbb222bbb000555666777111eeeddd111eeeeee555888111222666777888888555000ddd555eee333222999}}}`

Let's deduplicate that using the following search `(.)\1{2}` and replace `$1` to deduplicate all groups of 3 repeating characters.

`flag{1d2b2b05671ed1ee5812678850d5e329}`