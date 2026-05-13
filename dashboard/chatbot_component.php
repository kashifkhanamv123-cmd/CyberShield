<!-- CyberShield Elite AI Assistant Component -->
<?php
    // Calculate the path to the dashboard folder
    $current_dir = str_replace('\\', '/', dirname($_SERVER['PHP_SELF']));
    $base_chat_path = (strpos($current_dir, '/modules/') !== false) ? '../../dashboard/' : ( (strpos($current_dir, '/dashboard') !== false) ? '' : 'dashboard/' );
?>
<div id="ai-chatbot-container" class="fixed bottom-8 right-8 z-[1000] font-['Inter',sans-serif]" data-base-path="<?php echo $base_chat_path; ?>">
    <!-- Toggle Button -->
    <button id="chatbot-toggle" class="size-16 rounded-full bg-primary flex items-center justify-center shadow-[0_0_30px_-5px_#a0f000] hover:scale-110 transition-all duration-300 group">
        <span class="material-symbols-outlined text-neutral-dark text-3xl font-black group-hover:rotate-12 transition-transform">smart_toy</span>
        <div class="absolute -top-1 -right-1 size-4 bg-red-500 rounded-full border-2 border-neutral-dark animate-pulse"></div>
    </button>

    <!-- Chat Window -->
    <div id="chat-window" class="absolute bottom-20 right-0 w-[380px] h-[550px] bg-neutral-dark/90 backdrop-blur-lg border border-primary/20 rounded-[2.5rem] shadow-2xl flex flex-col overflow-hidden origin-bottom-right transition-all duration-500 pointer-events-none" style="transform: scale(0); opacity: 0;">
        <!-- Header -->
        <div class="p-6 border-b border-primary/10 flex items-center justify-between bg-primary/5">
            <div class="flex items-center gap-3">
                <div class="size-10 rounded-xl bg-primary/20 flex items-center justify-center text-primary shadow-glow">
                    <span class="material-symbols-outlined text-2xl font-black">shield_with_heart</span>
                </div>
                <div>
                    <h3 class="text-white text-xs font-black uppercase tracking-widest">LUNA <span class="text-primary italic">AI</span></h3>
                    <p class="text-[9px] text-primary/60 font-mono uppercase tracking-tighter flex items-center gap-1">
                        <span class="size-1.5 bg-primary rounded-full animate-pulse"></span>
                        Friendly Assistant Online
                    </p>
                </div>
            </div>
            <button id="close-chat" class="text-slate-500 hover:text-white transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <!-- Messages Area -->
        <div id="chat-messages" class="flex-1 overflow-y-auto p-6 space-y-6 custom-scrollbar bg-neutral-dark/30">
            <!-- AI Welcome -->
            <div class="flex gap-3">
                <div class="size-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary shrink-0">
                    <span class="material-symbols-outlined text-sm font-black">face</span>
                </div>
                <div class="bg-surface-light/50 border border-border-dim p-4 rounded-2xl rounded-tl-none max-w-[85%]">
                    <p class="text-[11px] text-slate-300 leading-relaxed">
                        Hi there! I'm Luna, your friendly security assistant. I'm here to help you learn and secure the network. How can I help you today? 😊
                    </p>
                </div>
            </div>
        </div>

        <!-- Input Area -->
        <div class="p-6 bg-neutral-dark border-t border-primary/10 relative z-[100] pointer-events-auto">
            <form id="chat-form" class="relative">
                <input type="text" id="chat-input" placeholder="Ask me anything..." 
                    style="color: white !important; background-color: #161810 !important;"
                    class="w-full border border-border-dim rounded-2xl px-5 py-4 text-[11px] placeholder:text-slate-600 focus:border-primary/50 outline-none transition-all pr-14">
                <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 size-10 rounded-xl bg-primary/10 text-primary hover:bg-primary hover:text-neutral-dark transition-all flex items-center justify-center pointer-events-auto">
                    <span class="material-symbols-outlined text-xl">send</span>
                </button>
            </form>
            <p class="text-[8px] text-slate-700 text-center mt-4 font-mono uppercase tracking-widest">
                Authorized access only // CyberShield Secure Relay
            </p>
        </div>
    </div>
</div>

<style>
    #ai-chatbot-container .shadow-glow {
        box-shadow: 0 0 15px -2px rgba(160, 240, 0, 0.3);
    }
    #ai-chatbot-container .custom-scrollbar::-webkit-scrollbar {
        width: 3px;
    }
    #ai-chatbot-container .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }
    #ai-chatbot-container .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(160, 240, 0, 0.1);
        border-radius: 10px;
    }
    #chat-window.active {
        transform: scale(1) !important;
        opacity: 1 !important;
        pointer-events: auto !important;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log("CyberShield AI Bot Initialized");
    const toggleBtn = document.getElementById('chatbot-toggle');
    const chatWindow = document.getElementById('chat-window');
    const closeBtn = document.getElementById('close-chat');
    const chatForm = document.getElementById('chat-form');
    const chatInput = document.getElementById('chat-input');
    const messagesArea = document.getElementById('chat-messages');

    if (!toggleBtn || !chatWindow) {
        console.error("Chatbot elements not found");
        return;
    }

    toggleBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        console.log("Toggle clicked");
        chatWindow.classList.toggle('active');
        if(chatWindow.classList.contains('active')) {
            chatInput.focus();
        }
    });

    window.addEventListener('click', (e) => {
        if (!chatWindow.contains(e.target) && !toggleBtn.contains(e.target)) {
            chatWindow.classList.remove('active');
        }
    });

    closeBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        chatWindow.classList.remove('active');
    });

    chatForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const msg = chatInput.value.trim();
        if(!msg) return;

        // Add User Message
        appendMessage('user', msg);
        chatInput.value = '';

        // Add "Thinking" indicator
        const thinkingId = 'thinking-' + Date.now();
        appendThinking(thinkingId);

        // SCRIPT-BASED ASSISTANT LOGIC
        setTimeout(() => {
            const thinkingEl = document.getElementById(thinkingId);
            if (thinkingEl) thinkingEl.remove();
            
            const reply = getLocalResponse(msg);
            appendMessage('ai', reply);
        }, 800 + Math.random() * 1000);
    });

    const assistantData = {
        greetings: {
            keywords: ["hi", "hello", "hey", "greetings", "good morning", "good afternoon", "good evening"],
            response: "Hello! I'm Luna, your CyberShield assistant. I'm here to help you navigate the platform and master your security labs. How can I assist you today?"
        },
        dashboard: {
            keywords: ["dashboard", "home", "main page", "overview"],
            response: "The Dashboard is your mission control. It shows your active labs, recent security events, and progress metrics. You can access it anytime by clicking 'Dashboard' in the navigation bar."
        },
        labs: {
            keywords: ["labs", "training", "exercises", "scenarios", "practice"],
            response: "CyberShield offers several interactive labs: \n1. **Phishing Simulation**: Practice identifying and creating phishing campaigns.\n2. **Brute Force Lab**: Learn how SSH attacks work and how to mitigate them.\n3. **Malware Analysis**: Investigate suspicious samples in a safe environment.\n4. **SOC Lab**: Monitor alerts and respond to simulated threats in real-time."
        },
        auth: {
            keywords: ["login", "register", "account", "signup", "password"],
            response: "You can manage your account through the 'Profile' section. If you're not logged in, use the 'Login' or 'Register' links at the top. Remember to use a strong password for your CyberShield operator profile!"
        },
        progress: {
            keywords: ["progress", "stats", "rank", "score", "performance"],
            response: "Your progress is tracked automatically as you complete lab tasks. Check the 'Analytics' or 'Progress' tab in your dashboard to see your performance metrics and ranking."
        },
        settings: {
            keywords: ["settings", "profile", "customize", "edit profile"],
            response: "In the 'Settings' area, you can update your personal details, change your password, and customize your operator profile image."
        },
        tools: {
            keywords: ["tools", "software", "utilities", "scripts"],
            response: "Each lab provides its own set of professional tools, including log analyzers, network scanners, and forensic utilities, accessible through the lab interface."
        },
        troubleshooting: {
            keywords: ["help", "error", "broken", "not working", "stuck"],
            response: "If you're stuck, first check the Lab Documentation provided in each module. If you encounter a system error, please submit a query through the 'Help Desk' section, and an admin will assist you."
        },
        cybersecurity: {
            keywords: ["cybersecurity", "security", "hacking", "defense", "protection"],
            response: "CyberShield is dedicated to teaching defensive security. Our focus is on monitoring, analysis, and threat mitigation to build robust digital defenses."
        },
        navigation: {
            keywords: ["navigate", "where is", "how to find", "menu"],
            response: "Use the sidebar or top navigation menu to switch between the Dashboard, Labs, Help Desk, and your Profile. Everything is just one click away!"
        },
        fallback: "I'm not quite sure about that. Try asking about 'Labs', 'Dashboard', or 'Settings', or visit the Help Desk for more specific assistance."
    };

    function getLocalResponse(message) {
        const msg = message.toLowerCase();
        for (const key in assistantData) {
            if (key === "fallback") continue;
            const category = assistantData[key];
            if (category.keywords.some(keyword => msg.includes(keyword))) {
                return category.response;
            }
        }
        return assistantData.fallback;
    }

    function appendMessage(type, text) {
        const div = document.createElement('div');
        div.className = 'flex gap-3 ' + (type === 'user' ? 'flex-row-reverse' : '');
        
        const icon = type === 'ai' ? 'face' : 'person';
        const colorClass = type === 'ai' ? 'primary' : 'secondary';
        
        div.innerHTML = `
            <div class="size-8 rounded-lg bg-${colorClass}/10 flex items-center justify-center text-${colorClass} shrink-0">
                <span class="material-symbols-outlined text-sm font-black">${icon}</span>
            </div>
            <div class="bg-surface-light/50 border border-border-dim p-4 rounded-2xl ${type === 'ai' ? 'rounded-tl-none' : 'rounded-tr-none'} max-w-[85%] shadow-xl">
                <p class="text-[11px] text-slate-300 leading-relaxed">
                    ${text}
                </p>
                <p class="text-[8px] text-slate-600 font-mono mt-2 uppercase tracking-widest">
                    ${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                </p>
            </div>
        `;
        messagesArea.appendChild(div);
        messagesArea.scrollTop = messagesArea.scrollHeight;
    }

    function appendThinking(id) {
        const div = document.createElement('div');
        div.id = id;
        div.className = 'flex gap-3';
        div.innerHTML = `
            <div class="size-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary shrink-0">
                <span class="material-symbols-outlined text-sm font-black animate-spin">sync</span>
            </div>
            <div class="bg-primary/5 border border-primary/10 p-4 rounded-2xl rounded-tl-none">
                <div class="flex gap-1">
                    <div class="size-1 bg-primary/40 rounded-full animate-bounce"></div>
                    <div class="size-1 bg-primary/40 rounded-full animate-bounce [animation-delay:-0.15s]"></div>
                    <div class="size-1 bg-primary/40 rounded-full animate-bounce [animation-delay:-0.3s]"></div>
                </div>
            </div>
        `;
        messagesArea.appendChild(div);
        messagesArea.scrollTop = messagesArea.scrollHeight;
    }
});
</script>
