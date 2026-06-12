<?php
session_start();
include '../configs/databaseConnection.php';

if (!isset($_SESSION['userId'])) { header("Location: login.php"); exit(); }
$userId = $_SESSION['userId'];

$cartQuery = "SELECT c.cartId, c.totalPrice 
              FROM cart c JOIN usercart uc ON c.cartId = uc.cartId
              WHERE uc.userId = $userId";
$cartResult = mysqli_query($conn, $cartQuery);
$cartData = mysqli_fetch_assoc($cartResult);
$cartId = $cartData['cartId'] ?? 0;

// Remove books
if (isset($_POST['selectedToRemoveBooks']) && isset($_POST['remove'])) {
    foreach ($_POST['selectedToRemoveBooks'] as $bookId) {
        $bookId = (int)$bookId;
        mysqli_query($conn, "UPDATE book SET cartId = NULL, bookStatus = 'In stock' WHERE bookId = $bookId AND cartId = $cartId");
        unset($_SESSION['book_added_' . $bookId]);
    }
    header("Location: cart.php?removed=1");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['checkoutBookOwnerId'])) {
    $_SESSION['selectedBookOwner'] = $_POST['checkoutBookOwnerId'];
}

// Checkout
if (isset($_POST['checkout'])) {
    if (!isset($_SESSION['selectedBookOwner'])) {
        header("Location: cart.php?error=no_seller");
        exit();
    }
    mysqli_begin_transaction($conn);
    $bookOwner = $_SESSION['selectedBookOwner'];
    // Get only books NOT unchecked (selectedToRemoveBooks contains unchecked ones)
    $unchecked = [];
    if (isset($_POST['selectedToRemoveBooks'])) {
        foreach ($_POST['selectedToRemoveBooks'] as $bid) { $unchecked[] = (int)$bid; }
    }
    try {
        $booksResult = mysqli_query($conn, "SELECT bookId FROM book WHERE cartId = $cartId AND userId = $bookOwner");
        while ($book = mysqli_fetch_assoc($booksResult)) {
            $bookId = $book['bookId'];
            if (in_array($bookId, $unchecked)) continue; // skip unchecked
            mysqli_query($conn, "UPDATE exchangerequest SET status = 'Completed' WHERE (bookToExchange = $bookId OR bookToExchangeWith = $bookId) AND status = 'Accepted'");
            mysqli_query($conn, "UPDATE book SET bookStatus = 'Out of stock', cartId = NULL WHERE bookId = $bookId");
        }
        mysqli_query($conn, "UPDATE cart SET totalPrice = 0.00 WHERE cartId = $cartId");
        mysqli_commit($conn);
        header("Location: cart.php?checkout=success");
        exit();
    } catch (Exception $e) {
        mysqli_rollback($conn);
        header("Location: cart.php?error=checkout_failed");
        exit();
    }
}

// Rating
if (isset($_POST['submit_rating']) && $_POST['submit_rating'] == 'Submit') {
    $ratingValue = (int)$_POST['rating'];
    $bookOwner = $_SESSION['selectedBookOwner'];
    $stmt = mysqli_prepare($conn, "INSERT INTO rating (ratingValue, userId) VALUES (?, ?)");
    mysqli_stmt_bind_param($stmt, "ii", $ratingValue, $bookOwner);
    mysqli_stmt_execute($stmt);
    header("Location: dashboard.php?rated=1");
    exit();
}

$bookOwnersQuery = "SELECT b.userId, u.fullName 
                    FROM book b JOIN user u ON b.userId = u.userId
                    WHERE b.cartId = $cartId GROUP BY b.userId";
$bookOwnersResult = mysqli_query($conn, $bookOwnersQuery);
$hasBooks = mysqli_num_rows($bookOwnersResult) > 0;
$bookOwnersResult = mysqli_query($conn, $bookOwnersQuery);

$totalPrice = 0;
if (isset($_POST['total']) && isset($_SESSION['selectedBookOwner'])) {
    $selectedbookOwner = $_SESSION['selectedBookOwner'];
    $tRes = mysqli_query($conn, "SELECT SUM(price) AS total FROM book WHERE cartId = $cartId AND userId = $selectedbookOwner");
    $tSet = mysqli_fetch_assoc($tRes);
    $totalPrice = $tSet['total'] ?? 0;
    if ($cartId > 0) mysqli_query($conn, "UPDATE cart SET totalPrice = $totalPrice WHERE cartId = $cartId");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart — ShelfTrade</title>
    <link rel="stylesheet" href="../assets/css/header.css">
    <link rel="stylesheet" href="../assets/css/cart.css">
</head>
<body>
<header>
    <div class="logo"><img src="../assets/images/logo_short.png" alt="ShelfTrade Logo"></div>
    <nav>
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="my_books.php">My Books</a></li>
            <li><a href="exchange_requests.php">Exchange Requests</a></li>
            <li><a href="logout.php">Logout</a></li>
            <li><a href="cart.php" class="to-cart">&#128722;</a></li>
        </ul>
    </nav>
</header>

<form method="POST" class="cart-layout">

    <!-- LEFT: Summary panel -->
    <div class="summary-panel">
        <h2 class="summary-title">Order Summary</h2>
        <p class="disclaimer">⚠️ You can only complete one order per seller at a time. Select a seller before checking out.</p>
        <div class="summary-total">
            <span>Total</span>
            <span id="total-price"><?= number_format($totalPrice, 2) ?> SAR</span>
        </div>
        <div class="summary-actions">
            <input type="submit" name="total" value="Calculate Total">
            <input type="submit" name="remove" value="Remove Selected">
            <input type="submit" name="checkout" value="Checkout">
        </div>
    </div>

    <!-- RIGHT: Cart items -->
    <div class="cart-items-panel">
        <h1>🛒 Your Shopping Cart</h1>

        <?php if (!$hasBooks): ?>
            <div class="empty-cart">
                <div class="empty-icon">📭</div>
                <p class="empty-title">Your cart is empty</p>
                <p class="empty-sub">Browse books and add them to your cart to get started.</p>
                <a href="dashboard.php" class="browse-btn">Browse Books</a>
            </div>
        <?php else: ?>
            <?php while ($row = mysqli_fetch_assoc($bookOwnersResult)):
                $selectedbookOwner = $row['userId'];
                $sellerName = htmlspecialchars($row['fullName']);
                $itemsQuery = "SELECT bookId, image, title, price, userId FROM book WHERE cartId = $cartId AND userId = $selectedbookOwner";
                $itemsResult = mysqli_query($conn, $itemsQuery);
                $isChecked = (isset($_SESSION['selectedBookOwner']) && $_SESSION['selectedBookOwner'] == $selectedbookOwner) ? 'checked' : '';
            ?>
            <div class="seller-row">
                <!-- Radio OUTSIDE seller box -->
                <label class="seller-radio-wrap" title="Select this seller to checkout">
                    <input type="radio" name="checkoutBookOwnerId" value="<?= $selectedbookOwner ?>" required <?= $isChecked ?>>
                </label>

                <!-- Seller box -->
                <div class="seller-box">
                    <!-- Seller header: avatar + name at the top -->
                    <div class="seller-header">
                        <img src="../assets/images/profile.jpg" alt="Seller" class="seller-avatar">
                        <span class="seller-name"><?= $sellerName ?></span>
                    </div>

                    <!-- Books -->
                    <?php foreach ($itemsResult as $item): ?>
                    <div class="cart-book-row">
                        <input type="checkbox" name="selectedToRemoveBooks[]" value="<?= $item['bookId'] ?>" class="remove-check">
                        <div class="book-card">
                            <img src="../assets/images/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['title']) ?>">
                            <div class="book-info">
                                <h3 class="book-title"><?= htmlspecialchars($item['title']) ?></h3>
                                <p class="book-price"><?= number_format($item['price'], 2) ?> SAR</p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>

</form>

<!-- Rating Modal -->
<?php if (isset($_GET['checkout']) && $_GET['checkout'] == 'success'): ?>
<div id="rating-modal" class="modal">
    <div class="modal-content">
        <h2>Checkout Completed!</h2>
        <h3>Rate Your Experience</h3>
        <p>Please rate your purchase:</p>
        <form method="POST">
            <div class="stars">
                <span class="star" data-value="1" onclick="selectRating(1)">★</span>
                <span class="star" data-value="2" onclick="selectRating(2)">★</span>
                <span class="star" data-value="3" onclick="selectRating(3)">★</span>
                <span class="star" data-value="4" onclick="selectRating(4)">★</span>
                <span class="star" data-value="5" onclick="selectRating(5)">★</span>
            </div>
            <input type="hidden" name="rating" id="rating-value" value="0">
            <div class="modal-actions">
                <input type="submit" name="submit_rating" value="Submit">
                <button type="button" onclick="closeModal()">Skip</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<footer>
    <div class="footer-content">
        <p>&copy; 2025 ShelfTrade. All Rights Reserved.</p>
        <div class="footer-links"><a href="#">Privacy Policy</a><a href="#">Terms of Service</a></div>
    </div>
</footer>

<div id="toast"></div>
<script>
function selectRating(value) {
    document.getElementById('rating-value').value = value;
    document.querySelectorAll('.star').forEach(s => {
        s.classList.toggle('selected', s.getAttribute('data-value') <= value);
    });
}
function closeModal() { window.location.href = 'dashboard.php'; }

document.addEventListener('DOMContentLoaded', function() {
    // Auto-check all books for selected seller when radio is clicked
    document.querySelectorAll('input[name="checkoutBookOwnerId"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            // Uncheck all
            document.querySelectorAll('.remove-check').forEach(function(cb) { cb.checked = false; });
            // Check all books inside this seller's box
            var sellerBox = this.closest('.seller-row').querySelector('.seller-box');
            if (sellerBox) {
                sellerBox.querySelectorAll('.remove-check').forEach(function(cb) { cb.checked = true; });
            }
        });
    });

    // Checkout validation
    var checkoutBtn = document.querySelector('input[name="checkout"]');
    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', function(e) {
            var radios = document.querySelectorAll('input[name="checkoutBookOwnerId"]');
            var selected = document.querySelector('input[name="checkoutBookOwnerId"]:checked');
            if (radios.length === 0) { e.preventDefault(); showToast('Your cart is empty.', 'error'); return; }
            if (!selected) { e.preventDefault(); showToast('Please select a seller before checking out.', 'error'); return; }
            showToast('Processing your order...', 'success');
        });
    }

    // Remove validation
    var removeBtn = document.querySelector('input[name="remove"]');
    if (removeBtn) {
        removeBtn.addEventListener('click', function(e) {
            var checked = document.querySelectorAll('input[name="selectedToRemoveBooks[]"]:checked');
            if (checked.length === 0) { e.preventDefault(); showToast('Please select books to remove.', 'error'); }
        });
    }

    // Delay rating modal to show checkout toast first
    var modal = document.getElementById('rating-modal');
    if (modal) {
        modal.style.display = 'none';
        showToast('Checkout completed successfully!', 'success');
        setTimeout(function() { modal.style.display = 'flex'; }, 3200);
    }
});

function showToast(msg, type) {
    var t = document.getElementById('toast');
    t.style.cssText = 'position:fixed;top:24px;left:50%;transform:translateX(-50%);z-index:9999;padding:14px 28px;border-radius:8px;font-size:15px;font-weight:500;box-shadow:0 4px 16px rgba(0,0,0,0.15);min-width:260px;text-align:center;opacity:1;transition:opacity 0.4s;background:'+(type==='error'?'#f8d7da':'#d4edda')+';color:'+(type==='error'?'#721c24':'#155724')+';border:1px solid '+(type==='error'?'#f5c6cb':'#c3e6cb')+';';
    t.innerText = msg;
    setTimeout(function(){ t.style.opacity='0'; }, 3000);
}
<?php if (isset($_GET['rated'])): ?>
showToast('Thank you for your rating!', 'success');
window.history.replaceState(null, null, window.location.pathname);
<?php endif; ?>
<?php if (isset($_GET['removed'])): ?>
showToast('Selected books removed from cart.', 'success');
<?php endif; ?>
</script>
</body>
</html>
