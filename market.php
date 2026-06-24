<?php
require_once '../includes/header.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

// Handle product posting
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['post_product'])) {
    $product_name = sanitizeInput($_POST['product_name']);
    $description = sanitizeInput($_POST['description']);
    $price = (float)$_POST['price'];
    $image_path = '';
    
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $target_dir = "../assets/uploads/products/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
        $image_path = time() . '_' . basename($_FILES['product_image']['name']);
        move_uploaded_file($_FILES['product_image']['tmp_name'], $target_dir . $image_path);
    }
    
    $expires_at = generateExpiryTime();
    $stmt = $db->prepare("INSERT INTO market (user_id, product_name, description, price, image_path, expires_at) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $product_name, $description, $price, $image_path, $expires_at]);
    $_SESSION['success'] = "Product posted successfully!";
    header("Location: market.php");
    exit();
}

// Get products
$stmt = $db->prepare("
    SELECT m.*, u.username, u.profile_photo 
    FROM market m
    JOIN users u ON u.user_id = m.user_id
    WHERE m.expires_at > NOW() AND m.status = 'available'
    ORDER BY m.created_at DESC
");
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="market-container">
    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    
    <h2><i class="fas fa-store"></i> Marketplace</h2>
    
    <!-- Post Product -->
    <div class="post-card">
        <h3><i class="fas fa-plus-circle"></i> Sell Something</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="post_product" value="1">
            <div class="form-group">
                <label>Product Name</label>
                <input type="text" name="product_name" required>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="3" required></textarea>
            </div>
            <div class="form-group">
                <label>Price (Ksh)</label>
                <input type="number" name="price" step="0.01" min="0" required>
            </div>
            <div class="form-group">
                <label>Product Image (max 5MB)</label>
                <input type="file" name="product_image" accept="image/*" class="image-input">
            </div>
            <button type="submit" class="btn-primary"><i class="fas fa-store-alt"></i> Post Product</button>
        </form>
    </div>
    
    <!-- Search Products -->
    <div class="post-card">
        <div class="form-group">
            <input type="text" id="search-products" placeholder="Search products..." style="width: 100%;">
        </div>
    </div>
    
    <!-- Products Grid -->
    <div class="product-grid" id="product-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px;">
        <?php foreach($products as $product): ?>
        <div class="product-card">
            <div class="product-image">
                <img src="../assets/uploads/products/<?php echo $product['image_path'] ?: 'default-product.jpg'; ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
            </div>
            <div class="product-info">
                <h4><?php echo htmlspecialchars($product['product_name']); ?></h4>
                <p style="color: #666; font-size: 14px;"><?php echo substr(htmlspecialchars($product['description']), 0, 100); ?>...</p>
                <div class="product-price">Ksh <?php echo number_format($product['price'], 2); ?></div>
                <div style="display: flex; align-items: center; margin-top: 10px;">
                    <img src="../assets/uploads/profiles/<?php echo $product['profile_photo'] ?: 'default.png'; ?>" 
                         style="width: 30px; height: 30px; border-radius: 50%; margin-right: 10px;">
                    <small><?php echo htmlspecialchars($product['username']); ?></small>
                </div>
                <a href="chat.php?user=<?php echo $product['user_id']; ?>" class="btn-primary" style="display: block; text-align: center; margin-top: 10px; text-decoration: none;">
                    <i class="fas fa-envelope"></i> Contact Seller
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>