const fs = require('fs');
const ejs = require('ejs');
const path = require('path');
const express = require('express');
const bodyParser = require('body-parser');
const app = express();

app.set('view engine', 'ejs');
app.set('views', path.join(__dirname, 'views'));

app.use(express.static(path.join(__dirname, 'public')));

app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: true }));

let profile = {
    emoji: "😊"
};

app.post('/setEmoji', (req, res) => {
    const { emoji } = req.body;
    profile.emoji = emoji;
    res.json({ profileEmoji: emoji });
});

app.get('/', (req, res) => {
    fs.readFile(path.join(__dirname, 'views', 'index.ejs'), 'utf8', (err, data) => {
        if (err) {
            return res.status(500).send('Failed to read server file. Please notify a CTF admin.');
        }
        
        try {
            const profilePage = data.replace(/<% profileEmoji %>/g, profile.emoji);
            const renderedHtml = ejs.render(profilePage, { profileEmoji: profile.emoji });
            res.send(renderedHtml);
        } catch (renderErr) {
            res.send("An error occurred: " + renderErr)
        }
    });
});

const PORT = 3000;
app.listen(PORT, () => {
    console.log(`Server is running on port ${PORT}`);
});
