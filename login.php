<?php
/**
 * Login Page - Dwivedi Tech Growth Suite
 * Secure user authentication with email and password
 * Made with ❤️ by Dwivedi Tech
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

if (isset($_GET['expired'])) {
    $error = 'Your session has expired. Please log in again.';
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Email and password are required';
    } else {
        $pdo = getPDOConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && verifyPassword($password, $user['password'])) {
            loginUser($user['id'], $user['name']);
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid email or password';
            logToFile("Failed login attempt for email: $email", 'WARNING');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Dwivedi Tech Growth Suite</title>
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
        .glow-badge {
            box-shadow: 0 0 30px rgba(102, 126, 234, 0.6);
        }
    </style>
</head>
<body class="dark-mode">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            <!-- Logo -->
            <div class="text-center mb-8">
                <div class="flex items-center justify-center gap-2 mb-4">
                    <i class="fas fa-rocket text-4xl gradient-text"></i>
                    <span class="text-3xl font-bold">Dwivedi Tech</span>
                </div>
                <div class="inline-block px-3 py-1 rounded-full bg-gradient-to-r from-purple-600 to-pink-600 text-xs font-semibold glow-badge">
                    Premium SaaS Platform
                </div>
            </div>

            <!-- Login Form -->
            <div class="p-8 rounded-xl border border-gray-700 bg-gray-900/50 backdrop-blur-sm">
                <h1 class="text-2xl font-bold mb-6 text-center">Welcome Back</h1>

                <?php if (!empty($error)): ?>
                    <div class="bg-red-500/20 border border-red-500 rounded-lg p-4 mb-6 text-red-300 text-sm">
                        <i class="fas fa-exclamation-circle mr-2"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-4">
                    <!-- Email Input -->
                    <div>
                        <label class="block text-sm font-medium mb-2">Email Address</label>
                        <div class="relative">
                            <i class="fas fa-envelope absolute left-3 top-3 text-gray-500"></i>
                            <input 
                                type="email" 
                                name="email"
                                placeholder="you@example.com"
                                value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                class="w-full pl-10 pr-4 py-2 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-purple-500 transition"
                                required
                            >
                        </div>
                    </div>

                    <!-- Password Input -->
                    <div>
                        <label class="block text-sm font-medium mb-2">Password</label>
                        <div class="relative">
                            <i class="fas fa-lock absolute left-3 top-3 text-gray-500"></i>
                            <input 
                                type="password" 
                                name="password"
                                placeholder="••••••••"
                                class="w-full pl-10 pr-4 py-2 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-purple-500 transition"
                                required
                            >
                        </div>
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center">
                        <input type="checkbox" id="remember" class="w-4 h-4">
                        <label for="remember" class="ml-2 text-sm text-gray-400">Remember me</label>
                    </div>

                    <!-- Login Button -->
                    <button 
                        type="submit"
                        class="w-full py-2 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg font-semibold hover:shadow-lg transition"
                    >
                        <i class="fas fa-sign-in-alt mr-2"></i> Sign In
                    </button>
                </form>

                <!-- Divider -->
                <div class="my-6 flex items-center">
                    <div class="flex-1 border-t border-gray-700"></div>
                    <span class="px-3 text-gray-500 text-sm">New here?</span>
                    <div class="flex-1 border-t border-gray-700"></div>
                </div>

                <!-- Sign Up Link -->
                <a href="signup.php" class="block w-full py-2 border border-gray-700 text-center rounded-lg hover:bg-gray-800 transition">
                    <i class="fas fa-user-plus mr-2"></i> Create Account
                </a>

                <!-- Demo Credentials -->
                <div class="mt-6 p-4 rounded-lg bg-blue-500/10 border border-blue-500/30">
                    <p class="text-xs text-gray-400 mb-2"><i class="fas fa-info-circle mr-1"></i> Demo Credentials:</p>
                    <p class="text-xs text-gray-300 font-mono">Email: demo@dwiveditech.com</p>
                    <p class="text-xs text-gray-300 font-mono">Password: Demo@12345</p>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center text-gray-500 text-sm mt-8">
                <p>Made with ❤️ by Dwivedi Tech</p>
                <p class="text-xs text-gray-600 mt-2">© 2026 Dwivedi Tech. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
