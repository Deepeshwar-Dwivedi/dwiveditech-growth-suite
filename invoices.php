<?php
/**
 * Invoice Manager Module - Dwivedi Tech Growth Suite
 * Create, track, and manage client invoices with Stripe integration
 * Made with ❤️ by Dwivedi Tech
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();
$userId = getCurrentUserId();
$userData = getUserData($userId);

// Get all invoices for user
$pdo = getPDOConnection();
$stmt = $pdo->prepare("
    SELECT * FROM invoices 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$userId]);
$invoices = $stmt->fetchAll();

// Calculate summary stats
$totalRevenue = 0;
$pendingAmount = 0;
foreach ($invoices as $invoice) {
    if ($invoice['status'] === 'paid') {
        $totalRevenue += $invoice['amount'];
    } elseif ($invoice['status'] === 'unpaid') {
        $pendingAmount += $invoice['amount'];
    }
}

$message = '';
$error = '';

// Handle invoice creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $invoice_number = sanitize($_POST['invoice_number'] ?? '');
    $client_name = sanitize($_POST['client_name'] ?? '');
    $client_email = sanitize($_POST['client_email'] ?? '');
    $amount = floatval($_POST['amount'] ?? 0);
    $due_date = sanitize($_POST['due_date'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    
    if (empty($invoice_number) || empty($client_name) || $amount <= 0) {
        $error = 'Invoice number, client name, and amount are required';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO invoices (user_id, invoice_number, client_name, client_email, amount, due_date, description)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $userId,
                $invoice_number,
                $client_name,
                $client_email,
                $amount,
                $due_date,
                $description
            ]);
            
            $message = 'Invoice created successfully!';
            $_POST = array();
            
            // Refresh invoices
            $stmt = $pdo->prepare("
                SELECT * FROM invoices 
                WHERE user_id = ? 
                ORDER BY created_at DESC
            ");
            $stmt->execute([$userId]);
            $invoices = $stmt->fetchAll();
            
        } catch (Exception $e) {
            $error = 'Error creating invoice: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Manager - Dwivedi Tech Growth Suite</title>
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
        .modal {
            display: none;
            position: fixed;
            z-index: 50;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
        }
        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
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
                    <a href="reviews.php" class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-400 hover:bg-gray-800/50 transition">
                        <i class="fas fa-star"></i>
                        <span>Review Manager</span>
                    </a>
                    <a href="invoices.php" class="flex items-center gap-3 px-4 py-3 rounded-lg sidebar-active">
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
                <h1 class="text-2xl font-bold">Invoice Manager</h1>
                <div class="flex items-center gap-4">
                    <button onclick="openModal('createInvoiceModal')" class="px-4 py-2 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg hover:shadow-lg transition text-sm">
                        <i class="fas fa-plus mr-2"></i> Create Invoice
                    </button>
                    <a href="logout.php" class="text-gray-400 hover:text-white transition">
                        <i class="fas fa-sign-out-alt text-xl"></i>
                    </a>
                </div>
            </header>

            <!-- Page Content -->
            <div class="flex-1 overflow-y-auto p-8">
                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="p-6 rounded-xl border border-gray-700 bg-gray-900/50">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-400 text-sm mb-2">Total Revenue</p>
                                <p class="text-3xl font-bold">$<?php echo number_format($totalRevenue, 2); ?></p>
                            </div>
                            <i class="fas fa-dollar-sign text-4xl text-green-500/30"></i>
                        </div>
                    </div>

                    <div class="p-6 rounded-xl border border-gray-700 bg-gray-900/50">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-400 text-sm mb-2">Pending Amount</p>
                                <p class="text-3xl font-bold">$<?php echo number_format($pendingAmount, 2); ?></p>
                            </div>
                            <i class="fas fa-clock text-4xl text-yellow-500/30"></i>
                        </div>
                    </div>

                    <div class="p-6 rounded-xl border border-gray-700 bg-gray-900/50">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-400 text-sm mb-2">Total Invoices</p>
                                <p class="text-3xl font-bold"><?php echo count($invoices); ?></p>
                            </div>
                            <i class="fas fa-file-alt text-4xl text-purple-500/30"></i>
                        </div>
                    </div>
                </div>

                <!-- Messages -->
                <?php if (!empty($message)): ?>
                    <div class="bg-green-500/20 border border-green-500 rounded-lg p-4 mb-6 text-green-300 text-sm">
                        ✓ <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div class="bg-red-500/20 border border-red-500 rounded-lg p-4 mb-6 text-red-300 text-sm">
                        ✗ <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <!-- Invoices Table -->
                <div class="p-6 rounded-xl border border-gray-700 bg-gray-900/50">
                    <h2 class="text-xl font-bold mb-6">Recent Invoices</h2>

                    <?php if (!empty($invoices)): ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="border-b border-gray-700">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-gray-400">Invoice #</th>
                                        <th class="px-4 py-3 text-left text-gray-400">Client</th>
                                        <th class="px-4 py-3 text-left text-gray-400">Amount</th>
                                        <th class="px-4 py-3 text-left text-gray-400">Due Date</th>
                                        <th class="px-4 py-3 text-left text-gray-400">Status</th>
                                        <th class="px-4 py-3 text-left text-gray-400">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($invoices as $invoice): ?>
                                        <tr class="border-b border-gray-700 hover:bg-gray-800/30 transition">
                                            <td class="px-4 py-3 font-mono text-purple-400"><?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
                                            <td class="px-4 py-3">
                                                <div>
                                                    <p class="font-medium"><?php echo htmlspecialchars($invoice['client_name']); ?></p>
                                                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($invoice['client_email']); ?></p>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 font-semibold">$<?php echo number_format($invoice['amount'], 2); ?></td>
                                            <td class="px-4 py-3 text-gray-400"><?php echo formatDate($invoice['due_date'], 'M d, Y'); ?></td>
                                            <td class="px-4 py-3">
                                                <span class="px-3 py-1 rounded-full text-xs font-medium <?php
                                                    echo $invoice['status'] === 'paid' ? 'bg-green-500/20 text-green-300' :
                                                        ($invoice['status'] === 'overdue' ? 'bg-red-500/20 text-red-300' :
                                                        'bg-yellow-500/20 text-yellow-300');
                                                ?>">
                                                    <?php echo ucfirst($invoice['status']); ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <button class="text-purple-400 hover:text-purple-300 text-sm">
                                                    <i class="fas fa-download mr-1"></i> PDF
                                                </button>
                                                <?php if ($invoice['status'] !== 'paid'): ?>
                                                    <button class="text-blue-400 hover:text-blue-300 text-sm ml-3">
                                                        <i class="fas fa-credit-card mr-1"></i> Pay
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-12">
                            <i class="fas fa-inbox text-4xl text-gray-700 mb-4"></i>
                            <p class="text-gray-400">No invoices yet. Create one to get started!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Footer -->
            <footer class="border-t border-gray-700 py-4 px-8 text-center text-gray-500 text-sm">
                <p>Made with ❤️ by Dwivedi Tech</p>
            </footer>
        </main>
    </div>

    <!-- Create Invoice Modal -->
    <div id="createInvoiceModal" class="modal">
        <div class="bg-gray-900 border border-gray-700 rounded-xl p-8 max-w-md w-full mx-4">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold">Create New Invoice</h2>
                <button onclick="closeModal('createInvoiceModal')" class="text-gray-400 hover:text-white">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="create">

                <div>
                    <label class="block text-sm font-medium mb-1">Invoice Number *</label>
                    <input 
                        type="text" 
                        name="invoice_number"
                        placeholder="INV-001"
                        class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-purple-500 transition text-sm"
                        required
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Client Name *</label>
                    <input 
                        type="text" 
                        name="client_name"
                        placeholder="John Doe"
                        class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-purple-500 transition text-sm"
                        required
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Client Email *</label>
                    <input 
                        type="email" 
                        name="client_email"
                        placeholder="client@email.com"
                        class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-purple-500 transition text-sm"
                        required
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Amount (USD) *</label>
                    <input 
                        type="number" 
                        name="amount"
                        placeholder="1500.00"
                        step="0.01"
                        class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-purple-500 transition text-sm"
                        required
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Due Date</label>
                    <input 
                        type="date" 
                        name="due_date"
                        class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-purple-500 transition text-sm"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Description</label>
                    <textarea 
                        name="description"
                        placeholder="Invoice details..."
                        rows="3"
                        class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-purple-500 transition text-sm"
                    ></textarea>
                </div>

                <div class="flex gap-3 pt-4">
                    <button 
                        type="button"
                        onclick="closeModal('createInvoiceModal')"
                        class="flex-1 py-2 border border-gray-700 rounded-lg hover:bg-gray-800 transition text-sm"
                    >
                        Cancel
                    </button>
                    <button 
                        type="submit"
                        class="flex-1 py-2 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg hover:shadow-lg transition text-sm"
                    >
                        Create Invoice
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>
