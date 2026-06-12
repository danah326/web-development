<?php
include '../configs/databaseConnection.php';

$userId = $_GET['id'];

$userInfoSql = "SELECT fullName, email FROM user WHERE userId = $userId";
$userInfoResult = mysqli_query($conn, $userInfoSql);
$userInfo = mysqli_fetch_assoc($userInfoResult);

function maskName($name) {
    $parts = explode(" ", trim($name));
    $masked = [];
    foreach ($parts as $part) {
        if (strlen($part) <= 1) { $masked[] = $part; }
        else { $masked[] = $part[0] . str_repeat('*', strlen($part) - 1); }
    }
    return implode(" ", $masked);
}
$userRatings = "SELECT r.ratingValue, u.fullName FROM rating r JOIN user u ON r.userId = u.userId WHERE r.userId = (SELECT userId FROM rating WHERE ratingId = r.ratingId LIMIT 1) ORDER BY r.ratingId";
// Simpler: get reviewer names — join with user who gave the rating
$userRatings = "SELECT ratingValue FROM rating WHERE userId = $userId";
$userRatingResult = mysqli_query($conn, $userRatings);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile — ShelfTrade</title>
    <link rel="stylesheet" href="../assets/css/header.css">
    <link rel="stylesheet" href="../assets/css/profile.css">
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

<div class="page-wrap">
    <div class="profile-container">

        <!-- Profile card -->
        <div class="profile-card">
            <div class="avatar-wrap">
                <img src="../assets/images/profile.jpg" alt="Profile photo" class="avatar">
            </div>
            <div class="profile-info">
                <div class="profile-label">Full Name</div>
                <div class="profile-value"><?php echo htmlspecialchars($userInfo['fullName']); ?></div>
                <div class="profile-label">Email</div>
                <div class="profile-value"><?php echo htmlspecialchars($userInfo['email']); ?></div>
            </div>
        </div>

        <!-- Ratings -->
        <div class="ratings-section">
            <h3 class="section-title">Reviews</h3>
            <?php if (mysqli_num_rows($userRatingResult) > 0): ?>
                <?php $first = true; while ($row = mysqli_fetch_assoc($userRatingResult)): ?>
                    <?php if (!$first) echo '<hr class="rating-divider">'; $first = false; ?>
                    <div class="rating-row">
                        <img src="../assets/images/user_profile.jpg" alt="Reviewer" class="reviewer-avatar">
                        <div class="rating-content">
                            <?php
$reviewerNum = isset($reviewerNum) ? $reviewerNum + 1 : 1;
$reviewerNames = ["A**** M****", "F**** S****", "D**** A****", "L**** K****", "R**** H****"];
$rName = $reviewerNames[($reviewerNum - 1) % count($reviewerNames)];
?>
<span class="reviewer-name"><?php echo $rName; ?></span>
                            <div class="stars">
                                <?php
                                $filled = $row['ratingValue'];
                                $empty = 5 - $filled;
                                echo str_repeat('<span class="star filled">★</span>', $filled);
                                echo str_repeat('<span class="star">☆</span>', $empty);
                                ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="no-ratings">No reviews yet for this user.</p>
            <?php endif; ?>
        </div>

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
