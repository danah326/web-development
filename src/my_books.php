<?php

include '../configs/databaseConnection.php';


session_start();
if (!isset($_SESSION['userId'])) { header("Location: login.php"); exit(); }
$userId = $_SESSION['userId']; // Default to 2 for testing

/*** Delete book and its image if 'delete' is in URL ***/
if (isset($_GET['delete'])) {
    $deleteId = $_GET['delete'];

    /*** Get image name from DB ***/
    $stmt = $conn->prepare("SELECT image FROM book WHERE bookId = ? AND userId = ?");
    $stmt->bind_param("ii", $deleteId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $image = $row['image'];
        $imagePath = "../assets/images/$image";

        /*** Delete image file if exists ***/
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }

    /*** Delete book record from Our DB ***/
    $stmt = $conn->prepare("DELETE FROM book WHERE bookId = ? AND userId = ?");
    $stmt->bind_param("ii", $deleteId, $userId);
    $stmt->execute();

    /*** Refresh page after deletion ***/
    header("Location: my_books.php?deleted=1");
    exit();
}


$stmt = $conn->prepare("SELECT * FROM book WHERE userId = ? ORDER BY bookId DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$books = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Books</title>
  <link rel="stylesheet" href="../assets/css/header.css">
    <link rel="stylesheet" href="../assets/css/my_books.css">

</head>
<body>



  <!-- *** Book List *** -->
  <header>
        <div class="logo"><img src="../assets/images/logo_short.png" alt="ShelfTrade Logo"></div>
        <nav>
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="my_books.php" class="active">My Books</a></li>
                <li><a href="exchange_requests.php">Exchange Requests</a></li>
            <li><a href="logout.php">Logout</a></li>
            <li><a href="cart.php" class="to-cart">&#128722;</a></li>
        </ul>
        </nav>
    </header>

  <div class="container">
    <div class="container-header">
        <h2>My Added Books</h2>
        <a href="add_book.php" class="add-book-btn">+ Add New Book</a>
    </div>
    <div id="booksList" class="books-list">

      <?php if (count($books) > 0): ?>
        <?php foreach ($books as $book): ?>
          <div class="book-item">
            <img src="../assets/images/<?= htmlspecialchars($book['image']) ?>" alt="<?= htmlspecialchars($book['title']) ?>">
            <div class="book-details">
              <?php if ($book['bookStatus'] === 'Out of stock'): ?>
                <p class="status-text">Out of stock — cannot be edited.</p>
              <?php endif; ?>
              <div class="book-title"><?= htmlspecialchars($book['title']) ?></div>
              <div class="book-description"><?= htmlspecialchars($book['description']) ?></div>
              <div class="book-price"><?= htmlspecialchars($book['price']) ?> SAR</div>
            </div>
            <div class="buttons">
              <?php if ($book['bookStatus'] !== 'Out of stock'): ?>
                <a href="add_book.php?id=<?= $book['bookId'] ?>" class="edit-btn">Edit</a>
              <?php endif; ?>
              <a href="my_books.php?delete=<?= $book['bookId'] ?>" class="delete-btn" onclick="return confirmDelete(this.href);">Delete</a>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p style="margin-top:20px;">You haven't added any books yet.</p>
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
    t.innerText = msg; setTimeout(function(){ t.style.opacity='0'; }, 3000);
}
function confirmDelete(href) {
    if (confirm('Are you sure you want to delete this book?')) { window.location.href = href; }
    return false;
}
<?php if (isset($_GET['deleted'])): ?>showToast('Book deleted successfully.', 'success');<?php endif; ?>
<?php if (isset($_GET['updated'])): ?>showToast('Book updated successfully.', 'success');<?php endif; ?>
<?php if (isset($_GET['added'])): ?>showToast('Book added successfully.', 'success');<?php endif; ?>
</script>
</body>
</html>
