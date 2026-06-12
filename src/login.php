<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../configs/databaseConnection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $query = "SELECT * FROM user WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['userId'] = $user['userId'];
            $_SESSION['userName'] = $user['fullName'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Incorrect password. Please try again.";
        }
    } else {
        $error = "No account found with that email.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShelfTrade — Log In</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: #f8f6f2;
            color: #2d2d2d;
            min-height: 100vh;
            display: flex;
        }
        .left-panel {
            flex: 1;
            background: linear-gradient(160deg, #ece4d6 0%, #7ad0d1 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 40px;
        }
        .left-panel img.logo {
            max-width: 340px;
            width: 85%;
            height: auto;
            object-fit: contain;
        }
        /* Right panel — form */
        .right-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px 40px;
            background: #fff;
        }
        .welcome-text {
            text-align: center;
            margin-bottom: 32px;
        }
        .welcome-text h2 {
            font-size: 26px;
            font-weight: 600;
            color: #2d2d2d;
            margin-bottom: 6px;
        }
        .welcome-text p {
            font-size: 14px;
            color: #888;
        }
        .form-box {
            width: 100%;
            max-width: 360px;
        }
        .error-msg {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b94040;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 16px;
            text-align: center;
        }
        label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #2d2d2d;
            margin-bottom: 6px;
        }
        input[type="email"], input[type="password"], input[type="text"] {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #ddd3c0;
            border-radius: 8px;
            font-size: 15px;
            color: #2d2d2d;
            background: #f8f6f2;
            outline: none;
            transition: border-color 0.2s, background 0.2s;
            margin-bottom: 16px;
        }
        input:focus { border-color: #4a8b8b; background: #fff; }
        .btn {
            width: 100%;
            padding: 13px;
            background: #4a8b8b;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 4px;
        }
        .btn:hover { background: #3a7070; }
        .form-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #888;
        }
        .form-footer a { color: #c4714a; text-decoration: none; font-weight: 500; }
        .form-footer a:hover { text-decoration: underline; }

        .password-wrap { position: relative; margin-bottom: 16px; }
        .password-wrap input { margin-bottom: 0; width: 100%; padding-right: 44px; }
        .eye-btn { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #aaa; font-size: 18px; padding: 0; line-height: 1; }
        .eye-btn:hover { color: #4a8b8b; }
        @media (max-width: 700px) {
            .left-panel { display: none; }
            .right-panel { padding: 40px 24px; }
        }
    </style>
</head>
<body>
    <div class="left-panel">
        <img src="../assets/images/logo_full.png" alt="ShelfTrade" class="logo">
    </div>
    <div class="right-panel">
        <div class="welcome-text">
            <h2>Welcome back</h2>
            <p>Log in to your account to continue</p>
        </div>
        <div class="form-box">
            <?php if (isset($error)) echo "<div class='error-msg'>$error</div>"; ?>
            <form method="POST" action="">
                <label for="email">Email address</label>
                <input type="email" name="email" id="email" placeholder="you@example.com" required>
                <label for="password">Password</label>
                <div class="password-wrap">
                    <input type="password" name="password" id="password" placeholder="Enter your password" required>
                    <button type="button" class="eye-btn" onclick="togglePwd('password', this)"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
                </div>
                <button type="submit" class="btn">Log In</button>
            </form>
            <div class="form-footer">
                Don't have an account? <a href="signup.php">Sign up</a>
            </div>
        </div>
    </div>


<script>
function togglePwd(inputId, btn) {
    var inp = document.getElementById(inputId);
    var eyeOpen = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
    var eyeClosed = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';
    if (inp.type === 'password') {
        inp.type = 'text';
        btn.innerHTML = eyeClosed;
    } else {
        inp.type = 'password';
        btn.innerHTML = eyeOpen;
    }
}
</script>
</body>
</html>
