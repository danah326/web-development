<?php
include '../configs/databaseConnection.php';
session_start();

if (!isset($_SESSION['userId'])) { header("Location: login.php"); exit(); }
$userId = $_SESSION['userId'];

if (isset($_POST['submit'])) {
    $requestId          = (int)$_POST['requestId'];
    $requestStatus      = $_POST['requestStatus'];
    $bookToExchange     = (int)$_POST['bookToExchange'];
    $bookToExchangeWith = (int)$_POST['bookToExchangeWith'];
    $senderId           = (int)$_POST['senderId'];

    $cartRow  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT cartId FROM usercart WHERE userId = $userId"));
    $userCart = $cartRow['cartId'];
    $cartRow2  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT cartId FROM usercart WHERE userId = $senderId"));
    $senderCart = $cartRow2['cartId'];

    if ($requestStatus == 'Rejected') {
        mysqli_query($conn, "UPDATE exchangerequest SET status = 'Rejected' WHERE requestId = $requestId");
        $msg = 'rejected';
    } else {
        // Accept this request
        mysqli_query($conn, "UPDATE exchangerequest SET status = 'Accepted' WHERE requestId = $requestId");
        mysqli_query($conn, "UPDATE book SET cartId = $userCart, bookStatus = 'Out of stock' WHERE bookId = $bookToExchange");
        mysqli_query($conn, "UPDATE book SET cartId = $senderCart, bookStatus = 'Out of stock' WHERE bookId = $bookToExchangeWith");

        // Auto-reject any other pending requests that involve either of these books
        mysqli_query($conn, "UPDATE exchangerequest SET status = 'Rejected'
            WHERE requestId != $requestId
            AND status = 'Pending'
            AND (bookToExchange IN ($bookToExchange, $bookToExchangeWith)
                 OR bookToExchangeWith IN ($bookToExchange, $bookToExchangeWith))");
        $msg = 'accepted';
    }
    header("Location: exchange_requests.php?msg=$msg");
    exit();
}

$requestReceivedSql = "SELECT requestId, bookToExchange, bookToExchangeWith
                       FROM exchangerequest
                       WHERE receiverId = $userId AND status = 'Pending'";
$requestReceived = mysqli_query($conn, $requestReceivedSql);

$requestSentSql = "SELECT requestId, status, bookToExchange, bookToExchangeWith
                   FROM exchangerequest WHERE senderId = $userId ORDER BY requestId DESC";
$requestSent = mysqli_query($conn, $requestSentSql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exchange Requests — ShelfTrade</title>
    <link rel="stylesheet" href="../assets/css/header.css">
    <link rel="stylesheet" href="../assets/css/exchange_requests.css">
</head>
<body>
<header>
    <div class="logo"><img src="../assets/images/logo_short.png" alt="ShelfTrade Logo"></div>
    <nav>
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="my_books.php">My Books</a></li>
            <li><a href="exchange_requests.php" class="active">Exchange Requests</a></li>
            <li><a href="logout.php">Logout</a></li>
            <li><a href="cart.php" class="to-cart">&#128722;</a></li>
        </ul>
    </nav>
</header>

<div class="page-wrap">
    <!-- RECEIVED -->
    <div class="section-container">
        <span class="section-label">Requests Received</span>
        <?php if (mysqli_num_rows($requestReceived) != 0): ?>
            <?php while ($row = mysqli_fetch_assoc($requestReceived)): ?>
            <form action="exchange_requests.php" method="POST" class="request-card">

                <?php
                // RECEIVED: bookToExchangeWith = MY book (sender wants it), bookToExchange = their book
                $pairs = [
                    ['id' => (int)$row['bookToExchangeWith'], 'mine' => true],
                    ['id' => (int)$row['bookToExchange'],     'mine' => false],
                ];
                ?>
                <div class="books-pair">
                <?php foreach ($pairs as $pair):
                    $bRes = mysqli_query($conn, "SELECT bookId, image, title, description, price, category, userId FROM book WHERE bookId = {$pair['id']}");
                    $b = mysqli_fetch_assoc($bRes);
                ?>
                    <div class="book-card <?php echo $pair['mine'] ? 'my-book' : 'their-book'; ?>">
                        <?php if ($pair['mine']): ?><span class="book-owner-tag my-tag">My Book</span><?php endif; ?>
                        <a href="profile.php?id=<?php echo $b['userId']; ?>">
                            <img src="../assets/images/profile.jpg" alt="Owner" class="owner-avatar">
                        </a>
                        <img src="../assets/images/<?php echo htmlspecialchars($b['image']); ?>" alt="Book" class="book-cover">
                        <h3 class="book-title" dir="rtl"><?php echo htmlspecialchars($b['title']); ?></h3>
                        <p class="book-price"><?php echo $b['price']; ?> SAR</p>
                        <p class="book-category"><?php echo $b['category']; ?></p>
                        <p class="book-description"><?php echo htmlspecialchars($b['description']); ?></p>
                    </div>
                <?php endforeach; ?>
                </div>

                <div class="request-actions">
                    <div class="radio-group">
                        <label class="radio-label"><input type="radio" name="requestStatus" value="Accepted" checked> Accept</label>
                        <label class="radio-label"><input type="radio" name="requestStatus" value="Rejected"> Reject</label>
                    </div>
                    <input type="hidden" name="requestId"          value="<?php echo $row['requestId']; ?>">
                    <input type="hidden" name="senderId"           value="<?php echo $b['userId']; ?>">
                    <input type="hidden" name="bookToExchange"     value="<?php echo $row['bookToExchange']; ?>">
                    <input type="hidden" name="bookToExchangeWith" value="<?php echo $row['bookToExchangeWith']; ?>">
                    <div class="submit-row">
                        <input type="submit" name="submit" value="Submit" class="btn-submit">
                    </div>
                </div>
            </form>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="empty-msg">No exchange requests received.</p>
        <?php endif; ?>
    </div>

    <!-- SENT -->
    <div class="section-container">
        <span class="section-label">Requests Sent</span>
        <?php if (mysqli_num_rows($requestSent) != 0): ?>
            <?php while ($row = mysqli_fetch_assoc($requestSent)): ?>
            <div class="request-card">
                <?php
                // SENT: bookToExchange = sender's book (MINE), bookToExchangeWith = receiver's book (theirs)
                $pairs = [
                    ['id' => (int)$row['bookToExchange'],     'mine' => true],
                    ['id' => (int)$row['bookToExchangeWith'], 'mine' => false],
                ];
                ?>
                <div class="books-pair">
                <?php foreach ($pairs as $pair):
                    $bRes = mysqli_query($conn, "SELECT bookId, image, title, description, price, category, userId FROM book WHERE bookId = {$pair['id']}");
                    $b = mysqli_fetch_assoc($bRes);
                ?>
                    <div class="book-card <?php echo $pair['mine'] ? 'my-book' : 'their-book'; ?>">
                        <?php if ($pair['mine']): ?><span class="book-owner-tag my-tag">My Book</span><?php endif; ?>
                        <a href="profile.php?id=<?php echo $b['userId']; ?>">
                            <img src="../assets/images/profile.jpg" alt="Owner" class="owner-avatar">
                        </a>
                        <img src="../assets/images/<?php echo htmlspecialchars($b['image']); ?>" alt="Book" class="book-cover">
                        <h3 class="book-title" dir="rtl"><?php echo htmlspecialchars($b['title']); ?></h3>
                        <p class="book-price"><?php echo $b['price']; ?> SAR</p>
                        <p class="book-category"><?php echo $b['category']; ?></p>
                        <p class="book-description"><?php echo htmlspecialchars($b['description']); ?></p>
                    </div>
                <?php endforeach; ?>
                </div>
                <div class="request-status-wrap">
                    <span class="status-badge status-<?php echo strtolower($row['status']); ?>"><?php echo $row['status']; ?></span>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="empty-msg">No exchange requests sent.</p>
        <?php endif; ?>
    </div>
</div>

<footer>
    <div class="footer-content">
        <p>&copy; 2025 ShelfTrade. All Rights Reserved.</p>
        <div class="footer-links"><a href="#">Privacy Policy</a><a href="#">Terms of Service</a></div>
    </div>
</footer>

<div id="toast"></div>
<script>
function showToast(msg, type) {
    var t = document.getElementById('toast');
    t.style.cssText = 'position:fixed;top:24px;left:50%;transform:translateX(-50%);z-index:9999;padding:14px 28px;border-radius:8px;font-size:15px;font-weight:500;box-shadow:0 4px 16px rgba(0,0,0,0.15);min-width:260px;text-align:center;opacity:1;transition:opacity 0.4s;background:'+(type==='error'?'#f8d7da':'#d4edda')+';color:'+(type==='error'?'#721c24':'#155724')+';border:1px solid '+(type==='error'?'#f5c6cb':'#c3e6cb')+';';
    t.innerText = msg;
    setTimeout(function(){ t.style.opacity='0'; }, 3000);
}
<?php if (isset($_GET['msg'])): ?>
<?php if ($_GET['msg'] === 'accepted'): ?>
showToast('Request accepted. The book has been added to your cart.', 'success');
<?php elseif ($_GET['msg'] === 'rejected'): ?>
showToast('Request rejected.', 'success');
<?php endif; ?>
<?php endif; ?>
</script>
</body>
</html>
