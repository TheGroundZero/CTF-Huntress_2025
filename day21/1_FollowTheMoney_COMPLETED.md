# Follow The Money

| Category | Author |
| -------- | ------ |
| 🕵️ OSINT | @Brady |


> Hey Support Team,
> 
> We had a bit of an issue yesterday that I need you to look into ASAP. There's been a possible case of money fraud involving our client, Harbor Line Bank. They handle a lot of transfers for real estate down payments, but the most recent one doesn't appear to have gone through correctly.
> 
> Here's the deal, we need to figure out what happened and where the money might have gone. The titling company is looping in their incident response firm to investigate from their end. I need you to quietly review things on our end and see what you can find. Keep it discreet and be passive.
> 
> I let Evelyn over at Harbor Line know that someone from our team might reach out. Her main email is offline right now just in case it was compromised, she's using a temporary address until things get sorted out:
> 
> evelyn.carter@51tjxh.onmicrosoft.com
> 
> **IMPORTANT**
> 
> This challenge uses a non-standard flag format.
> 
> **NOTE**
> 
> The password to the ZIP archive below is `follow_the_money`.

Zipfile contains 5 emails (.eml)

Email 5 contains modified link in footer `evergatetltle.netlify.app` instead of `evergatetitle.netlify.app`

Do a funds transfer

```
Details Submitted

Thanks for giving me your bank! Your friend, aHR0cHM6Ly9uMHRydXN0eC1ibG9nLm5ldGxpZnkuYXBwLw== Retrieval ID: 471082
```

Base64 string decodes to `https://n0trustx-blog.netlify.app/`

Website contains link to GitHub account of `N0TrustX` -> name of the hacker

1 GitHub repo: https://github.com/N0TrustX/Spectre.git

`spectre.html` contains a base54 encoded payload

```html
<!-- The Base64 encoded object is stored here, hidden from view -->
<div id="encodedPayload" class="hidden">ZmxhZ3trbDF6a2xqaTJkeWNxZWRqNmVmNnltbHJzZjE4MGQwZn0=</div>
```

decodes to `flag{kl1zklji2dycqedj6ef6ymlrsf180d0f}`