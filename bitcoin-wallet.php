<?php
session_start();
require_once 'config/database.php';
require_once 'classes/BitcoinService.php';

if(!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$bitcoin = new BitcoinService($db);

$wallet = $bitcoin->getUserWallet($_SESSION['user_id']);
$transactions = $bitcoin->getTransactionHistory($_SESSION['user_id'], 20);
$btc_price = $bitcoin->getBitcoinPrice();

include 'views/header.php';
?>

<!-- Hero Section -->
<div class="relative overflow-hidden bg-gradient-to-br from-yellow-600 via-orange-600 to-red-600 py-12">
    <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iMC4wNSI+PHBhdGggZD0iTTM2IDEzNGg3djdjMCAxLjEwNS0uODk1IDItMiAyaC0zYy0xLjEwNSAwLTItLjg5NS0yLTJ2LTd6bTAtMTRoN3Y3YzAgMS4xMDUtLjg5NSAyLTIgMmgtM2MtMS4xMDUgMC0yLS44OTUtMi0ydi03eiIvPjwvZz48L2c+PC9zdmc+')] opacity-10"></div>
    
    <div class="relative mx-auto max-w-6xl px-4">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <div class="mb-2 inline-flex items-center gap-2 rounded-full bg-white/20 px-3 py-1 text-sm font-bold text-white backdrop-blur-sm">
                    <i class="bi bi-currency-bitcoin"></i>
                    Bitcoin Wallet
                </div>
                <h1 class="text-4xl font-bold text-white md:text-5xl">Your Balance</h1>
            </div>
            <div class="text-right">
                <div class="text-sm text-white/70">BTC Price</div>
                <div class="text-2xl font-bold text-white">${<?php echo number_format($btc_price, 2); ?></div>
            </div>
        </div>

        <!-- Balance Display -->
        <div class="mb-6 rounded-2xl border-2 border-white/30 bg-white/10 p-8 backdrop-blur-sm">
            <div class="mb-6 grid gap-6 md:grid-cols-2">
                <div>
                    <div class="mb-2 text-sm text-white/70">Bitcoin Balance</div>
                    <div class="flex items-center gap-3">
                        <i class="bi bi-currency-bitcoin text-4xl text-white"></i>
                        <span class="text-5xl font-bold text-white"><?php echo number_format($wallet['btc_balance'], 8); ?></span>
                        <span class="text-2xl text-white/70">BTC</span>
                    </div>
                </div>
                <div>
                    <div class="mb-2 text-sm text-white/70">USD Value</div>
                    <div class="text-5xl font-bold text-white">
                        $<?php echo number_format($wallet['btc_balance'] * $btc_price, 2); ?>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-wrap gap-3">
                <button class="flex items-center gap-2 rounded-lg bg-white px-6 py-3 font-bold text-orange-600 shadow-lg transition-all hover:brightness-110">
                    <i class="bi bi-download"></i>
                    Deposit
                </button>
                <button class="flex items-center gap-2 rounded-lg border-2 border-white bg-white/20 px-6 py-3 font-bold text-white backdrop-blur-sm transition-all hover:bg-white/30">
                    <i class="bi bi-send-fill"></i>
                    Send
                </button>
                <button class="flex items-center gap-2 rounded-lg border-2 border-white bg-white/20 px-6 py-3 font-bold text-white backdrop-blur-sm transition-all hover:bg-white/30">
                    <i class="bi bi-arrow-repeat"></i>
                    Exchange
                </button>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-lg bg-white/10 p-4 backdrop-blur-sm">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-green-500">
                        <i class="bi bi-arrow-down-circle-fill text-white"></i>
                    </div>
                    <div>
                        <div class="text-xs text-white/70">Total Received</div>
                        <div class="font-bold text-white"><?php echo number_format($wallet['total_received'], 8); ?> BTC</div>
                    </div>
                </div>
            </div>
            <div class="rounded-lg bg-white/10 p-4 backdrop-blur-sm">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-red-500">
                        <i class="bi bi-arrow-up-circle-fill text-white"></i>
                    </div>
                    <div>
                        <div class="text-xs text-white/70">Total Sent</div>
                        <div class="font-bold text-white"><?php echo number_format($wallet['total_sent'], 8); ?> BTC</div>
                    </div>
                </div>
            </div>
            <div class="rounded-lg bg-white/10 p-4 backdrop-blur-sm">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-500">
                        <i class="bi bi-list-check text-white"></i>
                    </div>
                    <div>
                        <div class="text-xs text-white/70">Transactions</div>
                        <div class="font-bold text-white"><?php echo count($transactions); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Transaction History -->
<div class="bg-gh-bg py-12">
    <div class="mx-auto max-w-6xl px-4">
        <div class="mb-6 flex items-center justify-between">
            <h2 class="text-2xl font-bold text-white">
                <i class="bi bi-clock-history mr-2 text-gh-accent"></i>
                Recent Transactions
            </h2>
            <button class="text-sm text-gh-accent transition-colors hover:text-gh-success">
                View All
                <i class="bi bi-arrow-right ml-1"></i>
            </button>
        </div>

        <div class="space-y-3">
            <?php if(empty($transactions)): ?>
            <div class="rounded-lg border border-gh-border bg-gh-panel p-12 text-center">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gh-border text-3xl text-gh-muted">
                    <i class="bi bi-inbox"></i>
                </div>
                <h3 class="mb-2 text-lg font-bold text-white">No Transactions Yet</h3>
                <p class="mb-4 text-sm text-gh-muted">Your transaction history will appear here</p>
                <button class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-pink-600 to-purple-600 px-6 py-2 text-sm font-bold text-white">
                    <i class="bi bi-plus-circle"></i>
                    Make Your First Transaction
                </button>
            </div>
            <?php else: ?>
                <?php foreach($transactions as $tx): ?>
                <div class="group flex items-center gap-4 rounded-lg border border-gh-border bg-gh-panel p-4 transition-all hover:border-gh-accent">
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg 
                        <?php echo $tx['type'] == 'received' ? 'bg-green-500/20 text-green-500' : 'bg-red-500/20 text-red-500'; ?>">
                        <i class="bi <?php echo $tx['type'] == 'received' ? 'bi-arrow-down-circle-fill' : 'bi-arrow-up-circle-fill'; ?> text-xl"></i>
                    </div>
                    
                    <div class="flex-1">
                        <div class="mb-1 flex items-center gap-2">
                            <span class="font-bold text-white"><?php echo ucfirst($tx['type']); ?></span>
                            <?php if($tx['status'] == 'confirmed'): ?>
                            <span class="inline-flex items-center gap-1 rounded-full bg-green-500/20 px-2 py-0.5 text-xs font-semibold text-green-400">
                                <i class="bi bi-check-circle-fill"></i>
                                Confirmed
                            </span>
                            <?php else: ?>
                            <span class="inline-flex items-center gap-1 rounded-full bg-yellow-500/20 px-2 py-0.5 text-xs font-semibold text-yellow-400">
                                <i class="bi bi-hourglass-split"></i>
                                Pending
                            </span>
                            <?php endif; ?>
                        </div>
                        <div class="text-xs text-gh-muted"><?php echo date('M d, Y H:i', strtotime($tx['created_at'])); ?></div>
                    </div>
                    
                    <div class="text-right">
                        <div class="font-bold <?php echo $tx['type'] == 'received' ? 'text-green-400' : 'text-red-400'; ?>">
                            <?php echo $tx['type'] == 'received' ? '+' : '-'; ?><?php echo number_format($tx['amount'], 8); ?> BTC
                        </div>
                        <div class="text-xs text-gh-muted">
                            $<?php echo number_format($tx['amount'] * $btc_price, 2); ?>
                        </div>
                    </div>

                    <i class="bi bi-chevron-right text-gh-muted transition-transform group-hover:translate-x-1"></i>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Security Notice -->
        <div class="mt-8 flex items-start gap-4 rounded-lg border border-blue-500/30 bg-blue-500/10 p-6">
            <i class="bi bi-shield-check text-2xl text-blue-400"></i>
            <div>
                <h3 class="mb-2 font-bold text-blue-300">Secure Your Wallet</h3>
                <p class="text-sm leading-relaxed text-blue-200">
                    Enable two-factor authentication and never share your private keys. 
                    Always verify recipient addresses before sending Bitcoin.
                </p>
            </div>
        </div>
    </div>
</div>

<?php include 'views/footer.php'; ?>
