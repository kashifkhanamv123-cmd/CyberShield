const express = require('express');
const { GoogleGenerativeAI } = require('@google/generative-ai');
const dotenv = require('dotenv');
const cors = require('cors');
const path = require('path');

// Load environment variables
dotenv.config();

const app = express();
const port = process.env.PORT || 3000;

// Middleware
app.use(cors());
app.use(express.json());
app.use(express.static('public'));

// Initialize Gemini API
const genAI = new GoogleGenerativeAI(process.env.GEMINI_API_KEY);

// Chat endpoint
app.post('/chat', async (req, res) => {
    try {
        const { message, image } = req.body;

        if (!message && !image) {
            return res.status(400).json({ error: 'Message or image is required' });
        }

        // Use gemini-2.0-flash which supports both text and vision
        const model = genAI.getGenerativeModel({ model: "gemini-2.0-flash" });

        let result;
        if (image) {
            // Handle image + text
            const imageData = image.split(',')[1]; // Strip prefix
            result = await model.generateContent([
                message || "What is in this image?",
                {
                    inlineData: {
                        data: imageData,
                        mimeType: "image/jpeg"
                    }
                }
            ]);
        } else {
            // Text only
            result = await model.generateContent(message);
        }

        const response = await result.response;
        const text = response.text();

        res.json({ response: text });
    } catch (error) {
        console.error('Error with Gemini API:', error);
        res.status(500).json({ 
            error: 'Failed to get response from AI', 
            details: error.message 
        });
    }
});

// Start server
app.listen(port, () => {
    console.log(`Chatbot server running at http://localhost:${port}`);
});
