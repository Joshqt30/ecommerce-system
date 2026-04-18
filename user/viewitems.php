<?php


include '../config/db.php';

define('PRODUCT_IMGS_BASE', '/ecommerce-system/imgs/products/');

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($productId <= 0) {
    die("Product not found.");
}

$res = pg_query_params($conn,
    "SELECT * FROM products WHERE id = $1",
    [$productId]
);

$product = pg_fetch_assoc($res);

if (!$product) {
    die("Product not found.");
}

/* GET PRODUCT IMAGES */
$imgRes = pg_query_params($conn,
    "SELECT image_url FROM product_images WHERE product_id = $1",
    [$productId]
);

$images = [];
while ($img = pg_fetch_assoc($imgRes)) {
    $images[] = $img['image_url'];
}

/* fallback if no images */
if (count($images) === 0) {
    $images[] = $product['image'];
}

  // Fetch all variants for this product
  $variantRes = pg_query_params($conn,
      "SELECT * FROM product_variants WHERE product_id = $1 ORDER BY id",
      [$productId]
  );
  $variants = pg_fetch_all($variantRes) ?: [];

  // Group variants by attribute types (e.g., "Color", "Size")
  $attributeGroups = [];
  foreach ($variants as $v) {
      $attrs = json_decode($v['attributes'], true);
      foreach ($attrs as $key => $value) {
          $attributeGroups[$key][$value] = true;
      }
  }


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rating'])) {

    $rating  = (int)$_POST['rating'];
    $comment = trim($_POST['comment']);

    if ($rating >= 1 && $rating <= 5 && $comment !== '') {

        /* fake hashed username (privacy-safe) */
        $username = 'user_' . substr(hash('sha256', session_id() ?? uniqid()), 0, 6);

       $result = pg_query_params($conn,
    "INSERT INTO reviews (product_id, rating, comment, username, created_at)
     VALUES ($1, $2, $3, $4, NOW())",
    [$productId, $rating, $comment, $username]
      );

      if (!$result) {
          // Display the PostgreSQL error (temporary, for debugging)
          die("Review insert failed: " . pg_last_error($conn));
      }
    }

    header("Location: viewitems.php?id=" . $productId . "&tab=reviews");
    exit;
}

include '../includes/header.php';
include '../includes/cart-panel.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>E-Commerce – Product Page</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Serif+Display&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="../assets/css/cart-panel.css">
  <link rel="stylesheet" href="../assets/css/viewitems.css">
  <link rel="stylesheet" href="../assets/css/header.css">
</head>
<body>


<!-- MAIN -->
<main>
  <div class="product-top">

   <!-- GALLERY -->
    <div class="gallery">

      <div class="thumbnails" id="thumbs">
        <?php foreach ($images as $i => $img): ?>
          <div class="thumb <?= $i === 0 ? 'active' : '' ?>" data-idx="<?= $i ?>">
          <img src="<?= PRODUCT_IMGS_BASE . htmlspecialchars($img) ?>" alt="">          
        </div>
        <?php endforeach; ?>
      </div>

      <div class="main-image">
        <img id="mainImg"
          src="<?= PRODUCT_IMGS_BASE . htmlspecialchars($images[0]) ?>"
          alt="<?= htmlspecialchars($product['name']) ?>" />
      </div>

    </div>

    <!-- INFO -->
    <div class="product-info">
    <h1 class="product-title">
      <?= htmlspecialchars($product['name']) ?>
    </h1>
      <div class="product-meta">
        <div class="stars">
          <span class="star-icon">★</span><span class="star-icon">★</span>
          <span class="star-icon">★</span><span class="star-icon">★</span>
          <span class="star-icon" style="color:#d1d5db">★</span>
        </div>
        <span class="rating-val">4.5</span>
        <span class="sep">·</span>
        <div class="orders-count">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          154 orders
        </div>
      </div>

      <div class="product-specs">
        <span class="spec-label">Made in:</span><span class="spec-val">Australia</span>
        <span class="spec-label">Design:</span><span class="spec-val">Modern</span>
        <span class="spec-label">Delivery:</span>
        <span class="spec-val" style="color:#16a34a; display:flex; align-items:center; gap:5px;">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          2 days delivery
        </span>
      </div>

      <!-- SCREEN SIZE -->
      <?php
      // Fetch unique attribute names and values from variants
      $attributeMap = [];
      foreach ($variants as $v) {
          $attrs = json_decode($v['attributes'], true);
          foreach ($attrs as $attrName => $attrValue) {
              $attributeMap[$attrName][$attrValue] = true;
          }
      }
      ?>

      <?php foreach ($attributeMap as $attrName => $values): ?>
          <div class="option-group">
              <div class="option-label"><?= htmlspecialchars($attrName) ?></div>
              <div class="option-buttons" data-attribute="<?= htmlspecialchars($attrName) ?>">
                  <?php 
                  $first = true;
                  foreach (array_keys($values) as $val): 
                  ?>
                      <button class="opt-btn <?= $first ? 'active' : '' ?>" 
                              data-val="<?= htmlspecialchars($val) ?>">
                          <?= htmlspecialchars($val) ?>
                      </button>
                  <?php 
                  $first = false;
                  endforeach; 
                  ?>
              </div>
          </div>
      <?php endforeach; ?>

      <!-- STOCK STATUS -->
      <div class="stock-status-wrapper">
      <span class="stock-status" id="stockStatus">In Stock</span>
      <span class="stock-quantity" id="stockQuantity"></span>
    </div>
      <!-- QUANTITY -->
      <div class="option-group">
        <div class="option-label">Quantity</div>
        <div class="quantity-row">
          <button class="qty-btn" id="qtyMinus">−</button>
          <input class="qty-value" id="qtyVal" type="text" value="1" readonly/>
          <button class="qty-btn" id="qtyPlus">+</button>
        </div>
      </div>

      <!-- PRICE -->
      <div class="price-block">
        <div class="price-label">Price</div>
      <div class="price" id="priceDisplay" data-price="<?= (float)$product['price'] ?>">
      ₱<?= number_format((float)$product['price'], 2) ?>
    </div>     
    </div>

      <!-- ACTIONS -->
      <div class="cta-row">
        <button class="btn-cart" id="addToCart">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
          Add to cart
        </button>
        <button class="btn-buy" id="buyNow">Buy now</button>
      </div>
    </div>
  </div>

  <!-- TABS -->
  <div class="product-tabs">
    <div class="tab-nav">
      <button class="tab-btn active" data-tab="description">Description</button>
      <button class="tab-btn" data-tab="reviews">Reviews</button>
      <button class="tab-btn" data-tab="company">Company</button>
      <button class="tab-btn" data-tab="usage">Usage guide</button>
    </div>
    <div class="tab-content active" id="tab-description">
    <p><?= htmlspecialchars($product['description']) ?></p>
    </div>
  
    
   <div class="tab-content" id="tab-reviews">

        <?php
        $rRes = pg_query_params($conn,
            "SELECT * FROM reviews WHERE product_id = $1 ORDER BY created_at DESC",
            [$productId]
        );

        $reviews = [];
        while ($r = pg_fetch_assoc($rRes)) {
            $reviews[] = $r;
        }

        $totalReviews = count($reviews);
        $initialLimit = 2; // unang ipapakita
        ?>

        <div class="review-list-container">
          <?php if ($totalReviews > 0): ?>
            <div class="review-list">
              <?php 
              // Ipakita yung first $initialLimit reviews
              for ($i = 0; $i < min($initialLimit, $totalReviews); $i++): 
                $r = $reviews[$i];
              ?>
                <div class="review-card">
                  <div class="review-top">
                    <div class="review-user"><?= htmlspecialchars($r['username'] ?? 'user_xxxxxx') ?></div>
                    <div class="review-date"><?= date("M d, Y", strtotime($r['created_at'])) ?></div>
                  </div>
                  <div class="review-stars">
                    <?php for ($s = 1; $s <= 5; $s++): ?>
                      <span class="<?= $s <= $r['rating'] ? 'star-on' : 'star-off' ?>">★</span>
                    <?php endfor; ?>
                  </div>
                  <div class="review-comment"><?= htmlspecialchars($r['comment']) ?></div>
                </div>
              <?php endfor; ?>
            </div>

            <?php if ($totalReviews > $initialLimit): ?>
              <!-- Hidden additional reviews -->
              <div class="more-reviews" id="moreReviews">
                <?php for ($i = $initialLimit; $i < $totalReviews; $i++): 
                  $r = $reviews[$i];
                ?>
                  <div class="review-card">
                    <div class="review-top">
                      <div class="review-user"><?= htmlspecialchars($r['username'] ?? 'user_xxxxxx') ?></div>
                      <div class="review-date"><?= date("M d, Y", strtotime($r['created_at'])) ?></div>
                    </div>
                    <div class="review-stars">
                      <?php for ($s = 1; $s <= 5; $s++): ?>
                        <span class="<?= $s <= $r['rating'] ? 'star-on' : 'star-off' ?>">★</span>
                      <?php endfor; ?>
                    </div>
                    <div class="review-comment"><?= htmlspecialchars($r['comment']) ?></div>
                  </div>
                <?php endfor; ?>
              </div>

              <!-- See More Button -->
              <button class="see-more-link" id="seeMoreBtn">
              <span>See more</span>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 12 15 18 9"></polyline>
              </svg>
            </button>
            <?php endif; ?>

          <?php else: ?>
            <p class="no-reviews">No reviews yet. Be the first to review this product.</p>
          <?php endif; ?>
        </div>

        <!-- Review Form (same as before) -->
       <form method="POST" class="review-form">
        <!-- Rating field stays on top, compact -->
        <div class="rating-field">
          <label>Rating</label>
          <select name="rating" required>
            <option value="5">★★★★★</option>
            <option value="4">★★★★☆</option>
            <option value="3">★★★☆☆</option>
            <option value="2">★★☆☆☆</option>
            <option value="1">★☆☆☆☆</option>
          </select>
        </div>

        <!-- Textarea + Submit button side by side -->
        <div class="review-input-row">
          <textarea name="comment" placeholder="Write your review..." required></textarea>
          <button type="submit" class="submit-review-btn">Submit</button>
        </div>
      </form>

      </div>
   
    <div class="tab-content" id="tab-company">
      <p>We are a premium electronics retailer committed to delivering quality products directly to your door. Every item is sourced from verified manufacturers with a full warranty and dedicated customer support.</p>
      <p>Founded in 2015, our company has served over 500,000 customers worldwide, with a focus on transparency, fast delivery, and hassle-free returns.</p>
    </div>
    <div class="tab-content" id="tab-usage">
      <p><strong>Getting Started:</strong> Charge the device fully before first use. Press and hold the power button for 3 seconds to boot.</p>
      <p><strong>Care Instructions:</strong> Use a soft microfiber cloth for cleaning. Avoid exposing the device to extreme temperatures or moisture. Store in a cool, dry place when not in use.</p>
      <p><strong>Warranty:</strong> This product comes with a 12-month manufacturer warranty. Contact our support team for any hardware-related concerns.</p>
    </div>
  </div>
</main>

<!-- TOAST -->
<div class="toast" id="toast">
  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
  <span id="toastMsg">Item added to cart!</span>
</div>


<script src="../scripts/cart-panel.js"></script>
<script>
    window.productId = <?= json_encode($productId) ?>;
    window.productVariants = <?= json_encode($variants) ?>;
    window.productImgsBase = <?= json_encode(PRODUCT_IMGS_BASE) ?>;
</script>
<script src="../scripts/viewitems.js"></script>

</body>
</html>