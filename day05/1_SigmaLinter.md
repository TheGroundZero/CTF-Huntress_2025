# Sigma Linter

| Category | Author       |
| -------- | ------------ |
| 🌐 Web   | John Hammond |

> Oh wow, another web app interface for command-line tools that already exist!
> 
> This one seems a little busted, though...

Download `app.js` from the web app

Potential attack path

```js
fetch('/lint', { /* ... */ })
/* ... */
.catch(error => {
        displayResults({
            result: false,
            reasons: ['Network error: ' + error.message],
            error_type: 'network_error'
        });
    })
```

```js
function displayResults(data) {
    /* ... */
    if (data.result && (data.error_type === 'none' || data.error_type === 'validation')) {
        /* ... */
    } else {
        const reasonsList = data.reasons.map(reason => `<li>${escapeHtml(reason)}</li>`).join('');
        reasonsSection = `
            <p>${message}</p>
            <ul class="result-reasons">
                ${reasonsList}
            </ul>
        `;
    }
    /* ... */
    resultsContainer.innerHTML = `
        <div class="${resultClass}">
            <div class="result-title">
                ${icon} ${resultTitle}
            </div>
            ${reasonsSection}
        </div>
        ${formattedCodeSection}
    `;
```