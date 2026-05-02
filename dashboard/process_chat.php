<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

/** @var mysqli $conn */

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];
    $message = trim($_POST['message'] ?? '');

    if (empty($message)) {
        echo json_encode(['status' => 'error', 'message' => 'Empty message']);
        exit;
    }

    // Fetch User Name for Personalization
    $user_name = "Operator";
    $stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $user_name = explode(' ', trim($row['name']))[0]; 
        }
        $stmt->close();
    }

    // Simple Knowledge Base - Expanded & Personalized
    $knowledge = [
        [
            'keywords' => ['phishing', 'email', 'fake site'],
            'reply' => "Oh, the Phishing lab! 🎣 It's a great place to start. Just head over to 'Phishing Simulation' under the Modules menu. You can set up your own practice campaign and see how it works! Need help with a template?"
        ],
        [
            'keywords' => ['soc', 'forensic', 'investigate', 'alerts'],
            'reply' => "The SOC Lab is where the real action is, $user_name! 🛡️ You can look at security logs and help stop 'attacks' on the system. It's like being a digital detective! Just check your Active Alerts to begin."
        ],
        [
            'keywords' => ['ddos', 'flood', 'denial of service', 'syn flood'],
            'reply' => "Dealing with a DDoS attack? 🌪️ Don't worry, $user_name! In that lab, you'll learn how to identify those massive traffic floods and keep our systems running smoothly. It's all about balance!"
        ],
        [
            'keywords' => ['malware', 'virus', 'trojan', 'ransomware', 'spyware'],
            'reply' => "Malware analysis is super interesting! 🔍 In our lab, you get to safely look at how suspicious files behave without any risk to your computer. It's very educational!"
        ],
        [
            'keywords' => ['brute', 'password guessing', 'dictionary attack'],
            'reply' => "Brute force attacks are like someone trying every key in a lock. 🔑 In this lab, you'll see how we detect those repeated login attempts and keep our users safe."
        ],
        [
            'keywords' => ['settings', 'profile', 'change name', 'update picture', 'account'],
            'reply' => "Looking for your settings, $user_name? ⚙️ Just click on 'Profile Node' in the sidebar! You can change your name, update your picture, or even set up extra security for your account."
        ],
        [
            'keywords' => ['hello', 'hi', 'hey', 'greetings'],
            'reply' => "Hi $user_name! I'm Luna! 😊 I'm so happy to see you here. How can I help make your security journey easier today?"
        ],
        [
            'keywords' => ['how are you', 'how\'s it going'],
            'reply' => "I'm doing wonderful, thank you for asking! 🌸 I'm just here and ready to help you secure the world. How are you doing today?"
        ],
        [
            'keywords' => ['who are you', 'what are you', 'your name'],
            'reply' => "I'm Luna, your personal CyberShield AI Assistant! 🤖 I'm here to guide you through our labs and help you with any platform questions."
        ],
        [
            'keywords' => ['creator', 'created', 'who made', 'author', 'developed'],
            'reply' => "I was created by the brilliant team behind CyberShield! 🚀 They wanted to make sure you have a friendly face to help you on your security path."
        ],
        [
            'keywords' => ['mfa', 'multifactor', 'multi-factor', '2fa', 'two factor', 'verification'],
            'reply' => "Multi-Factor Authentication (MFA) is like having a second lock on your door. 📱 It's a great idea to turn it on! You can find it in your Profile Settings."
        ],
        [
            'keywords' => ['password', 'passwords', 'strong password', 'secure password'],
            'reply' => "Strong passwords are your first line of defense! 🔐 Try to use at least 12 characters with a mix of numbers and symbols. You can rotate yours in the Settings menu anytime."
        ],
        [
            'keywords' => ['analy', 'dashboard', 'charts', 'data'],
            'reply' => "The Analytics dashboard is your mission control! 📊 It shows you exactly how well your simulations are performing. It's really cool to see the data in real-time!"
        ],
        [
            'keywords' => ['alert', 'alerts', 'notification', 'notifications'],
            'reply' => "Alerts are our way of saying 'Hey, look at this!' 🚨 In the SOC lab, you'll learn how to investigate these alerts and decide if they are real threats or just false alarms."
        ],
        [
            'keywords' => ['secure', 'security', 'safety', 'protect'],
            'reply' => "Staying secure is all about being careful and curious, $user_name! 🛡️ Always double-check links and keep your node identity updated."
        ]
    ];

    $reply = "I'm not quite sure about that one yet, but I'm learning every day! 🌸 Maybe try asking about one of our labs like SOC or Phishing, or even your settings? I can also tell you about MFA or strong passwords!";

    // Match keywords
    $lowercase_msg = strtolower($message);
    foreach ($knowledge as $item) {
        foreach ($item['keywords'] as $keyword) {
            if (strpos($lowercase_msg, $keyword) !== false) {
                $reply = $item['reply'];
                break 2;
            }
        }
    }

    // Log to database
    $stmt = $conn->prepare("INSERT INTO user_queries (user_id, query_text, solution_text, status) VALUES (?, ?, ?, 'resolved')");
    if ($stmt) {
        $stmt->bind_param("iss", $user_id, $message, $reply);
        $stmt->execute();
        $stmt->close();
    }

    echo json_encode([
        'status' => 'success',
        'reply' => $reply,
        'timestamp' => date('H:i')
    ]);

} catch (Exception $e) {
    error_log("Chatbot Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'System error: ' . $e->getMessage()]);
}
