/**
 * CyberShield Assistant Knowledge Base
 * This file contains predefined responses for the script-based assistant.
 */

const assistantData = {
    greetings: {
        keywords: ["hi", "hello", "hey", "greetings", "good morning", "good afternoon", "good evening", "luna"],
        response: "Hello Operator! I'm Luna, your Elite AI security assistant. I'm connected to the CyberShield neural network and ready to guide you through our defensive training modules. How can I help you secure the perimeter today?"
    },
    dashboard: {
        keywords: ["dashboard", "home", "main page", "overview", "status"],
        response: "The **Analyst Dashboard** is your central command center. Here you can monitor the 'Global Threat Monitor' for real-time simulations, check your current 'Security Level', and view the 'Intrusion Feed' for live logs. It's designed to give you a 360-degree view of the network's health."
    },
    phishing_lab: {
        keywords: ["phishing", "email", "spoof", "campaign", "social engineering"],
        response: "In the **Phishing Lab**, you learn to think like an attacker to build better defenses. You can create simulated campaigns by choosing a sender name, spoofing an email address, and crafting a convincing body. The goal is to analyze 'Click Rates' and 'Credential Harvests' to understand user vulnerability."
    },
    brute_force: {
        keywords: ["brute force", "ssh", "hydra", "password attack", "cracking"],
        response: "The **Brute Force Lab** simulates automated credential attacks against an SSH server. You'll witness how attackers use wordlists to guess passwords. Your task is to analyze the 'Brute Force Logs', identify the attacker's IP, and implement mitigation strategies like account lockouts and IP banning."
    },
    malware: {
        keywords: ["malware", "virus", "ransomware", "analysis", "forensics", "sample"],
        response: "In the **Malware Analysis** module, you are presented with suspicious file samples. You must perform static and dynamic analysis (simulated) to determine the 'Sample Type' (e.g., Ransomware, Trojan, Spyware) and provide a final verdict. It's a safe environment to practice digital forensics."
    },
    soc_lab: {
        keywords: ["soc", "siem", "alerts", "monitoring", "incidents", "threat hunting"],
        response: "The **Elite SOC Lab** is our most advanced module. You'll act as a Security Operations Center analyst, triaging 'High' and 'Critical' alerts. You must examine 'Log Evidence', determine the 'Canonical Type' of the threat, and move through phases to successfully mitigate the attack."
    },
    ddos: {
        keywords: ["ddos", "dos", "flood", "syn", "traffic", "botnet"],
        response: "Our **DDoS Defense** simulator allows you to experience various attack types like SYN Floods and UDP amplification. You'll learn to monitor traffic spikes and use the 'Mitigation Toggle' to protect the server's availability during an active assault."
    },
    progress: {
        keywords: ["progress", "stats", "rank", "score", "performance", "my level"],
        response: "Your progress is tracked in real-time. Every successful lab mitigation increases your 'Node Integrity' and overall score. You can see your detailed breakdown in the **Progress Tracking** section of your profile."
    },
    settings: {
        keywords: ["settings", "profile", "customize", "edit", "identity", "image"],
        response: "Access **Node Config** (Settings) to manage your identity. You can synchronize your Node ID, update your regional settings, and upload a custom avatar. We recommend keeping your profile updated for the leaderboard."
    },
    navigation: {
        keywords: ["navigate", "where is", "how to find", "menu", "sidebar", "go to"],
        response: "The navigation sidebar on the left gives you instant access to: \n- **Dashboard**: Global status\n- **Labs**: All training modules\n- **Node Config**: Profile & Settings\n- **Help Desk**: Support queries\n\nSimply click an icon to re-route your session."
    },
    auth: {
        keywords: ["login", "register", "logout", "account", "signup", "access"],
        response: "CyberShield uses encrypted session handling. To secure your data, always 'Terminate' (Logout) your session when leaving your terminal. New operators can join via the 'Register' node on the login page."
    },
    help: {
        keywords: ["help", "stuck", "support", "admin", "query", "question"],
        response: "If you're facing technical issues or have a specific question, navigate to the **Help Desk**. You can submit a 'Support Query' directly to our administrators, and they will provide a solution within your 'Resolved Queries' tab."
    },
    mission: {
        keywords: ["what is", "about", "mission", "purpose", "who are you"],
        response: "CyberShield is an elite cybersecurity training platform designed to bridge the gap between theory and practice. Our mission is to forge the next generation of cyber defenders through immersive, high-fidelity simulations."
    },
    faq: {
        keywords: ["faq", "questions", "common issues"],
        response: "Common questions include: \n- **How do I reset a lab?** Use the 'Reset Node' button within the lab module.\n- **Can I work offline?** Yes! Our new Luna Scripted Assistant is fully offline-capable.\n- **Are the threats real?** No, all simulations are safely sandboxed."
    },
    fallback: "I've scanned my database but couldn't find a direct match for that query. Try asking about 'SOC Lab', 'Phishing', 'DDoS', or 'How to navigate'. I'm here to help!"
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
