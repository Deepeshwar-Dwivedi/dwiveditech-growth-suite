<?php
/**
 * Review Manager Module - Dwivedi Tech Growth Suite
 * Send review requests and manage customer feedback
 * Made with ❤️ by Dwivedi Tech
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();
$userId = getCurrentUserId();
$userData = getUserData($userId);

// Get user's review campaigns
$pdo = getPDOConnection();
$stmt = $pdo->prepare("SELECT * FROM review_campaigns WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$campaigns = $stmt->fetchAll();

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campaign_name = sanitize($_POST['campaign_name'] ?? '');
    $customer_name = sanitize($_POST['customer_name'] ?? '');
    $customer_email = sanitize($_POST['customer_email'] ?? '');
    $customer_phone = sanitize($_POST['customer_phone'] ?? '');
    $google_review_url = sanitize($_POST['google_review_url'] ?? '');
    
    if (empty($customer_name) || empty($customer_email)) {
        $error = 'Customer name and email are required';
    } else {
        try {
            $unique_token = generateToken();
            $stmt = $pdo->prepare("
                INSERT INTO review_campaigns (user_id, campaign_name, customer_name, customer_contact, customer_email, customer_phone, unique_token, google_review_url)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $userId,
                $campaign_name,
                $customer_name,
                $customer_email,
                $customer_email,
                $customer_phone,
                $unique_token,
                $google_review_url
            ]);
            
            $message = 'Review request sent successfully!';
            $_POST = array(); // Clear form
            
            // Refresh campaigns
            $stmt = $pdo->prepare("SELECT * FROM review_campaigns WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->execute([$userId]);
            $campaigns = $stmt->fetchAll();
        } catch (Exception $e) {
            $error = 'Error sending review request: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Manager - Dwivedi Tech Growth Suite</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .dark-mode {
            background: linear-gradient(135deg, #0f0f1e 0%, #1a1a2e 100%);
            color: #e0e0e0;
        }
        .sidebar-active {
            background: rgba(102, 126, 234, 0.2);
            border-left: 3px solid #667eea;
        }
    </style>
</head>
<body class="dark-mode">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-gray-900/50 border-r border-gray-700 fixed h-full overflow-y-auto">
            <div class="p-6">
                <div class="flex items-center gap-2 mb-8">
                    <i class="fas fa-rocket text-2xl"></i>
                    <span class="font-bold text-lg">Dwivedi Tech</span>
                </div>
                <nav class="space-y-2">
                    <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-400 hover:bg-gray-800/50 transition">
                        <i class="fas fa-chart-line"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="leads.php" class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-400 hover:bg-gray-800/50 transition">
                        <i class="fas fa-search"></i>
                        <span>Lead Extractor</span>
                    </a>
                    <a href="reviews.php" class="flex items-center gap-3 px-4 py-3 rounded-lg sidebar-active">
                        <i class="fas fa-star"></i>
                        <span>Review Manager</span>
                    </a>
                    <a href="invoices.php" class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-400 hover:bg-gray-800/50 transition">
                        <i class="fas fa-file-invoice-dollar"></i>
                        <span>Invoice Manager</span>
                    </a>
                </nav>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 ml-64 flex flex-col">
            <!-- Top Bar -->
            <header class="bg-gray-900/50 border-b border-gray-700 px-8 py-4 flex justify-between items-center">
                <h1 class="text-2xl font-bold">Review Manager</h1>
                <a href="logout.php" class="text-gray-400 hover:text-white transition">
                    <i class="fas fa-sign-out-alt text-xl"></i>
                </a>
            </header>

            <!-- Page Content -->
            <div class="flex-1 overflow-y-auto p-8">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Send Review Request Form -->
                    <div class="lg:col-span-1 p-6 rounded-xl border border-gray-700 bg-gray-900/50 h-fit sticky top-8">
                        <h2 class="text-xl font-bold mb-6">Send Review Request</h2>

                        <?php if (!empty($message)): ?>
                            <div class="bg-green-500/20 border border-green-500 rounded-lg p-3 mb-6 text-green-300 text-sm">
                                ✓ <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($error)): ?>
                            <div class="bg-red-500/20 border border-red-500 rounded-lg p-3 mb-6 text-red-300 text-sm">
                                ✗ <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="space-y-4">
                            <!-- Campaign Name -->
                            <div>
                                <label class="block text-sm font-medium mb-1">Campaign Name</label>
                                <input 
                                    type="text" 
                                    name="campaign_name"
                                    placeholder="e.g., Q4 Review Push"
                                    class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-purple-500 transition text-sm"
                                >
                            </div>

                            <!-- Customer Name -->
                            <div>
                                <label class="block text-sm font-medium mb-1">Customer Name *</label>
                                <input 
                                    type="text" 
                                    name="customer_name"
                                    placeholder="John Doe"
                                    class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-purple-500 transition text-sm"
                                    required
                                >
                            </div>

                            <!-- Customer Email -->
                            <div>
                                <label class="block text-sm font-medium mb-1">Email Address *</label>
                                <input 
                                    type="email" 
                                    name="customer_email"
                                    placeholder="customer@email.com"
                                    class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-purple-500 transition text-sm"
                                    required
                                >
                            </div>

                            <!-- Customer Phone -->
                            <div>
                                <label class="block text-sm font-medium mb-1">Phone Number</label>
                                <input 
                                    type="tel" 
                                    name="customer_phone"
                                    placeholder="+1 (555) 123-4567"
                                    class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-purple-500 transition text-sm"
                                >
                            </div>

                            <!-- Google Review URL -->
                            <div>
                                <label class="block text-sm font-medium mb-1">Google Review URL</label>
                                <input 
                                    type="url" 
                                    name="google_review_url"
                                    placeholder="https://google.com/reviews/..."
                                    class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-purple-500 transition text-sm"
                                >
                            </div>

                            <!-- Submit Button -->
                            <button 
                                type="submit"
                                class="w-full py-2 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg font-semibold hover:shadow-lg transition text-sm"
                            >
                                <i class="fas fa-paper-plane mr-2"></i> Send Request
                            </button>
                        </form>
                    </div>

                    <!-- Recent Review Campaigns -->
                    <div class="lg:col-span-2 p-6 rounded-xl border border-gray-700 bg-gray-900/50">
                        <h2 class="text-xl font-bold mb-6">Review Campaigns</h2>

                        <?php if (!empty($campaigns)): ?>
                            <div class="space-y-4">
                                <?php foreach ($campaigns as $campaign): ?>
                                    <div class="p-4 rounded-lg border border-gray-700 bg-gray-800/50 hover:bg-gray-800 transition">
                                        <div class="flex justify-between items-start mb-3">
                                            <div>
                                                <h4 class="font-semibold"><?php echo htmlspecialchars($campaign['customer_name']); ?></h4>
                                                <p class="text-sm text-gray-400"><?php echo htmlspecialchars($campaign['campaign_name'] ?? 'General Review'); ?></p>
                                            </div>
                                            <span class="px-3 py-1 rounded-full text-xs font-medium <?php
                                                echo $campaign['status'] === 'submitted' ? 'bg-green-500/20 text-green-300' :
                                                    ($campaign['status'] === 'opened' ? 'bg-blue-500/20 text-blue-300' :
                                                    'bg-yellow-500/20 text-yellow-300');
                                            ?>">
                                                <?php echo ucfirst($campaign['status']); ?>
                                            </span>
                                        </div>

                                        <div class="grid grid-cols-2 gap-4 text-sm mb-3">
                                            <div>
                                                <p class="text-gray-400 text-xs">Email</p>
                                                <p class="text-white break-all"><?php echo htmlspecialchars($campaign['customer_email']); ?></p>
                                            </div>
                                            <div>
                                                <p class="text-gray-400 text-xs">Rating</p>
                                                <?php if (!empty($campaign['rating_given'])): ?>
                                                    <p class="text-yellow-400">
                                                        <?php echo str_repeat('⭐', $campaign['rating_given']); ?>
                                                        <span class="text-white">(<?php echo $campaign['rating_given']; ?}/5)</span>
                                                    </p>
                                                <?php else: ?>
                                                    <p class="text-gray-500">Not yet rated</p>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <?php if (!empty($campaign['feedback_text'])): ?>
                                            <div class="p-3 rounded bg-gray-900/50 border border-gray-700 text-sm mb-3">
                                                <p class="text-gray-400 text-xs mb-1">Feedback:</p>
                                                <p class="text-white"><?php echo htmlspecialchars($campaign['feedback_text']); ?></p>
                                            </div>
                                        <?php endif; ?>

                                        <div class="flex items-center justify-between text-xs text-gray-500">
                                            <span>Sent: <?php echo formatDate($campaign['sent_date'], 'M d, Y'); ?></span>
                                            <button onclick="copyFeedbackLink('<?php echo htmlspecialchars($campaign['unique_token']); ?>')" class="text-purple-400 hover:text-purple-300">
                                                <i class="fas fa-copy mr-1"></i> Copy Link
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-12">
                                <i class="fas fa-inbox text-4xl text-gray-700 mb-4"></i>
                                <p class="text-gray-400">No review campaigns yet. Start by filling the form on the left.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <footer class="border-t border-gray-700 py-4 px-8 text-center text-gray-500 text-sm">
                <p>Made with ❤️ by Dwivedi Tech</p>
            </footer>
        </main>
    </div>

    <script>
        function copyFeedbackLink(token) {
            const feedbackUrl = '<?php echo APP_URL; ?>/feedback.php?id=' + token;
            navigator.clipboard.writeText(feedbackUrl).then(() => {
                alert('Feedback link copied to clipboard!');
            });
        }
    </script>
</body>
</html>
