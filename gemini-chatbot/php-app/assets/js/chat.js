/**
 * Gemini Chatbot Frontend Logic
 * Handles UI updates and communication with the PHP backend.
 */

document.addEventListener('DOMContentLoaded', () => {
    const chatForm = document.getElementById('chat-form');
    const userInput = document.getElementById('user-input');
    const chatBox = document.getElementById('chat-box');
    const loadingIndicator = document.getElementById('loading');

    /**
     * Appends a message to the chat container
     * @param {string} text - The message content
     * @param {string} sender - 'user' or 'bot'
     */
    const appendMessage = (text, sender) => {
        const messageDiv = document.createElement('div');
        messageDiv.classList.add('message', sender);
        messageDiv.textContent = text;
        
        // Insert before the loading indicator
        chatBox.insertBefore(messageDiv, loadingIndicator);
        
        // Scroll to bottom
        chatBox.scrollTop = chatBox.scrollHeight;
    };

    /**
     * Handles the form submission
     */
    chatForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const message = userInput.value.trim();
        if (!message) return;

        // 1. Display user message
        appendMessage(message, 'user');
        userInput.value = '';
        userInput.focus();

        // 2. Show loading indicator
        loadingIndicator.style.display = 'flex';
        chatBox.scrollTop = chatBox.scrollHeight;

        try {
            // 3. Send message to PHP backend
            const response = await fetch('api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ message: message })
            });

            const data = await response.json();

            // 4. Hide loading indicator
            loadingIndicator.style.display = 'none';

            // 5. Handle response
            if (data.reply) {
                appendMessage(data.reply, 'bot');
            } else if (data.error) {
                appendMessage("Error: " + data.error, 'bot');
            } else {
                appendMessage("An unexpected error occurred.", 'bot');
            }

        } catch (error) {
            console.error('Fetch error:', error);
            loadingIndicator.style.display = 'none';
            appendMessage("Could not connect to the server. Please check your connection.", 'bot');
        }
    });
});
