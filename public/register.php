<?php
session_start();
require __DIR__.'/config/database.php';
if(isset($_SESSION['user_id'])){ header('Location: dashboard.php'); exit; }

$msg = '';
$msg_type = '';

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    if(empty($full_name) || empty($username) || empty($email) || empty($password)){
        $msg = "⚠️ Please fill all fields";
        $msg_type = 'error';
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $msg = "⚠️ Invalid Email Format";
        $msg_type = 'error';
    } elseif(strlen($password) < 6){
        $msg = "⚠️ Password must be 6+ characters";
        $msg_type = 'error';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email OR username = :username");
            $stmt->execute(['email' => $email, 'username' => $username]);
            
            if($stmt->fetch()){
                $msg = "⚠️ Email or Username already taken";
                $msg_type = 'error';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $sql = "INSERT INTO users (username, full_name, email, password) VALUES (:username, :full_name, :email, :password)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'username' => $username,
                    'full_name' => $full_name,
                    'email' => $email,
                    'password' => $hashed_password
                ]);

                $pdo->prepare("INSERT INTO expense_users (username, full_name, email, password) VALUES (?, ?, ?, ?)")
                    ->execute([$username, $full_name, $email, $hashed_password]);

                $msg = "✅ Account Created Successfully! Redirecting to Login...";
                $msg_type = 'success';
                header("refresh:2;url=login.php");
            }
        } catch(PDOException $e){
            $msg = "Database Error: " . $e->getMessage();
            $msg_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register | Student Expense Tracker</title>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#000; 
  --card:#0a0a0a;
  --ink:#e0e0e0; 
  --muted:#7a7a7a;
  --neon:#00d4ff;        /* Neon Blue */
  --neon-dark:#0099cc;   
  --neon-glow:rgba(0,212,255,0.5);
  --line:#1f1f1f;
}
*{box-sizing:border-box;margin:0;padding:0;font-family:'Outfit',system-ui,sans-serif}
body{
  background:var(--bg);
  color:var(--ink);
  min-height:100vh;
  display:grid;
  place-items:center;
  overflow:hidden;
  position:relative;
}

/* Animated neon grid background */
body::before{
  content:'';
  position:absolute;
  top:0;left:0;width:100%;height:100%;
  background:
    linear-gradient(rgba(0,212,255,0.07) 1px, transparent 1px),
    linear-gradient(90deg, rgba(0,212,255,0.07) 1px, transparent 1px);
  background-size:50px 50px;
  animation:gridMove 20s linear infinite;
}
@keyframes gridMove{
 0%{transform:translate(0,0)}
 100%{transform:translate(50px,50px)}
}

.wrap{width:100%;max-width:440px;padding:20px;z-index:2;position:relative}
.card{
  background:var(--card);
  border:1px solid var(--neon);
  border-radius:16px;
  padding:40px 35px;
  box-shadow:0 0 30px var(--neon-glow), inset 0 0 20px rgba(0,212,255,0.05);
  backdrop-filter:blur(10px);
}
.logo{
  text-align:center;
  font-family:'Orbitron',sans-serif;
  font-size:32px;
  font-weight:900;
  color:var(--neon);
  text-shadow:0 0 10px var(--neon), 0 0 20px var(--neon);
  margin-bottom:8px;
  letter-spacing:2px;
}
.card h2{font-size:22px;font-weight:700;margin-bottom:4px;text-align:center;color:var(--ink)}
.sub{font-size:12px;color:var(--muted);margin-bottom:25px;text-align:center}

.lab{display:block;font-size:11px;font-weight:600;color:var(--neon);margin:14px 0 6px;text-transform:uppercase;letter-spacing:1px}
.inp{
  width:100%;padding:14px 15px;background:#111;
  border:1.5px solid var(--line);
  border-radius:8px;color:var(--ink);font-size:14px;outline:none;
  transition:0.3s
}
.inp:focus{
  border-color:var(--neon);
  background:#151515;
  box-shadow:0 0 0 3px var(--neon-glow), 0 0 15px var(--neon-glow);
}
.inp::placeholder{color:#444}

.btn{
  width:100%;margin-top:22px;padding:15px;
  background:transparent;
  color:var(--neon);
  border:2px solid var(--neon);
  border-radius:8px;font-weight:700;font-size:15px;cursor:pointer;
  transition:0.3s;font-family:'Orbitron',sans-serif;
  letter-spacing:1px;
  text-transform:uppercase;
}
.btn:hover{
  background:var(--neon);
  color:#000;
  box-shadow:0 0 20px var(--neon), 0 0 40px var(--neon);
  transform:translateY(-2px);
}

.divider{margin:20px 0;display:flex;align-items:center;gap:10px;color:var(--muted);font-size:11px}
.divider::before,.divider::after{content:'';flex:1;height:1px;background:var(--line)}
.footer{text-align:center;font-size:13px;color:var(--muted)}
.footer a{color:var(--neon);text-decoration:none;font-weight:700;text-shadow:0 0 5px var(--neon)}
.footer a:hover{text-decoration:underline}

.alert{padding:12px 14px;border-radius:8px;margin-bottom:18px;font-size:12px;font-weight:600;text-align:center}
.alert.error{background:rgba(255,0,100,0.15);border:1px solid #ff0066;color:#ff4d94;box-shadow:0 0 10px rgba(255,0,100,0.3)}
.alert.success{background:rgba(0,212,255,0.15);border:1px solid var(--neon);color:var(--neon);box-shadow:0 0 10px var(--neon-glow)}
</style>
</head>
<body>

<div class="wrap">
  <div class="card">
    <div class="logo">S-EXPENSE</div>
    <h2>Create Account</h2>
    <div class="sub">Join the future of expense tracking</div>

    <?php if($msg): ?>
        <div class="alert <?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
        <label class="lab">Full Name</label>
        <input type="text" name="full_name" class="inp" placeholder="Ammu Kannan" required>

        <label class="lab">Username</label>
        <input type="text" name="username" class="inp" placeholder="ammu_k" required>

        <label class="lab">Email Address</label>
        <input type="email" name="email" class="inp" placeholder="you@gmail.com" required>

        <label class="lab">Password</label>
        <input type="password" name="password" class="inp" placeholder="Minimum 6 characters" required>

        <button type="submit" class="btn">Create Account</button>
    </form>

    <div class="divider">or</div>
    <div class="footer">
        Already have an account? <a href="login.php">Login here</a>
    </div>
  </div>
</div>

</body>
</html>