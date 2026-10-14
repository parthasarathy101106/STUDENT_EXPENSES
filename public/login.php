<?php
session_start();
require __DIR__.'/config/database.php';
if(isset($_SESSION['user_id'])){ header('Location: dashboard.php'); exit; }

$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 $email=trim($_POST['email']??'');
 $pass=$_POST['password']??'';
 if($email && $pass){
  $u = function_exists('getUserByEmail')? getUserByEmail($email) : null;
  if(!$u){
    try{
      global $pdo;
      if(isset($pdo)){
        $stmt=$pdo->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
        $stmt->execute([$email]);
        $u=$stmt->fetch(PDO::FETCH_ASSOC);
      }
    }catch(Exception $e){}
  }
  if($u && password_verify($pass,$u['password'])){
   session_regenerate_id(true);
   $_SESSION['user_id']=$u['id'];
   $_SESSION['user_name']=$u['name']??$u['username']??explode('@',$u['email'])[0];
   header('Location: dashboard.php'); exit;
  } else {
    $error="Invalid email or password. Please try again.";
  }
 } else {
   $error="Please fill in all fields.";
 }
}
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Log in - Student Expense Tracker</title>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#000;--card:#0a0a0a;--ink:#e0e0e0;--muted:#7a7a7a;
  --neon:#00d4ff;--neon-glow:rgba(0,212,255,0.5);--line:#1f1f1f;--error:#ff0066;
}
*{box-sizing:border-box;margin:0;padding:0;font-family:'Outfit',system-ui,sans-serif}
body{
  background:var(--bg);color:var(--ink);min-height:100vh;display:grid;place-items:center;
  overflow:hidden;position:relative;
}

/* 1. ANIMATED NEON GRID BACKGROUND */
body::before{
  content:'';position:absolute;top:0;left:0;width:200%;height:200%;
  background:
    linear-gradient(rgba(0,212,255,0.05) 1px, transparent 1px),
    linear-gradient(90deg, rgba(0,212,255,0.05) 1px, transparent 1px);
  background-size:60px 60px;
  animation:gridMove 20s linear infinite;
}
@keyframes gridMove{0%{transform:translate(0,0)}100%{transform:translate(-60px,-60px)}}

/* 2. FLOATING ORBS */
.orb{
  position:absolute;border-radius:50%;filter:blur(80px);
  background:radial-gradient(circle,var(--neon),transparent);
  opacity:0.3;animation:floatOrb 15s infinite ease-in-out;
}
.orb1{width:400px;height:400px;top:-100px;left:-100px;animation-delay:0s}
.orb2{width:300px;height:300px;bottom:-100px;right:-100px;animation-delay:5s}
@keyframes floatOrb{
 0%,100%{transform:translate(0,0) scale(1)}
 50%{transform:translate(30px,30px) scale(1.1)}
}

.wrap{width:100%;max-width:420px;padding:20px;z-index:2;position:relative;animation:fadeInUp 0.8s ease-out}
@keyframes fadeInUp{from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:translateY(0)}}

.card{
  background:var(--card);border:1px solid var(--neon);border-radius:18px;padding:40px 35px;
  box-shadow:0 0 40px var(--neon-glow), inset 0 0 30px rgba(0,212,255,0.05);
  backdrop-filter:blur(15px);animation:glowPulse 3s ease-in-out infinite;
}
@keyframes glowPulse{
 0%,100%{box-shadow:0 0 40px var(--neon-glow), inset 0 0 30px rgba(0,212,255,0.05)}
 50%{box-shadow:0 0 60px var(--neon-glow), inset 0 0 40px rgba(0,212,255,0.1)}
}

.logo{
  text-align:center;font-family:'Orbitron',sans-serif;font-size:28px;font-weight:900;
  color:var(--neon);text-shadow:0 0 10px var(--neon), 0 0 20px var(--neon);
  margin-bottom:6px;letter-spacing:2px;animation:flicker 2s infinite;
}
@keyframes flicker{0%,100%{opacity:1}50%{opacity:0.8}}

.card h2{font-size:24px;font-weight:700;margin-bottom:4px;text-align:center;color:var(--ink)}
.sub{font-size:12px;color:var(--muted);margin-bottom:25px;text-align:center}

.lab{display:block;font-size:11px;font-weight:600;color:var(--neon);margin:16px 0 8px;text-transform:uppercase;letter-spacing:1px}

/* 3. INPUT TYPING ANIMATION EFFECT */
.inp{
  width:100%;padding:14px 16px;background:#111;border:1.5px solid var(--line);
  border-radius:10px;color:var(--ink);font-size:14px;outline:none;
  transition:0.3s cubic-bezier(0.4,0,0.2,1)
}
.inp:focus{
  border-color:var(--neon);background:#151515;
  box-shadow:0 0 0 3px var(--neon-glow), 0 0 20px var(--neon-glow);
  transform:scale(1.02) translateY(-2px);
}
.inp::placeholder{color:#444;transition:0.3s}
.inp:focus::placeholder{color:var(--neon);transform:translateX(5px)}

.pass-wrap{position:relative}
.eye{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--neon);font-size:18px;width:32px;height:32px;display:grid;place-items:center;border-radius:8px;transition:0.3s}
.eye:hover{background:rgba(0,212,255,0.1);text-shadow:0 0 10px var(--neon);transform:translateY(-50%) scale(1.2)}

/* 4. BUTTON SHINE + RIPPLE ANIMATION */
.btn{
  width:100%;margin-top:24px;padding:15px;background:transparent;color:var(--neon);
  border:2px solid var(--neon);border-radius:10px;font-weight:700;font-size:15px;cursor:pointer;
  transition:0.3s;font-family:'Orbitron',sans-serif;letter-spacing:1px;text-transform:uppercase;
  position:relative;overflow:hidden;
}
.btn::before{ /* Shine effect */
  content:'';position:absolute;top:0;left:-100%;width:100%;height:100%;
  background:linear-gradient(90deg,transparent,rgba(0,212,255,0.4),transparent);
  transition:0.6s;
}
.btn:hover::before{left:100%}
.btn:hover{
  background:var(--neon);color:#000;
  box-shadow:0 0 25px var(--neon), 0 0 50px var(--neon);
  transform:translateY(-3px);
}
.btn:active{transform:scale(0.97)} /* Click ripple */

.div{margin:22px 0;display:flex;align-items:center;gap:12px;color:var(--muted);font-size:12px}
.div::before,.div::after{content:'';flex:1;height:1px;background:var(--line)}
.foot{text-align:center;font-size:13px;color:var(--muted)}
.foot a{color:var(--neon);text-decoration:none;font-weight:700;text-shadow:0 0 5px var(--neon);transition:0.3s}
.foot a:hover{text-decoration:underline;letter-spacing:0.5px}

/* 5. ERROR SHAKE ANIMATION */
.error{
  background:rgba(255,0,100,0.15);border:1px solid var(--error);color:#ff4d94;
  padding:12px 14px;border-radius:10px;margin-bottom:18px;font-size:13px;font-weight:600;
  animation:shake 0.5s;box-shadow:0 0 15px rgba(255,0,100,0.3)
}
@keyframes shake{
 0%,100%{transform:translateX(0)}
 10%,30%,50%,70%,90%{transform:translateX(-5px)}
 20%,40%,60%,80%{transform:translateX(5px)}
}

@media(max-width:900px){.wrap{padding:15px}.card{padding:30px 25px}}
</style>
</head><body>

<!-- Floating Orbs -->
<div class="orb orb1"></div>
<div class="orb orb2"></div>

<div class="wrap">
  <div class="card">
    <div class="logo">S-EXPENSE</div>
    <h2>Welcome Back</h2>
    <div class="sub">Sign in to access your dashboard</div>

    <?php if($error):?><div class="error"><?=htmlspecialchars($error)?></div><?php endif;?>

    <form method="POST" autocomplete="off">
      <label class="lab">Email Address</label>
      <input type="email" name="email" class="inp" required placeholder="you@college.edu" autocomplete="off">

      <label class="lab">Password</label>
      <div class="pass-wrap">
        <input type="password" id="password" name="password" class="inp" required placeholder="Enter your password" autocomplete="new-password">
        <button type="button" class="eye" onclick="togglePass()" id="eyeBtn">👁</button>
      </div>

      <button type="submit" class="btn">Log in</button>
    </form>
    <div class="div">or</div>
    <div class="foot">New here? <a href="register.php">Create an account</a></div>
  </div>
</div>

<script>
function togglePass(){
  const inp = document.getElementById('password');
  const btn = document.getElementById('eyeBtn');
  if(inp.type === 'password'){
    inp.type = 'text';
    btn.textContent = '🙈';
  } else {
    inp.type = 'password';
    btn.textContent = '👁';
  }
}
</script>
</body></html>