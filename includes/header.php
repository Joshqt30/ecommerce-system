<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../config/db.php';

$user = null;

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
}
?>
 
 
 <!-- Header -->
    <header class="header">
        <div class="nav-bar">
            <a href="../user/dashboard.php" class="logo-wrap">
                <img class="logo-icon" src="https://cdn.codia.ai/figma/DNIGD5YlSaH0gJQnZ0iH7f/img-40e47e05667e0932.png" alt="E-Commerce logo" width="24" height="24" />
                <span class="logo-text">E-Commerce</span>
            </a>
            <!-- Nav links + action buttons -->
        <nav class="nav-links">
            <a href="../user/dashboard.php">About</a>
            <a href="../user/dashboard.php">Shop</a>
            <a href="#">Help</a>
 
            <!-- Cart button -->
            <a href="#" class="cart-btn" id="cartBtn">
                <svg class="cart-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="9" cy="21" r="1"/>
                    <circle cx="20" cy="21" r="1"/>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                </svg>
                <span class="cart-text">Cart <span class="cart-count" id="cartCount">(0)</span></span>
            </a>
 
            <!-- ── Account icon + dropdown ── -->
            <?php if (isset($_SESSION['user_id'])): ?>
            <div class="account-wrap" id="accountWrap">
 
                <!-- Circular account button -->
                <button class="account-btn" id="accountBtn" aria-label="Account menu">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="8" r="4"/>
                        <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                    </svg>
                </button>
 
                <!-- Dropdown — matches the screenshot exactly -->
                <div class="account-dropdown" id="accountDropdown">
 
                    <!-- Avatar + name + email -->
                    <div class="dd-profile">
                        <div class="dd-avatar">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="8" r="4"/>
                                <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                            </svg>
                            <!-- Edit badge -->
                            <span class="dd-avatar-badge">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 20h9"/>
                                    <path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"/>
                                </svg>
                            </span>
                        </div>
                        <p class="dd-name">
                        <?= isset($user['username']) ? $user['username'] : 'Guest'; ?>
                    </p>

                    <p class="dd-email">
                        <?= isset($user['email']) ? $user['email'] : 'Not logged in'; ?>
                    </p>
                    </div>
 
                    <div class="dd-divider"></div>
 
                    <!-- Menu items -->
                    <a href="../user/profile.php?tab=profile" target="_blank" class="dd-item">
                        <span class="dd-item-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="8" r="4"/>
                                <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                            </svg>
                        </span>
                        Profile
                    </a>
 
                    <a href="../user/profile.php?tab=orders" class="dd-item">
                        <span class="dd-item-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>
                                <line x1="3" y1="6" x2="21" y2="6"/>
                                <path d="M16 10a4 4 0 01-8 0"/>
                            </svg>
                        </span>
                        My Orders
                    </a>
 
                    <a href="../user/profile.php?tab=settings" class="dd-item">
                        <span class="dd-item-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="3"/>
                                <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>
                            </svg>
                        </span>
                        Settings
                    </a>
 
                    <div class="dd-divider"></div>
 
                    <a href="/ecommerce-system/auth/logout.php" class="dd-item dd-logout">
                        <span class="dd-item-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
                                <polyline points="16 17 21 12 16 7"/>
                                <line x1="21" y1="12" x2="9" y2="12"/>
                            </svg>
                        </span>
                        Log out
                    </a>
 
                </div>
            </div>
            <?php else: ?>

            <a href="../auth/login.php" class="login-btn">Login</a>

        <?php endif; ?>
        
        </nav>
    </div>
</header>
 
<script>
(function () {
    const btn      = document.getElementById('accountBtn');
    const dropdown = document.getElementById('accountDropdown');
    const wrap     = document.getElementById('accountWrap');
    if (!btn) return;
 
    btn.addEventListener('click', e => {
        e.stopPropagation();
        const isOpen = dropdown.classList.toggle('open');
        btn.classList.toggle('active', isOpen);
    });
 
    document.addEventListener('click', e => {
        if (!wrap.contains(e.target)) {
            dropdown.classList.remove('open');
            btn.classList.remove('active');
        }
    });
 
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            dropdown.classList.remove('open');
            btn.classList.remove('active');
        }
    });
})();
</script>