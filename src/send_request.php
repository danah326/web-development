<?php
include '../configs/databaseConnection.php';
session_start();

if (!isset($_SESSION['userId'])) { header("Location: login.php"); exit(); }

$userId     = $_SESSION['userId'];
$bookOwnerId = (int)$_GET['bookOwnerId'];
$bookId      = (int)$_GET['bookId'];

// Get the target book info
$targetRes = mysqli_query($conn, "SELECT bookId, image, title, price, category FROM book WHERE bookId = $bookId");
$targetBook = mysqli_fetch_assoc($targetRes);

// Get user's in-stock books
$bookSql = "SELECT bookId, image, title FROM book WHERE userId = $userId AND bookStatus = 'In stock'";
$books = mysqli_query($conn, $bookSql);

$error = null;

if (isset($_POST['selectedBookId'])) {
    $selectedBookId = (int)$_POST['selectedBookId'];

    // Duplicate pending check — show error ON THIS PAGE
    $dupCheck = mysqli_query($conn, "SELECT requestId FROM exchangerequest
        WHERE senderId = $userId
        AND bookToExchange = $selectedBookId
        AND bookToExchangeWith = $bookId
        AND status = 'Pending'");

    if (mysqli_num_rows($dupCheck) > 0) {
        $error = "You already have a pending request for this exact book pair. Please wait for a response.";
    } else {
        mysqli_query($conn, "INSERT INTO exchangerequest (senderId, receiverId, bookToExchange, bookToExchangeWith)
                             VALUES ($userId, $bookOwnerId, $selectedBookId, $bookId)");
        header("Location: dashboard.php?sent=1");
        exit();
    }
}

function sessionStorage_toast($msg, $type) {
    echo "<script>sessionStorage.setItem('toast', JSON.stringify({msg:'$msg', type:'$type'}));</script>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Exchange — ShelfTrade</title>
    <link rel="stylesheet" href="../assets/css/header.css">
    <link rel="stylesheet" href="../assets/css/send_request.css">
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

<div class="sr-wrap">
    <div class="sr-select">
        <span class="sr-title">Select a book to exchange for <span class="sr-target-title" dir="rtl"><?php echo htmlspecialchars($targetBook['title']); ?></span></span>
        <?php if ($error): ?>
            <div class="sr-error"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="sr-books-list">
                <?php
                $hasBooks = false;
                while ($row = mysqli_fetch_assoc($books)):
                    $hasBooks = true;
                ?>
                <label class="sr-book-option">
                    <input type="radio" name="selectedBookId" value="<?php echo $row['bookId']; ?>" required>
                    <div class="sr-option-card">
                        <img src="../assets/images/<?php echo htmlspecialchars($row['image']); ?>" alt="Book">
                        <span dir="rtl"><?php echo htmlspecialchars($row['title']); ?></span>
                    </div>
                </label>
                <?php endwhile; ?>
                <?php if (!$hasBooks): ?>
                    <p class="sr-empty">You have no books available for exchange. <a href="add_book.php">Add a book</a> first.</p>
                <?php endif; ?>
            </div>
            <?php if ($hasBooks): ?>
            <button type="submit" class="sr-btn">Send Exchange Request</button>
            <?php endif; ?>
        </form>
    </div>
</div>

<footer>
    <div class="footer-content">
        <p>&copy; 2025 ShelfTrade. All Rights Reserved.</p>
        <div class="footer-links"><a href="#">Privacy Policy</a><a href="#">Terms of Service</a></div>
    </div>
</footer>
</body>
</html>
