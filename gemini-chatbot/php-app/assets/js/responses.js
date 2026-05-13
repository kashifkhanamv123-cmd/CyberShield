/**
 * CyberShield Assistant Knowledge Base
 * This file contains predefined responses for the script-based assistant.
 */

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

/**
 * Find the best matching response for a given user message
 */
function getAssistantResponse(message) {
    const msg = message.toLowerCase();
    
    // Check each category for keyword matches
    for (const key in assistantData) {
        if (key === "fallback") continue;
        
        const category = assistantData[key];
        if (category.keywords.some(keyword => msg.includes(keyword))) {
            return category.response;
        }
    }
    
    return assistantData.fallback;
}
