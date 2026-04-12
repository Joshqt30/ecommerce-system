<?php
include '../includes/cart-panel.php';
include '../includes/header.php';
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
                    <a href="#" data-category="Samsung phones">Samsung phone</a>
                    <a href="#" data-category="Phone accessories">Phone accessories</a>
                    <a href="#" data-category="Cases & covers">Cases & covers</a>
                </div>
                <a href="#" class="view-all-btn" data-category="Cell phones">
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
                    <a href="#" data-category="Monitors">Monitors</a>
                    <a href="#" data-category="Apple laptops">Apple laptops</a>
                    <a href="#" data-category="Web cameras">Web cameras</a>
                </div>
                <a href="#" class="view-all-btn" data-category="Computers & Laptops">
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
                    <a href="#" data-category="Blenders & Mixers">Blenders, Mixers</a>
                    <a href="#" data-category="Kitchen knives">Kitchen knife</a>
                    <a href="#" data-category="Kitchen accessories">Other accessories</a>
                </div>
                <a href="#" class="view-all-btn" data-category="Kitchen Equipment">
                    <span>View all</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 12h14M12 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
            <img class="category-image cat-img-kitchen" src="https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=300&q=80" alt="Kitchen equipment" />
        </div>
    </section>

    <!-- Sub-category Icons -->
    <section class="subcategories-section">
        <div class="item-subcategory" data-category="Smartphones">
            <img src="https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=160&q=75" alt="Smartphones" />
            <span class="subcategory-title">Smartphones</span>
        </div>
        <div class="item-subcategory" data-category="Headsets">
            <img src="https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=160&q=75" alt="Headsets" />
            <span class="subcategory-title">Headsets</span>
        </div>
        <div class="item-subcategory" data-category="Laptops">
            <img src="https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=160&q=75" alt="Laptops" />
            <span class="subcategory-title">Laptops</span>
        </div>
        <div class="item-subcategory" data-category="TV sets">
            <img src="https://images.unsplash.com/photo-1593784991095-a205069470b6?w=160&q=75" alt="TV sets" />
            <span class="subcategory-title">TV sets</span>
        </div>
        <div class="item-subcategory" data-category="Sound">
            <img src="https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=160&q=75" alt="Sound" />
            <span class="subcategory-title">Sound</span>
        </div>
        <div class="item-subcategory" data-category="Watches">
            <img src="https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=160&q=75" alt="Watches" />
            <span class="subcategory-title">Watches</span>
        </div>
        <div class="item-subcategory" data-category="Cameras">
            <img src="https://images.unsplash.com/photo-1526170375885-4d8ecf77b99f?w=160&q=75" alt="Cameras" />
            <span class="subcategory-title">Cameras</span>
        </div>
        <div class="item-subcategory" data-category="Others">
            <img src="https://images.unsplash.com/photo-1491553895911-0055eca6402d?w=160&q=75" alt="Others" />
            <span class="subcategory-title">Others</span>
        </div>
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