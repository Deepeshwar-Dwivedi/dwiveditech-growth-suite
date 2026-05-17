<?php
/**
 * Smart Feedback Router - Dwivedi Tech Growth Suite
 * Routes customer feedback based on rating
 * Made with ❤️ by Dwivedi Tech
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

$token = sanitize($_GET['id'] ?? '');
$campaign = null;
$rating_submitted = false;
$error = '';

if (empty($token)) {
    $error = 'Invalid feedback link';
} else {
    // Get campaign by token
    $pdo = getPDOConnection();
    $stmt = $pdo->prepare("SELECT * FROM review_campaigns WHERE unique_token = ?");
    $stmt->execute([$token]);
    $campaign = $stmt->fetch();
    
    if (!$campaign) {
        $error = 'Feedback link not found or has expired';
    }
}

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($campaign)) {
    $rating = (int)($_POST['rating'] ?? 0);
    $feedback = sanitize($_POST['feedback'] ?? '');
    
    if ($rating < 1 || $rating > 5) {
        $error = 'Please select a valid rating';
    } else {
        try {
            $pdo = getPDOConnection();
            
            // Update campaign with rating
            $stmt = $pdo->prepare("
                UPDATE review_campaigns 
                SET rating_given = ?, feedback_text = ?, status = 'submitted', response_date = NOW()
                WHERE unique_token = ?
            ");
            $stmt->execute([$rating, $feedback, $token]);
            
            $rating_submitted = true;
            $campaign['rating_given'] = $rating;
            $campaign['feedback_text'] = $feedback;
            $campaign['status'] = 'submitted';
            
        } catch (Exception $e) {
            $error = 'Error submitting feedback: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Share Your Feedback - Dwivedi Tech</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .dark-mode {
            background: linear-gradient(135deg, #0f0f1e 0%, #1a1a2e 100%);
            color: #e0e0e0;
        }
        .gradient-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</head>
<body class="dark-mode min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="flex items-center justify-center gap-2 mb-4">
                <i class="fas fa-rocket text-3xl gradient-text"></i>
                <span class="text-2xl font-bold">Dwivedi Tech</span>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <!-- Error State -->
            <div class="p-8 rounded-xl border border-red-500 bg-red-500/10 text-center">
                <i class="fas fa-exclamation-circle text-4xl text-red-400 mb-4"></i>
                <p class="text-red-300"><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php elseif ($rating_submitted): ?>
            <!-- Success State -->
            <div class="p-8 rounded-xl border border-gray-700 bg-gray-900/50 text-center">
                <i class="fas fa-check-circle text-4xl text-green-400 mb-4"></i>
                <h2 class="text-2xl font-bold mb-2">Thank You!</h2>
                
                <?php if ($campaign['rating_given'] >= 4): ?>
                    <p class="text-gray-300 mb-6">Your positive feedback is greatly appreciated! You'll be redirected to leave a review on Google.</p>
                    <button onclick="redirectToGoogle()" class="px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg font-semibold hover:shadow-lg transition w-full">
                        <i class="fas fa-external-link-alt mr-2"></i> Leave Google Review
                    </button>
                <?php else: ?>
                    <p class="text-gray-300 mb-6">Thank you for your honest feedback. We'll work on improving our service!</p>
                    <p class="text-gray-400 text-sm">Your feedback has been recorded and will help us serve you better.</p>
                <?php endif; ?>
            </div>
        <?php elseif (!empty($campaign)): ?>
            <!-- Feedback Form -->
            <div class="p-8 rounded-xl border border-gray-700 bg-gray-900/50">
                <h2 class="text-2xl font-bold mb-2 text-center">Share Your Experience</h2>
                <p class="text-gray-400 text-center text-sm mb-8">Hi <?php echo htmlspecialchars($campaign['customer_name']); ?>, we'd love to hear your feedback!</p>

                <form method="POST" class="space-y-6">
                    <!-- Star Rating -->
                    <div>
                        <p class="text-sm font-medium mb-4 text-center">How would you rate your experience?</p>
                        <div id="ratingContainer" class="flex justify-center gap-3 mb-6">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <button 
                                    type="button" 
                                    onclick="setRating(<?php echo $i; ?>)"
                                    class="rating-star text-4xl transition transform hover:scale-125 cursor-pointer"
                                    data-rating="<?php echo $i; ?>"
                                >
                                    ⭐
                                </button>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" id="rating" name="rating" value="0" required>
                    </div>

                    <!-- Feedback Textarea (Hidden Initially) -->
                    <div id="feedbackBox" class="hidden">
                        <label class="block text-sm font-medium mb-2">Tell us how we can improve:</label>
                        <textarea 
                            name="feedback" 
                            placeholder="Your detailed feedback..."
                            rows="4"
                            class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-purple-500 transition text-sm"
                        ></textarea>
                    </div>

                    <!-- Submit Button -->
                    <button 
                        type="submit"
                        id="submitBtn"
                        class="w-full py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg font-semibold hover:shadow-lg transition disabled:opacity-50 disabled:cursor-not-allowed"
                        disabled
                    >
                        <i class="fas fa-paper-plane mr-2"></i> Submit Feedback
                    </button>
                </form>

                <!-- Footer Note -->
                <p class="text-xs text-gray-500 text-center mt-6">
                    <i class="fas fa-lock mr-1"></i> Your feedback is private and secure
                </p>
            </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="text-center text-gray-500 text-sm mt-8">
            <p>Made with ❤️ by Dwivedi Tech</p>
        </div>
    </div>

    <script>
        let selectedRating = 0;

        function setRating(rating) {
            selectedRating = rating;
            document.getElementById('rating').value = rating;
            document.getElementById('submitBtn').disabled = false;

            // Update star display
            document.querySelectorAll('.rating-star').forEach((star, index) => {
                if (index < rating) {
                    star.style.color = '#fbbf24';
                    star.style.opacity = '1';
                } else {
                    star.style.color = '#6b7280';
                    star.style.opacity = '0.5';
                }
            });

            // Show feedback box only for low ratings (1-3 stars)
            if (rating <= 3) {
                document.getElementById('feedbackBox').classList.remove('hidden');
            } else {
                document.getElementById('feedbackBox').classList.add('hidden');
            }
        }

        function redirectToGoogle() {
            const googleReviewUrl = '<?php echo htmlspecialchars($campaign['google_review_url']); ?>';
            if (googleReviewUrl) {
                window.location.href = googleReviewUrl;
            } else {
                alert('Google review URL not configured');
            }
        }
    </script>
</body>
</html>
