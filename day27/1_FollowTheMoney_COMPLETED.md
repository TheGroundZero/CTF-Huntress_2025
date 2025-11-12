# Follow The Money: The Sequel

| Category | Author |
| -------- | ------ |
| 🕵️ OSINT | @Brady |

> **WARNING**
>
> The initial Follow the Money challenge should be completed first before this challenge.

Hey Support Team,

Thanks for your help the other day! After seeing the way you handled yourself and gathered these details, I wanted to see if I could get a bit more help from you. I know you found their username the other day. See what you can do with that. I need you to find the town that this hacker lives in. I don't think the IR firm is doing enough. I want to have every piece of information we can find. Maybe we can pay a visit. Let me know what you find. Thanks!

> **IMPORTANT**
>
> This challenge DOES NOT require you to contact any businesses. This can be fully solved with publicly available information. Being that this is OSINT and public-facing, please DO NOT do anything to disrupt other CTF players or others that are not involved with the CTF.

> **IMPORTANT**
>
> This challenge uses a non-standard flag format.

Flags
- What town does our hacker friend live in?  
  `Wytheville`
- Can you find the nearby flag?  
  `Flag{this_is_good_java}`

- From previous challenge (day21): username of hacker is `N0TrustX`.
- Use social media search tool to find X profile https://x.com/N0TrustX
- Find tweets referencing home location containing pictures
- Look for clues in pictures that may reveal location, like `Elisabeth Brown Park`
- End up in Wytheville, Virginia, USA
- Find tweet referencing leaving reviews for local business. Notice coffee theme.  
  > Always review your local business. Really helps them out.☕️⭐️⭐️⭐️⭐️⭐️
  > Working on some Java in this town. Pretty great Java
  > - Anyway, back to the grind
- Look for coffee bars in Wytheville
- End up looking at Google Reviews of a place called `The Grind`
- Find Google Review by Jim Bossem mentioning programming and java.  
  Flag both in picture and text
  https://maps.app.goo.gl/eSsfNPsyXqmAu92L9

  `Flag{this_is_good_java}`