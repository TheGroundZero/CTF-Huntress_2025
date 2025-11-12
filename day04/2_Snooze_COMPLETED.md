# Snooze

| Category   | Author       |
| ---------- | ------------ |
| 👶 Warmups | John Hammond |

> Don't bug me, I'm sleeping! Zzzz... zzz... zzzz....
> 
> Uncover the flag from the file presented.

!. Download the file
2. Run `file` on it
    ```
    snooze: compress'd data 16 bits
    ```
3. Some researchs reveals this is a Linux compressed file
4. Rename to `file.z` and run `uncompress snooze.z`

`flag{c1c07c90efa59876a97c44c2b175903e}`