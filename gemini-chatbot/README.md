# Gemini AI Chatbot

A modern, responsive chatbot built with Node.js, Express, and the Google Gemini API.

## Features
- ✨ **Gemini Pro Integration**: Powered by Google's latest AI model.
- 🎨 **Premium UI**: Modern glassmorphic design with smooth animations.
- 📱 **Responsive**: Works perfectly on desktop and mobile.
- 🛡️ **Secure**: Uses environment variables for API key management.

## Prerequisites
- Node.js installed on your machine.
- A Google Gemini API Key (get it from [Google AI Studio](https://aistudio.google.com/app/apikey)).

## Installation

1. Navigate to the project directory:
   ```bash
   cd gemini-chatbot
   ```

2. Install dependencies:
   ```bash
   npm install
   ```

3. Configure your API key:
   - Open the `.env` file.
   - Replace `your_api_key_here` with your actual Google Gemini API key.

## Running the Server

Start the server using:
```bash
npm start
```

The application will be available at `http://localhost:3000`.

## Project Structure
- `index.js`: Backend Express server and API integration.
- `.env`: Environment variables (API keys).
- `public/`: Frontend assets (HTML, CSS, JS).
- `package.json`: Project dependencies and scripts.
