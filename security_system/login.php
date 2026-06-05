<?php
// =====================================================
// LOGIN PAGE - login.php
// Step 1: Start session
// Step 2: If already logged in, redirect to dashboard
// Step 3: Handle login form POST
// Step 4: Show login UI
// =====================================================
session_start();

// Step 2: Already logged in? Go straight to dashboard
if (isset($_SESSION['LoggedIn']) && $_SESSION['LoggedIn'] === true) {
    header("Location: dashboard.php");
    exit;
}

require_once 'includes/db_connect.php';

$ErrorMsg = '';

// Step 3: Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $Username = trim($_POST['Username'] ?? '');
    $Password = $_POST['Password'] ?? '';

    if ($Username && $Password) {
        try {
            $db   = getConnection();
            $stmt = $db->prepare("SELECT * FROM AdminUsers WHERE Username = ? LIMIT 1");
            $stmt->execute([$Username]);
            $User = $stmt->fetch();

            // Verify password against stored hash
            if ($User && $Password === $User['PasswordHash']) {
                $_SESSION['LoggedIn']  = true;
                $_SESSION['Username']  = $User['Username'];
                $_SESSION['FullName']  = $User['FullName'];
                header("Location: dashboard.php");
                exit;
            } else {
                $ErrorMsg = "Invalid username or password.";
            }
        } catch (PDOException $e) {
            $ErrorMsg = "Database error. Please try again.";
        }
    } else {
        $ErrorMsg = "Please fill in both fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | FAST SecureForce</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Exo+2:wght@300;400;500;600;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html, body {
            height: 100%;
            font-family: 'Exo 2', sans-serif;
            background: #03091c;
            color: #e8f4ff;
            overflow: hidden;
        }

        /* Animated background layers */
        .BgLayer {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
        }

        .BgGlow {
            background:
                radial-gradient(ellipse 70% 60% at 15% 20%, rgba(0, 80, 180, 0.18) 0%, transparent 60%),
                radial-gradient(ellipse 50% 50% at 85% 75%, rgba(0, 210, 255, 0.10) 0%, transparent 55%),
                radial-gradient(ellipse 40% 40% at 50% 50%, rgba(255, 107, 0, 0.05) 0%, transparent 70%);
        }

        .BgGrid {
            background-image:
                linear-gradient(rgba(0, 210, 255, 0.028) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 210, 255, 0.028) 1px, transparent 1px);
            background-size: 40px 40px;
        }

        /* Center wrapper */
        .LoginPage {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        /* Glass card */
        .LoginCard {
            width: 100%;
            max-width: 420px;
            background: rgba(7, 19, 48, 0.72);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(0, 210, 255, 0.18);
            border-radius: 18px;
            box-shadow:
                0 24px 64px rgba(0, 0, 0, 0.6),
                0 0 0 1px rgba(0, 210, 255, 0.05),
                0 0 40px rgba(0, 210, 255, 0.08);
            overflow: hidden;
            animation: CardIn 0.55s cubic-bezier(.22,.9,.36,1) both;
        }

        @keyframes CardIn {
            from { opacity: 0; transform: translateY(28px) scale(0.96); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* Top accent line */
        .CardAccent {
            height: 2px;
            background: linear-gradient(90deg, transparent 0%, #00d2ff 40%, #ff6b00 70%, transparent 100%);
        }

        /* Header section */
        .CardHeader {
            padding: 32px 36px 24px;
            text-align: center;
            border-bottom: 1px solid rgba(0, 210, 255, 0.08);
        }

        /* Logo */
        .LogoBox {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #ff6b00, #b34400);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 800;
            color: #fff;
            letter-spacing: 0.5px;
            margin: 0 auto 18px;
            box-shadow: 0 0 24px rgba(255, 107, 0, 0.4), 0 0 0 1px rgba(255,107,0,0.3);
        }

        /* If logo image is present */
        .LogoImg {
            width: 64px;
            height: 64px;
            object-fit: contain;
            margin: 0 auto 18px;
            display: block;
            filter: drop-shadow(0 0 10px rgba(0, 210, 255, 0.4));
        }

        .FirmName {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: #e8f4ff;
            margin-bottom: 4px;
        }

        .FirmSub {
            font-size: 10px;
            color: rgba(0, 210, 255, 0.7);
            letter-spacing: 2.5px;
            text-transform: uppercase;
            font-family: 'Space Mono', monospace;
        }

        /* Body section */
        .CardBody {
            padding: 28px 36px 32px;
        }

        .LoginHeading {
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 1px;
            color: rgba(180, 210, 240, 0.6);
            text-transform: uppercase;
            margin-bottom: 22px;
            text-align: center;
        }

        /* Error alert */
        .AlertDanger {
            background: rgba(255, 23, 68, 0.08);
            border: 1px solid rgba(255, 23, 68, 0.25);
            color: #ff6080;
            padding: 11px 14px;
            border-radius: 8px;
            font-size: 12.5px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
        }

        /* Form fields */
        .FormGroup {
            margin-bottom: 16px;
        }

        .FormLabel {
            display: block;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1.8px;
            text-transform: uppercase;
            color: rgba(0, 210, 255, 0.65);
            margin-bottom: 7px;
        }

        .FormControl {
            width: 100%;
            background: rgba(0, 10, 30, 0.65);
            border: 1px solid rgba(0, 210, 255, 0.15);
            border-radius: 9px;
            color: #e8f4ff;
            padding: 12px 16px;
            font-size: 14px;
            font-family: 'Exo 2', sans-serif;
            transition: 0.18s ease;
            outline: none;
        }

        .FormControl:focus {
            border-color: #00d2ff;
            box-shadow: 0 0 0 3px rgba(0, 210, 255, 0.1), 0 0 16px rgba(0, 210, 255, 0.08);
            background: rgba(0, 15, 40, 0.85);
        }

        .FormControl::placeholder {
            color: rgba(100, 150, 190, 0.4);
        }

        /* Login button */
        .BtnLogin {
            width: 100%;
            background: linear-gradient(135deg, #ff6b00, #cc5000);
            color: #fff;
            border: 1px solid rgba(255, 107, 0, 0.5);
            padding: 13px;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 700;
            font-family: 'Exo 2', sans-serif;
            cursor: pointer;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-top: 24px;
            transition: 0.18s ease;
            box-shadow: 0 4px 18px rgba(255, 107, 0, 0.3);
        }

        .BtnLogin:hover {
            background: linear-gradient(135deg, #ff8c3a, #ff6b00);
            box-shadow: 0 6px 24px rgba(255, 107, 0, 0.45);
            transform: translateY(-1px);
        }

        .BtnLogin:active {
            transform: translateY(0);
        }

        /* Footer hint */
        .CardFooter {
            padding: 14px 36px 18px;
            text-align: center;
            border-top: 1px solid rgba(0, 210, 255, 0.06);
            font-size: 10px;
            color: rgba(100, 150, 190, 0.4);
            font-family: 'Space Mono', monospace;
            letter-spacing: 0.5px;
        }

        /* Live status dot */
        .StatusRow {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            margin-top: 18px;
        }

        .StatusDot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #00d2ff;
            box-shadow: 0 0 6px #00d2ff;
            animation: Pulse 2s infinite;
        }

        @keyframes Pulse {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0.3; }
        }

        .StatusText {
            font-size: 10px;
            color: rgba(0, 210, 255, 0.55);
            font-family: 'Space Mono', monospace;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>

<div class="BgLayer BgGlow"></div>
<div class="BgLayer BgGrid"></div>

<div class="LoginPage">
    <div class="LoginCard">

        <div class="CardAccent"></div>

        <div class="CardHeader">
            <?php if (file_exists('logo.png')): ?>
                <img src="logo.png" alt="FAST Logo" class="LogoImg">
            <?php elseif (file_exists('logo.jpg')): ?>
                <img src="logo.jpg" alt="FAST Logo" class="LogoImg">
            <?php else: ?>
                <div class="LogoBox">FAST</div>
            <?php endif; ?>

            <div class="FirmName">SecureForce</div>
            <div class="FirmSub">FAST Management System</div>
        </div>

        <div class="CardBody">

            <div class="LoginHeading">System Access</div>

            <?php if ($ErrorMsg): ?>
                <div class="AlertDanger">⚠ <?= htmlspecialchars($ErrorMsg) ?></div>
            <?php endif; ?>

            <form method="POST" action="login.php">

                <div class="FormGroup">
                    <label class="FormLabel" for="Username">Username</label>
                    <input
                        type="text"
                        id="Username"
                        name="Username"
                        class="FormControl"
                        placeholder="Enter your username"
                        autocomplete="username"
                        value="<?= htmlspecialchars($_POST['Username'] ?? '') ?>"
                        required
                    >
                </div>

                <div class="FormGroup">
                    <label class="FormLabel" for="Password">Password</label>
                    <input
                        type="password"
                        id="Password"
                        name="Password"
                        class="FormControl"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <button type="submit" class="BtnLogin">🔐 Login to System</button>

            </form>

            <div class="StatusRow">
                <div class="StatusDot"></div>
                <div class="StatusText">System Online</div>
            </div>

        </div>

        <div class="CardFooter">
            © 2025 SecureForce · FAST Academic Project
        </div>

    </div>
</div>

</body>
</html>
