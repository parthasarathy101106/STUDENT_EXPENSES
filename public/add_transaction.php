<?php
session_start();
require __DIR__.'/config/database.php';
if(!isset($_SESSION['user_id'])){ header('Location: login.php'); exit; }
$uid=$_SESSION['user_id'];
$err='';

if($_SERVER['REQUEST_METHOD']==='POST'){
 $type=trim($_POST['type']??'expense');
 $cat=trim($_POST['category']??'Food');
 $amt=floatval($_POST['amount']??0);
 $date=$_POST['date']??date('Y-m-d');
 $desc=trim($_POST['description']??'');

 if($amt<=0) $err="Please enter a valid amount greater than zero.";
 else{
  if(function_exists('addTransaction')) {
    try{
      addTransaction($uid,$type,$cat,$amt,$date,$desc);
    }catch(Exception $e){ $err="Failed to save: ".$e->getMessage(); }
  } else {
    try{
      global $pdo;
      if(isset($pdo)){
        $cols = $pdo->query("SHOW COLUMNS FROM transactions")->fetchAll(PDO::FETCH_COLUMN);
        $dateCol = 'date';
        if(in_array('transaction_date',$cols)) $dateCol='transaction_date';
        elseif(in_array('txn_date',$cols)) $dateCol='txn_date';
        elseif(in_array('trans_date',$cols)) $dateCol='trans_date';
        elseif(in_array('created_at',$cols) && !in_array('date',$cols)) $dateCol='created_at';
        
        $sql = "INSERT INTO transactions (user_id,type,category,amount,$dateCol,description) VALUES (?,?,?,?,?,?)";
        $stmt=$pdo->prepare($sql);
        $stmt->execute([$uid,$type,$cat,$amt,$date,$desc]);
      }
    }catch(Exception $e){ 
      $err="Database error: ".$e->getMessage(); 
    }
  }
  if(!$err){ header('Location: dashboard.php'); exit; }
 }
}
function h($s){return htmlspecialchars($s??'');}
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Add transaction - Student Expense Tracker</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#000;
  --card:#0a0a0a;
  --ink:#e0e0e0;
  --muted:#7a7a7a;
  --neon:#00d4ff; /* Neon Blue */
  --neon-dark:#0099cc;
  --line:#1f1f1f;
  --red:#ff4d94;
}
*{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',system-ui,sans-serif}
body{background:var(--bg);color:var(--ink);min-height:100vh;overflow-x:hidden}

/* Animated Grid BG */
body::before{
  content:'';position:fixed;top:0;left:0;width:200%;height:200%;
  background:linear-gradient(rgba(0,212,255,0.03) 1px, transparent 1px),linear-gradient(90deg, rgba(0,212,255,0.03) 1px, transparent 1px);
  background-size:40px 40px;animation:gridMove 15s linear infinite;z-index:0;
}
@keyframes gridMove{0%{transform:translate(0,0)}100%{transform:translate(-40px,-40px)}}

.top{position:sticky;top:0;z-index:10;background:rgba(10,10,10,0.9);backdrop-filter:blur(14px);border-bottom:1px solid var(--neon);padding:14px 28px;display:flex;justify-content:space-between;align-items:center;animation:slideDown 0.6s}
@keyframes slideDown{from{transform:translateY(-100%)}to{transform:translateY(0)}}
.top b{font-size:14px;display:flex;align-items:center;gap:10px;letter-spacing:0.5px;color:var(--neon);font-weight:800}
.top b i{width:30px;height:30px;background:var(--neon);border-radius:8px;display:grid;place-items:center;color:#000;font-weight:900;font-style:normal;animation:spin 4s linear infinite}
@keyframes spin{0%{transform:rotate(0)}100%{transform:rotate(360deg)}}
.nav{display:flex;gap:24px;align-items:center}
.nav a{color:var(--muted);text-decoration:none;font-size:13px;font-weight:600;position:relative;padding-bottom:3px;transition:0.3s}
.nav a.active,.nav a:hover{color:var(--neon);text-shadow:0 0 8px var(--neon)}
.nav a.active::after{content:'';position:absolute;left:0;right:0;bottom:-8px;height:2px;background:var(--neon);border-radius:2px;animation:widthGrow 0.4s}
@keyframes widthGrow{from{width:0}to{width:100%}}

.wrap{max-width:720px;margin:0 auto;padding:40px 24px;position:relative;z-index:1;animation:fadeIn 0.8s}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}

h1{font-size:44px;font-weight:800;letter-spacing:-1.5px;text-align:center;margin-bottom:10px;
  background:linear-gradient(90deg,var(--neon),#fff,var(--neon));background-size:200% 100%;
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;animation:shimmer 3s infinite}
@keyframes shimmer{0%{background-position:0%}100%{background-position:200%}}
.sub-h{text-align:center;color:var(--muted);font-size:13.5px;margin-bottom:28px;animation:fadeInUp 0.8s 0.2s backwards}
@keyframes fadeInUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}

.card{max-width:520px;margin:0 auto;background:var(--card);border:1px solid var(--line);border-radius:20px;padding:28px;
  box-shadow:0 0 30px rgba(0,212,255,0.1),inset 0 1px 0 rgba(255,255,255,0.05);
  animation:floatCard 3s ease-in-out infinite;transition:0.3s}
.card:hover{box-shadow:0 0 40px rgba(0,212,255,0.3)}
@keyframes floatCard{0%,100%{transform:translateY(0)}50%{transform:translateY(-8px)}}

.tog{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:24px}
.tog label{background:#111;border:1.5px solid var(--line);border-radius:12px;padding:14px 16px;display:flex;align-items:center;gap:10px;cursor:pointer;font-size:13px;font-weight:600;color:var(--muted);transition:0.3s;position:relative;overflow:hidden}
.tog label::before{content:'';position:absolute;top:50%;left:50%;width:0;height:0;border-radius:50%;background:var(--neon);transform:translate(-50%,-50%);transition:0.4s;opacity:0.2}
.tog label:active::before{width:300px;height:300px}
.tog input{accent-color:var(--neon);width:16px;height:16px}
.tog label:has(input:checked){border-color:var(--neon);background:#151515;color:var(--neon);box-shadow:0 0 0 4px rgba(0,212,255,0.15);transform:scale(1.03)}

.lab{display:block;font-size:12px;font-weight:600;margin:20px 0 8px;color:var(--neon);letter-spacing:0.2px}
.lab span{color:var(--muted);font-weight:400}
.inp,.sel{width:100%;padding:14px 16px;background:#111;border:1.5px solid var(--line);border-radius:12px;color:var(--ink);font-size:14px;outline:none;transition:0.3s;appearance:none}
.inp:focus,.sel:focus{border-color:var(--neon);background:#151515;box-shadow:0 0 0 4px rgba(0,212,255,0.15);transform:translateY(-2px)}
.inp::placeholder{color:var(--muted)}

.btns{display:flex;align-items:center;gap:16px;margin-top:28px}
.save{padding:13px 24px;background:linear-gradient(180deg,var(--neon),var(--neon-dark));color:#000;border:none;border-radius:12px;font-weight:700;font-size:13.5px;cursor:pointer;
  box-shadow:0 0 20px rgba(0,212,255,0.4);transition:0.3s;animation:pulseGlow 2s infinite}
.save:hover{transform:translateY(-3px) scale(1.05);box-shadow:0 0 35px rgba(0,212,255,0.7)}
@keyframes pulseGlow{0%,100%{box-shadow:0 0 20px rgba(0,212,255,0.4)}50%{box-shadow:0 0 30px rgba(0,212,255,0.7)}}
.cancel{font-size:13px;color:var(--muted);text-decoration:none;border-bottom:1px solid var(--line);padding-bottom:2px;transition:0.3s}
.cancel:hover{color:var(--neon);border-color:var(--neon);text-shadow:0 0 8px var(--neon)}
.err{background:#1a0a0a;border:1px solid #3a1a1a;color:var(--red);padding:12px 14px;border-radius:10px;margin-bottom:18px;font-size:13px;animation:shake 0.5s}
@keyframes shake{0%,100%{transform:translateX(0)}25%{transform:translateX(-5px)}75%{transform:translateX(5px)}}
</style></head><body>
<div class="top"><b><i>S</i> S-EXPENSE</b><div class="nav"><a href="dashboard.php">Dashboard</a><a class="active" href="add_transaction.php">Add transaction</a><a href="monthly_report.php">Monthly report</a><a href="logout.php">Log out</a></div></div>
<div class="wrap">
<h1>Add transaction</h1>
<div class="sub-h">Record your income or expense in seconds</div>
<div class="card">
<?php if($err):?><div class="err"><?=h($err)?></div><?php endif;?>
<form method="POST">
<div class="tog">
<label><input type="radio" name="type" value="expense" checked onchange="updateCats()"> Expense</label>
<label><input type="radio" name="type" value="income" onchange="updateCats()"> Income</label>
</div>

<label class="lab">Category</label>
<select name="category" id="cat" class="sel" required>
  <optgroup label="Daily Needs"><option>Food</option><option>Mess / Canteen</option><option>Transport / Bus</option><option>Accommodation / Rent</option></optgroup>
  <optgroup label="Study"><option>Education</option><option>Books / Stationery</option><option>Internet / Recharge</option><option>Course Fees</option></optgroup>
  <optgroup label="Lifestyle"><option>Travel</option><option>Entertainment / Movies</option><option>Shopping / Clothes</option><option>Health / Medical</option><option>Gym / Sports</option></optgroup>
  <optgroup label="Others"><option>Bills / Electricity</option><option>Personal Care</option><option>Other</option></optgroup>
</select>

<label class="lab">Amount (₹)</label>
<input type="number" step="0.01" min="1" name="amount" class="inp" placeholder="e.g. 500" required>

<label class="lab">Date</label>
<input type="date" name="date" class="inp" value="<?=date('Y-m-d')?>" required>

<label class="lab">Description <span>(optional)</span></label>
<input type="text" name="description" class="inp" placeholder="e.g. Mess fees, bus pass">

<div class="btns"><button type="submit" class="save">Save transaction</button><a href="dashboard.php" class="cancel">Cancel</a></div>
</form>
</div>
</div>

<script>
function updateCats(){
 const type=document.querySelector('input[name=type]:checked').value;
 const sel=document.getElementById('cat');
 if(type==='income'){
  sel.innerHTML='<optgroup label="Income Sources"><option>Salary</option><option>Pocket Money</option><option>Scholarship</option><option>Freelance / Part-time</option><option>From Parents</option><option>Internship</option><option>Gift</option><option>Other</option></optgroup>';
 } else {
  sel.innerHTML='<optgroup label="Daily Needs"><option>Food</option><option>Mess / Canteen</option><option>Transport / Bus</option><option>Accommodation / Rent</option></optgroup><optgroup label="Study"><option>Education</option><option>Books / Stationery</option><option>Internet / Recharge</option><option>Course Fees</option></optgroup><optgroup label="Lifestyle"><option>Travel</option><option>Entertainment / Movies</option><option>Shopping / Clothes</option><option>Health / Medical</option><option>Gym / Sports</option></optgroup><optgroup label="Others"><option>Bills / Electricity</option><option>Personal Care</option><option>Other</option></optgroup>';
 }
}
</script>
</body></html>