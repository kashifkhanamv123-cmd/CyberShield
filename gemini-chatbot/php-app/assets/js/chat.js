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

        // 2. Show loading indicator (Typing simulation)
        loadingIndicator.style.display = 'flex';
        chatBox.scrollTop = chatBox.scrollHeight;

        // Simulate thinking time
        setTimeout(() => {
            // 3. Get response from local script
            const reply = getAssistantResponse(message);

            // 4. Hide loading indicator
            loadingIndicator.style.display = 'none';

            // 5. Display bot message with typing effect
            appendBotMessage(reply);
        }, 800 + Math.random() * 1000);
    });

    /**
     * Appends a bot message with a simple typing effect
     * @param {string} text 
     */
    const appendBotMessage = (text) => {
        const messageDiv = document.createElement('div');
        messageDiv.classList.add('message', 'bot');
        chatBox.insertBefore(messageDiv, loadingIndicator);
        
        let i = 0;
        const speed = 20; // ms per character

        function type() {
            if (i < text.length) {
                messageDiv.textContent += text.charAt(i);
                i++;
                chatBox.scrollTop = chatBox.scrollHeight;
                setTimeout(type, speed);
            }
        }
        
        type();
    };
});
