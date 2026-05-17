<?php
/**
 * Lead Extractor Module - Dwivedi Tech Growth Suite
 * Extract leads from various sources and manage outreach
 * Made with ❤️ by Dwivedi Tech
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();
$userId = getCurrentUserId();
$userData = getUserData($userId);

// Get all extracted leads for user
$pdo = getPDOConnection();
$stmt = $pdo->prepare("
    SELECT * FROM extracted_leads 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$userId]);
$leads = $stmt->fetchAll();

$message = '';
$error = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lead Extractor - Dwivedi Tech Growth Suite</title>
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
                    <a href="leads.php" class="flex items-center gap-3 px-4 py-3 rounded-lg sidebar-active">
                        <i class="fas fa-search"></i>
                        <span>Lead Extractor</span>
                    </a>
                    <a href="reviews.php" class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-400 hover:bg-gray-800/50 transition">
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
                <h1 class="text-2xl font-bold">Lead Extractor</h1>
                <a href="logout.php" class="text-gray-400 hover:text-white transition">
                    <i class="fas fa-sign-out-alt text-xl"></i>
                </a>
            </header>

            <!-- Page Content -->
            <div class="flex-1 overflow-y-auto p-8">
                <!-- Search Form -->
                <div class="p-6 rounded-xl border border-gray-700 bg-gray-900/50 mb-8">
                    <h2 class="text-xl font-bold mb-6">Search Leads</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Keyword Input -->
                        <div>
                            <label class="block text-sm font-medium mb-2">Keyword</label>
                            <input 
                                type="text" 
                                id="keyword"
                                placeholder="e.g., Dentists, Restaurants"
                                class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-purple-500 transition"
                            >
                        </div>

                        <!-- Location Input -->
                        <div>
                            <label class="block text-sm font-medium mb-2">Location</label>
                            <input 
                                type="text" 
                                id="location"
                                placeholder="e.g., New York, NY"
                                class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-purple-500 transition"
                            >
                        </div>

                        <!-- Platform Source -->
                        <div>
                            <label class="block text-sm font-medium mb-2">Platform</label>
                            <select id="platform" class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-purple-500 transition">
                                <option value="google_maps">Google Maps</option>
                                <option value="instagram">Instagram</option>
                                <option value="linkedin">LinkedIn</option>
                            </select>
                        </div>

                        <!-- Search Button -->
                        <div class="flex items-end">
                            <button 
                                onclick="extractLeads()"
                                class="w-full px-4 py-2 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg hover:shadow-lg transition font-semibold"
                            >
                                <i class="fas fa-search mr-2"></i> Search
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Messages -->
                <div id="messageContainer"></div>

                <!-- Leads Table -->
                <div class="p-6 rounded-xl border border-gray-700 bg-gray-900/50">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold">Extracted Leads</h2>
                        <?php if (!empty($leads)): ?>
                            <button onclick="exportToCSV()" class="text-purple-400 hover:text-purple-300 text-sm">
                                <i class="fas fa-download mr-1"></i> Export CSV
                            </button>
                        <?php endif; ?>
                    </div>

                    <div id="leadsTableContainer">
                        <?php if (!empty($leads)): ?>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead class="border-b border-gray-700">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-gray-400">Business Name</th>
                                            <th class="px-4 py-3 text-left text-gray-400">Email</th>
                                            <th class="px-4 py-3 text-left text-gray-400">Phone</th>
                                            <th class="px-4 py-3 text-left text-gray-400">Website</th>
                                            <th class="px-4 py-3 text-left text-gray-400">Source</th>
                                            <th class="px-4 py-3 text-left text-gray-400">Status</th>
                                            <th class="px-4 py-3 text-left text-gray-400">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($leads as $lead): ?>
                                            <tr class="border-b border-gray-700 hover:bg-gray-800/30 transition">
                                                <td class="px-4 py-3 font-medium"><?php echo htmlspecialchars($lead['business_name']); ?></td>
                                                <td class="px-4 py-3 text-gray-400 break-all"><?php echo htmlspecialchars($lead['email']); ?></td>
                                                <td class="px-4 py-3 text-gray-400"><?php echo htmlspecialchars($lead['phone']); ?></td>
                                                <td class="px-4 py-3">
                                                    <?php if (!empty($lead['website'])): ?>
                                                        <a href="<?php echo htmlspecialchars($lead['website']); ?>" target="_blank" class="text-purple-400 hover:text-purple-300 text-xs">
                                                            <i class="fas fa-external-link-alt mr-1"></i> Visit
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-gray-500 text-xs">N/A</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <span class="px-2 py-1 rounded text-xs font-medium bg-blue-500/20 text-blue-300">
                                                        <?php echo ucwords(str_replace('_', ' ', $lead['platform_source'])); ?>
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <span class="px-2 py-1 rounded-full text-xs font-medium <?php
                                                        echo $lead['outreach_status'] === 'sent' ? 'bg-green-500/20 text-green-300' :
                                                            ($lead['outreach_status'] === 'failed' ? 'bg-red-500/20 text-red-300' :
                                                            'bg-yellow-500/20 text-yellow-300');
                                                    ?>">
                                                        <?php echo ucfirst($lead['outreach_status']); ?>
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-sm space-x-2">
                                                    <button onclick="sendEmail(<?php echo $lead['id']; ?>)" class="text-blue-400 hover:text-blue-300">
                                                        <i class="fas fa-envelope mr-1"></i> Email
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-12">
                                <i class="fas fa-search text-4xl text-gray-700 mb-4"></i>
                                <p class="text-gray-400">No leads extracted yet. Use the search form above to get started!</p>
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
        function extractLeads() {
            const keyword = document.getElementById('keyword').value;
            const location = document.getElementById('location').value;
            const platform = document.getElementById('platform').value;

            if (!keyword || !location) {
                showMessage('Please enter keyword and location', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('keyword', keyword);
            formData.append('location', location);
            formData.append('platform', platform);

            showMessage('Searching for leads...', 'info');

            fetch('api/extract_leads.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(data.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showMessage(data.message, 'error');
                }
            })
            .catch(error => {
                showMessage('Error: ' + error.message, 'error');
                console.error('Error:', error);
            });
        }

        function sendEmail(leadId) {
            if (!confirm('Send outreach email to this lead?')) return;

            const formData = new FormData();
            formData.append('lead_id', leadId);

            fetch('api/send_email.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(data.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showMessage(data.message, 'error');
                }
            })
            .catch(error => {
                showMessage('Error: ' + error.message, 'error');
            });
        }

        function exportToCSV() {
            window.location.href = 'api/export_leads.php';
        }

        function showMessage(text, type) {
            const container = document.getElementById('messageContainer');
            const bgColor = type === 'success' ? 'bg-green-500/20 border-green-500 text-green-300' :
                          type === 'error' ? 'bg-red-500/20 border-red-500 text-red-300' :
                          'bg-blue-500/20 border-blue-500 text-blue-300';
            
            container.innerHTML = `
                <div class="rounded-lg p-4 mb-6 border ${bgColor} text-sm">
                    ${text}
                </div>
            `;
        }
    </script>
</body>
</html>
