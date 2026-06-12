<?php
include '../configs/databaseConnection.php';
session_start();

if (!isset($_SESSION['userId'])) { header("Location: login.php"); exit(); }

$userId = $_SESSION['userId'];

$bookSql = "SELECT bookId, image, title, description, price, category, userId
            FROM book WHERE bookStatus = 'In stock' AND userId != $userId";
$books = mysqli_query($conn, $bookSql);

$usernameSql = "SELECT fullName FROM user WHERE userId = $userId";
$usernameResult = mysqli_query($conn, $usernameSql);
$username = mysqli_fetch_assoc($usernameResult);
$fullName = $username['fullName'];
$firstName = explode(" ", $fullName)[0];

$cartSql = "SELECT cartId FROM usercart WHERE userId = $userId";
$cartResult = mysqli_query($conn, $cartSql);
$cartSet = mysqli_fetch_assoc($cartResult);
$cart = $cartSet['cartId'];

$bookId = isset($_GET['bookId']) ? (int)$_GET['bookId'] : null;
if ($bookId !== null && !isset($_SESSION['book_added_' . $bookId])) {
    $buySql = "UPDATE book SET cartId = $cart, bookStatus = 'Out of stock' WHERE bookId = $bookId";
    mysqli_query($conn, $buySql);
    $_SESSION['book_added_' . $bookId] = true;
    header("Location: dashboard.php?bought=1");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — ShelfTrade</title>
    <link rel="stylesheet" href="../assets/css/header.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>

<header>
    <div class="logo"><img src="../assets/images/logo_short.png" alt="ShelfTrade Logo"></div>
    <nav>
        <ul>
            <li><a href="dashboard.php" class="active">Dashboard</a></li>
            <li><a href="my_books.php">My Books</a></li>
            <li><a href="exchange_requests.php">Exchange Requests</a></li>
            <li><a href="logout.php">Logout</a></li>
            <li><a href="cart.php" class="to-cart">&#128722;</a></li>
        </ul>
    </nav>
</header>

<!-- Welcome banner -->
<div class="welcome-bar">
    <div class="welcome-inner">
        <div class="welcome-text">
            <span class="welcome-greeting">Welcome back, <strong><?php echo htmlspecialchars($firstName); ?></strong></span>
        </div>
    </div>
</div>

<!-- Carousel -->
<div class="carousel">
    <div class="carousel-track" id="carouselTrack">
        <div class="carousel-slide" style="background: linear-gradient(135deg, #7ad0d1 0%, #ece4d6 100%);">
            <div class="slide-content">
                <h2>Find Books You Love</h2>
                <p>Trade with readers around you and give your shelf a fresh start.</p>
            </div>
        </div>
        <div class="carousel-slide" style="background: linear-gradient(135deg, #ece4d6 0%, #7ad0d1 100%);">
            <div class="slide-content">
                <h2>More Features Coming</h2>
                <p>Wishlists, reading clubs, and more are on the way in the next release.</p>
            </div>
        </div>
    </div>
    <button class="carousel-btn prev" onclick="moveCarousel(-1)">&#8249;</button>
    <button class="carousel-btn next" onclick="moveCarousel(1)">&#8250;</button>
    <div class="carousel-dots" id="carouselDots"></div>
</div>

<div class="container" id="books">
    <div class="Search-Filter">
        <input type="text" class="search-bar" placeholder="Search for a book...">
        <select class="filter">
            <option value="all">All Categories</option>
            <option value="literature">Literature</option>
            <option value="history">History</option>
            <option value="novels">Novels</option>
            <option value="philosophy">Philosophy</option>
        </select>
    </div>

    <main class="books-container">
        <?php while ($row = mysqli_fetch_assoc($books)) { ?>
        <div class="book-card" data-category="<?php echo strtolower($row['category']); ?>">
            <div class="card-owner">
                <a href="profile.php?id=<?php echo $row['userId']; ?>">
                    <img src="../assets/images/profile.jpg" alt="Owner" class="owner-avatar">
                </a>
            </div>
            <img src="../assets/images/<?php echo htmlspecialchars($row['image']); ?>" alt="Book cover" class="book-cover">
            <h3 class="book-title" dir="rtl"><?php echo htmlspecialchars($row['title']); ?></h3>
            <p class="book-price"><?php echo $row['price']; ?> SAR</p>
            <p class="book-category">Category: <?php echo $row['category']; ?></p>
            <p class="book-description"><?php echo htmlspecialchars($row['description']); ?></p>
            <div class="card-actions">
                <a href="dashboard.php?bookId=<?php echo $row['bookId']; ?>" class="btn-buy">Buy</a>
                <a href="send_request.php?bookOwnerId=<?php echo $row['userId']; ?>&bookId=<?php echo $row['bookId']; ?>" class="btn-exchange">Request Exchange</a>
            </div>
        </div>
        <?php } ?>
    </main>
</div>

<footer>
    <div class="footer-content">
        <p>&copy; 2025 ShelfTrade. All Rights Reserved.</p>
        <div class="footer-links"><a href="#">Privacy Policy</a><a href="#">Terms of Service</a></div>
    </div>
</footer>

<!-- Toast -->
<div id="toast"></div>

<script>
function showToast(msg, type) {
    var t = document.getElementById('toast');
    t.style.cssText = 'position:fixed;top:24px;left:50%;transform:translateX(-50%);z-index:9999;padding:14px 28px;border-radius:8px;font-size:15px;font-weight:500;box-shadow:0 4px 16px rgba(0,0,0,0.15);min-width:260px;text-align:center;transition:opacity 0.4s;background:'+(type==='error'?'#f8d7da':'#d4edda')+';color:'+(type==='error'?'#721c24':'#155724')+';border:1px solid '+(type==='error'?'#f5c6cb':'#c3e6cb')+';';
    t.innerText = msg;
    t.style.opacity = '1';
    setTimeout(function(){ t.style.opacity='0'; }, 3000);
}

// Carousel
var current = 0;
var slides = document.querySelectorAll('.carousel-slide');
var dotsContainer = document.getElementById('carouselDots');
slides.forEach(function(_, i) {
    var d = document.createElement('span');
    d.className = 'dot' + (i===0?' active':'');
    d.onclick = function(){ goTo(i); };
    dotsContainer.appendChild(d);
});
function goTo(n) {
    current = (n + slides.length) % slides.length;
    document.getElementById('carouselTrack').style.transform = 'translateX(-' + (current * 100) + '%)';
    document.querySelectorAll('.dot').forEach(function(d,i){ d.className = 'dot'+(i===current?' active':''); });
}
function moveCarousel(dir) { goTo(current + dir); }
setInterval(function(){ goTo(current + 1); }, 5000);

<?php if (isset($_GET['bought'])): ?>
showToast('Book added to your cart successfully!', 'success');
<?php endif; ?>
<?php if (isset($_GET['sent'])): ?>
showToast('Exchange request sent successfully!', 'success');
<?php endif; ?>
// Show cross-page toast from sessionStorage
var pending = sessionStorage.getItem('toast');
if (pending) { var d = JSON.parse(pending); showToast(d.msg, d.type); sessionStorage.removeItem('toast'); }

// Search + filter
document.addEventListener("DOMContentLoaded", function () {
    var searchBar = document.querySelector(".search-bar");
    var filterSelect = document.querySelector(".filter");
    var cards = document.querySelectorAll(".book-card");

    function filterBooks() {
        var q = searchBar.value.toLowerCase();
        var cat = filterSelect.value.toLowerCase();
        var any = false;
        cards.forEach(function(card) {
            var title = card.querySelector(".book-title").textContent.toLowerCase();
            var desc = card.querySelector(".book-description").textContent.toLowerCase();
            var cardCat = card.dataset.category;
            var match = (title.includes(q) || desc.includes(q)) && (cat === "all" || cardCat === cat);
            card.style.display = match ? "" : "none";
            if (match) any = true;
        });
        var nr = document.querySelector(".no-results");
        if (!any) {
            if (!nr) { var p = document.createElement("p"); p.className="no-results"; p.textContent="No books found."; p.style.cssText="color:#888;font-size:16px;grid-column:1/-1;text-align:center;padding:40px 0;"; document.querySelector(".books-container").appendChild(p); }
        } else { if (nr) nr.remove(); }
    }
    searchBar.addEventListener("keyup", filterBooks);
    filterSelect.addEventListener("change", filterBooks);
});
</script>
</body>
</html>
