const chatForm = document.getElementById('chat-form');
const userInput = document.getElementById('user-input');
const chatContainer = document.getElementById('chat-container');
const imageInput = document.getElementById('image-input');
const imagePreviewContainer = document.getElementById('image-preview-container');
const imagePreview = document.getElementById('image-preview');
const removeImageBtn = document.getElementById('remove-image-btn');

let selectedImageBase64 = null;

// Handle image selection
imageInput.addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = (event) => {
            selectedImageBase64 = event.target.result;
            imagePreview.src = selectedImageBase64;
            imagePreviewContainer.style.display = 'flex';
        };
        reader.readAsDataURL(file);
    }
});

// Handle image removal
removeImageBtn.addEventListener('click', () => {
    selectedImageBase64 = null;
    imageInput.value = '';
    imagePreviewContainer.style.display = 'none';
    imagePreview.src = '';
});

// Function to add a message to the chat container
function addMessage(text, isBot = false) {
    const messageDiv = document.createElement('div');
    messageDiv.classList.add('message');
    messageDiv.classList.add(isBot ? 'bot-message' : 'user-message');
    messageDiv.textContent = text;
    chatContainer.appendChild(messageDiv);
    
    // Scroll to bottom
    chatContainer.scrollTop = chatContainer.scrollHeight;
}

// Function to show typing indicator
function showTypingIndicator() {
    const indicator = document.createElement('div');
    indicator.classList.add('message', 'bot-message', 'typing-indicator');
    indicator.id = 'typing-indicator';
    indicator.innerHTML = '<span></span><span></span><span></span>';
    chatContainer.appendChild(indicator);
    chatContainer.scrollTop = chatContainer.scrollHeight;
}

// Function to remove typing indicator
function removeTypingIndicator() {
    const indicator = document.getElementById('typing-indicator');
    if (indicator) {
        indicator.remove();
    }
}

// Handle form submission
chatForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const message = userInput.value.trim();
    if (!message && !selectedImageBase64) return;

    // Clear input and preview
    userInput.value = '';
    const currentImage = selectedImageBase64;
    
    if (selectedImageBase64) {
        imagePreviewContainer.style.display = 'none';
        selectedImageBase64 = null;
        imageInput.value = '';
    }
    
    // Add user message to UI
    if (message) {
        addMessage(message, false);
    } else {
        addMessage("(Sent an image)", false);
    }
    
    // Show typing indicator
    showTypingIndicator();

    try {
        // Decide which API to call. Since the user is working in PHP, 
        // we'll try the PHP API if it's available, otherwise fallback to /chat
        const apiEndpoint = window.location.pathname.includes('php-app') ? 'api.php' : '/chat';
        
        const response = await fetch(apiEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ 
                message: message || "What is in this image?", 
                image: currentImage 
            })
        });

        const data = await response.json();
        
        // Remove indicator and add bot response
        removeTypingIndicator();
        
        const reply = data.reply || data.response;
        if (reply) {
            addMessage(reply, true);
        } else if (data.error) {
            addMessage(`Error: ${data.error}`, true);
        }
    } catch (error) {
        removeTypingIndicator();
        addMessage('Sorry, something went wrong. Please check if the server is running.', true);
        console.error('Fetch error:', error);
    }
});
