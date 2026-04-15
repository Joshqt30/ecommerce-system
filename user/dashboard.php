<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

include '../includes/cart-panel.php';
include '../includes/header.php';
include '../config/db.php';
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>E-Commerce Store</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/cart-panel.css">
    <link rel="stylesheet" href="../assets/css/header.css">
</head>
<body>

    <!-- Search Bar -->
    <div class="search-bar">
        <div class="search-field-wrap">
            <div class="search-field" id="searchField">
                <input type="text" placeholder="Search for products" id="searchInput" autocomplete="off" />
                <button class="search-btn" id="searchBtn" aria-label="Search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                </button>
            </div>
            <div class="search-results" id="searchResults"></div>
        </div>
    </div>

   <!-- Category Boxes -->
<section class="categories-section">
    <!-- Cell phones -->
    <div class="box-category">
        <div class="category-info">
            <h2 class="category-title">Cell phones</h2>
            <div class="category-links">
                <a href="../user/category.php?cat=<?= urlencode('Samsung phones') ?>">Samsung phone</a>
                <a href="../user/category.php?cat=<?= urlencode('Phone accessories') ?>">Phone accessories</a>
                <a href="../user/category.php?cat=<?= urlencode('Cases & covers') ?>">Cases & covers</a>
            </div>
            <a href="../user/category.php?cat=<?= urlencode('Cell phones') ?>" class="view-all-btn">
                <span>View all</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12h14M12 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
        <img class="category-image cat-img-phones" src="https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=300&q=80" alt="Cell phones" />
    </div>

    <!-- Computers & Laptops -->
    <div class="box-category">
        <div class="category-info">
            <h2 class="category-title">Computers & Laptops</h2>
            <div class="category-links">
                <a href="../user/category.php?cat=<?= urlencode('Monitors') ?>">Monitors</a>
                <a href="../user/category.php?cat=<?= urlencode('Apple laptops') ?>">Apple laptops</a>
                <a href="../user/category.php?cat=<?= urlencode('Web cameras') ?>">Web cameras</a>
            </div>
            <a href="../user/category.php?cat=<?= urlencode('Computers & Laptops') ?>" class="view-all-btn">
                <span>View all</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12h14M12 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
        <img class="category-image cat-img-computers" src="https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=300&q=80" alt="Computers and Laptops" />
    </div>

    <!-- Kitchen Equipment -->
    <div class="box-category">
        <div class="category-info">
            <h2 class="category-title">Kitchen Equipment</h2>
            <div class="category-links">
                <a href="../user/category.php?cat=<?= urlencode('Blenders & Mixers') ?>">Blenders, Mixers</a>
                <a href="../user/category.php?cat=<?= urlencode('Kitchen knives') ?>">Kitchen knife</a>
                <a href="../user/category.php?cat=<?= urlencode('Kitchen accessories') ?>">Other accessories</a>
            </div>
            <a href="../user/category.php?cat=<?= urlencode('Kitchen Equipment') ?>" class="view-all-btn">
                <span>View all</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12h14M12 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
        <img class="category-image cat-img-kitchen" src="https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=300&q=80" alt="Kitchen equipment" />
    </div>
</section>

<!-- Sub-category Icons (now clickable as normal links) -->
<section class="subcategories-section">
    <a href="../user/category.php?cat=<?= urlencode('Smartphones') ?>" class="item-subcategory" style="text-decoration: none; color: inherit;">
        <img src="https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=160&q=75" alt="Smartphones" />
        <span class="subcategory-title">Smartphones</span>
    </a>
    <a href="../user/category.php?cat=<?= urlencode('Headsets') ?>" class="item-subcategory" style="text-decoration: none; color: inherit;">
        <img src="https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=160&q=75" alt="Headsets" />
        <span class="subcategory-title">Headsets</span>
    </a>
    <a href="../user/category.php?cat=<?= urlencode('Laptops') ?>" class="item-subcategory" style="text-decoration: none; color: inherit;">
        <img src="https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=160&q=75" alt="Laptops" />
        <span class="subcategory-title">Laptops</span>
    </a>
    <a href="../user/category.php?cat=<?= urlencode('TV sets') ?>" class="item-subcategory" style="text-decoration: none; color: inherit;">
        <img src="https://images.unsplash.com/photo-1593784991095-a205069470b6?w=160&q=75" alt="TV sets" />
        <span class="subcategory-title">TV sets</span>
    </a>
    <a href="../user/category.php?cat=<?= urlencode('Sound') ?>" class="item-subcategory" style="text-decoration: none; color: inherit;">
        <img src="https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=160&q=75" alt="Sound" />
        <span class="subcategory-title">Sound</span>
    </a>
    <a href="../user/category.php?cat=<?= urlencode('Watches') ?>" class="item-subcategory" style="text-decoration: none; color: inherit;">
        <img src="https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=160&q=75" alt="Watches" />
        <span class="subcategory-title">Watches</span>
    </a>
    <a href="../user/category.php?cat=<?= urlencode('Cameras') ?>" class="item-subcategory" style="text-decoration: none; color: inherit;">
        <img src="https://images.unsplash.com/photo-1526170375885-4d8ecf77b99f?w=160&q=75" alt="Cameras" />
        <span class="subcategory-title">Cameras</span>
    </a>
   <a href="../user/category.php?cat=All" class="item-subcategory" style="text-decoration: none; color: inherit;">
        <img src="https://images.unsplash.com/photo-1491553895911-0055eca6402d?w=160&q=75" alt="Others" />
        <span class="subcategory-title">Others</span>
    </a>
</section>

    <!-- Toast Notification -->
    <div class="toast" id="toast">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"/>
        </svg>
        <span id="toastMsg">Done</span>
    </div>

    <script src="../scripts/cart-panel.js"></script>
    <script src="../scripts/dashboard.js"></script>
</body>
</html>