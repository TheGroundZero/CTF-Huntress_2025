let editor;

document.addEventListener('DOMContentLoaded', function() {
    editor = CodeMirror.fromTextArea(document.getElementById('yaml-editor'), {
        mode: 'yaml',
        theme: 'monokai',
        lineNumbers: true,
        indentUnit: 2,
        tabSize: 2,
        lineWrapping: true,
        autoCloseBrackets: true,
        matchBrackets: true,
        foldGutter: true,
        gutters: ['CodeMirror-linenumbers', 'CodeMirror-foldgutter']
    });

    document.getElementById('lint-btn').addEventListener('click', lintRule);
    loadExamples();
});

function lintRule() {
    const yamlContent = editor.getValue();
    
    const lintButton = document.getElementById('lint-btn');
    const resultsContainer = document.getElementById('results');
    
    lintButton.disabled = true;
    lintButton.textContent = 'Linting...';
    
    fetch('/lint', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            yaml_content: yamlContent,
            method: 's2'
        })
    })
    .then(response => response.json())
    .then(data => {
        displayResults(data);
    })
    .catch(error => {
        displayResults({
            result: false,
            reasons: ['Network error: ' + error.message],
            error_type: 'network_error'
        });
    })
    .finally(() => {
        lintButton.disabled = false;
        lintButton.textContent = 'Lint Rule';
    });
}

function displayResults(data) {
    const resultsContainer = document.getElementById('results');
    
    let resultClass = 'result-error';
    let resultTitle = 'Error';
    let icon = '⚠️';
    let message = '';
    
    if (data.result && (data.error_type === 'none' || data.error_type === 'validation')) {
        resultClass = 'result-valid';
        resultTitle = 'Valid';
        icon = '✅';
    } else if (data.error_type === 'validation') {
        resultClass = 'result-invalid';
        resultTitle = 'Invalid';
        icon = '❌';
    } else if (data.error_type === 'yaml_error') {
        resultClass = 'result-error';
        resultTitle = 'YAML Error';
        icon = '⚠️';
    } else if (data.error_type === 'unsupported') {
        resultClass = 'result-error';
        resultTitle = 'Unsupported';
        icon = '⚠️';
        message = 'Unsupported format:';
    } else if (data.error_type === 'unexpected') {
        resultClass = 'result-error';
        resultTitle = 'Unexpected Error';
        icon = '⚠️';
        message = 'Unexpected error:';
    }
    
    let reasonsSection = '';
    if (data.result && (data.error_type === 'none' || data.error_type === 'validation')) {
        reasonsSection = `<p>${message}</p>`;
    } else {
        const reasonsList = data.reasons.map(reason => `<li>${escapeHtml(reason)}</li>`).join('');
        reasonsSection = `
            <p>${message}</p>
            <ul class="result-reasons">
                ${reasonsList}
            </ul>
        `;
    }
    
    let formattedCodeSection = '';
    if (data.formatted_code) {
        formattedCodeSection = `
            <div class="formatted-code">
                <strong>Formatted Sigma Rule:</strong>
                <div id="formatted-editor"></div>
            </div>
        `;
    }
    
    resultsContainer.innerHTML = `
        <div class="${resultClass}">
            <div class="result-title">
                ${icon} ${resultTitle}
            </div>
            ${reasonsSection}
        </div>
        ${formattedCodeSection}
    `;
    
    if (data.formatted_code && formattedCodeSection) {
        setTimeout(() => {
            const formattedEditor = CodeMirror(document.getElementById('formatted-editor'), {
                value: data.formatted_code,
                mode: 'yaml',
                theme: 'monokai',
                lineNumbers: true,
                readOnly: true,
                indentUnit: 2,
                tabSize: 2,
                lineWrapping: true,
                foldGutter: true,
                gutters: ['CodeMirror-linenumbers', 'CodeMirror-foldgutter']
            });
        }, 100);
    }
}

function loadExamples() {
    fetch('/examples')
    .then(response => response.json())
    .then(data => {
        const examplesContainer = document.getElementById('examples');
        examplesContainer.innerHTML = '';
        
        data.forEach(example => {
            const exampleCard = document.createElement('div');
            exampleCard.className = 'example-card';
            exampleCard.innerHTML = `
                <h4>${escapeHtml(example.name)}</h4>
                <p>Click to load this example into the editor</p>
            `;
            exampleCard.addEventListener('click', () => {
                editor.setValue(example.content);
                editor.focus();
            });
            examplesContainer.appendChild(exampleCard);
        });
    })
    .catch(error => {
        document.getElementById('examples').innerHTML = `
            <div class="loading">Error loading examples: ${error.message}</div>
        `;
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
