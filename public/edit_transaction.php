<?php
session_start();
require __DIR__.'/config/database.php';
if(!isset($_SESSION['user_id'])){ header('Location: login.php'); exit; }
$uid=$_SESSION['user_id'];
$id=$_GET['id']??0;
if(!$id){ header('Location: dashboard.php'); exit; }

$txn=null;
try{
  global $pdo;
  if(isset($pdo)){
    $stmt=$pdo->prepare("SELECT * FROM transactions WHERE id=? AND user_id=?");
    $stmt->execute([$id,$uid]);
    $txn=$stmt->fetch(PDO::FETCH_ASSOC);
  }
}catch(Exception $e){}
if(!$txn){ header('Location: dashboard.php'); exit; }

$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $type=trim($_POST['type']??'expense');
  $cat=trim($_POST['category']??'Other');
  $amt=floatval($_POST['amount']??0);
  $date=$_POST['date']??date('Y-m-d');
  $desc=trim($_POST['description']??'');

  if($amt<=0) $err="Please enter a valid amount.";
  else{
    try{
      global $pdo;
      $cols = $pdo->query("SHOW COLUMNS FROM transactions")->fetchAll(PDO::FETCH_COLUMN);
      $dateCol = 'date';
      if(in_array('transaction_date',$cols)) $dateCol='transaction_date';
      elseif(in_array('txn_date',$cols)) $dateCol='txn_date';
      elseif(!in_array('date',$cols) && in_array('created_at',$cols)) $dateCol='created_at';
      $sql = "UPDATE transactions SET type=?, category=?, amount=?, $dateCol=?, description=? WHERE id=? AND user_id=?";
      $stmt=$pdo->prepare($sql);
      $stmt->execute([$type,$cat,$amt,$date,$desc,$id,$uid]);
      header('Location: dashboard.php'); exit;
    }catch(Exception $e){ $err="Database error: ".$e->getMessage(); }
  }
}
function h($s){return htmlspecialchars($s??'');}
function sel($a,$b){return strtolower(trim($a))===strtolower(trim($b))?'selected':'';}
$dateVal = $txn['date']??$txn['transaction_date']??$txn['created_at']??date('Y-m-d');
$currCat = $txn['category']??'Other';
$currType = $txn['type']??'expense';
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Edit Transaction - Student Expense Tracker</title>
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

.top{position:sticky;top:0;z-index:10;background:rgba(10,10,10,0.9);backdrop-filter:blur(14px);border-bottom:1px solid var(--neon);padding:14px 24px;display:flex;justify-content:space-between;align-items:center;animation:slideDown 0.6s}
@keyframes slideDown{from{transform:translateY(-100%)}to{transform:translateY(0)}}
.top b{font-size:15px;color:var(--neon);font-weight:800;letter-spacing:0.5px}
.nav a{color:var(--muted);text-decoration:none;margin-left:22px;font-size:13.5px;font-weight:600;transition:0.3s}
.nav a.active,.nav a:hover{color:var(--neon);text-shadow:0 0 8px var(--neon)}

.wrap{max-width:560px;margin:0 auto;padding:40px 22px;position:relative;z-index:1;animation:fadeIn 0.8s}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}

h1{font-size:36px;font-weight:800;letter-spacing:-0.5px;margin-bottom:28px;text-align:center;
  background:linear-gradient(90deg,var(--neon),#fff,var(--neon));background-size:200% 100%;
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;animation:shimmer 3s infinite}
@keyframes shimmer{0%{background-position:0%}100%{background-position:200%}}

.card{background:var(--card);border:1px solid var(--line);border-radius:20px;padding:26px;
  box-shadow:0 0 30px rgba(0,212,255,0.1);animation:floatCard 3s ease-in-out infinite}
@keyframes floatCard{0%,100%{transform:translateY(0)}50%{transform:translateY(-6px)}}

.tog{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:18px}
.tog label{background:#111;border:1.5px solid var(--line);border-radius:12px;padding:12px 14px;display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;font-weight:600;color:var(--muted);transition:0.3s}
.tog input{accent-color:var(--neon)}
.tog label:has(input:checked){border-color:var(--neon);background:#151515;color:var(--neon);box-shadow:0 0 0 4px rgba(0,212,255,0.15);transform:scale(1.03)}

.lab{display:block;font-size:13px;font-weight:600;margin:18px 0 8px;color:var(--neon)}
.inp, .sel{width:100%;padding:13px 16px;background:#111;border:1.5px solid var(--line);border-radius:10px;color:var(--ink);font-size:14px;outline:none;transition:0.3s}
.inp:focus, .sel:focus{border-color:var(--neon);background:#151515;box-shadow:0 0 0 4px rgba(0,212,255,0.15);transform:translateY(-2px)}

.btns{display:flex;align-items:center;gap:14px;margin-top:22px}
.save{padding:11px 22px;background:linear-gradient(180deg,var(--neon),var(--neon-dark));color:#000;border:none;border-radius:10px;font-weight:700;cursor:pointer;
  box-shadow:0 0 20px rgba(0,212,255,0.4);transition:0.3s;animation:pulseGlow 2s infinite}
.save:hover{transform:translateY(-2px) scale(1.05);box-shadow:0 0 30px rgba(0,212,255,0.7)}
@keyframes pulseGlow{0%,100%{box-shadow:0 0 20px rgba(0,212,255,0.4)}50%{box-shadow:0 0 30px rgba(0,212,255,0.7)}}
.cancel{color:var(--muted);text-decoration:underline;font-size:14px;transition:0.3s}
.cancel:hover{color:var(--neon);text-shadow:0 0 8px var(--neon)}
.err{background:#1a0a0a;border:1px solid #3a1a1a;color:var(--red);padding:12px;border-radius:10px;margin-bottom:16px;font-size:13px;animation:shake 0.5s}
@keyframes shake{0%,100%{transform:translateX(0)}25%{transform:translateX(-5px)}75%{transform:translateX(5px)}}
</style></head><body>
<div class="top"><b>S-EXPENSE</b><div class="nav"><a href="dashboard.php">Dashboard</a><a class="active" href="#">Edit transaction</a><a href="monthly_report.php">Monthly report</a><a href="logout.php">Log out</a></div></div>
<div class="wrap">
<h1>Edit transaction</h1>
<div class="card">
<?php if($err):?><div class="err"><?=h($err)?></div><?php endif;?>
<form method="POST">
<div class="tog">
<label><input type="radio" name="type" value="expense" <?= $currType!=='income'?'checked':''?> onchange="updateCats()"> Expense</label>
<label><input type="radio" name="type" value="income" <?= $currType==='income'?'checked':''?> onchange="updateCats()"> Income</label>
</div>

<label class="lab">Category</label>
<select name="category" id="cat" class="sel" required>
  <optgroup label="Daily Needs">
    <option <?=sel($currCat,'Food')?>>Food</option>
    <option <?=sel($currCat,'Mess / Canteen')?>>Mess / Canteen</option>
    <option <?=sel($currCat,'Transport / Bus')?>>Transport / Bus</option>
    <option <?=sel($currCat,'Accommodation / Rent')?>>Accommodation / Rent</option>
  </optgroup>
  <optgroup label="Study">
    <option <?=sel($currCat,'Education')?>>Education</option>
    <option <?=sel($currCat,'Books / Stationery')?>>Books / Stationery</option>
    <option <?=sel($currCat,'Internet / Recharge')?>>Internet / Recharge</option>
    <option <?=sel($currCat,'Course Fees')?>>Course Fees</option>
  </optgroup>
  <optgroup label="Lifestyle">
    <option <?=sel($currCat,'Travel')?>>Travel</option>
    <option <?=sel($currCat,'Entertainment / Movies')?>>Entertainment / Movies</option>
    <option <?=sel($currCat,'Shopping / Clothes')?>>Shopping / Clothes</option>
    <option <?=sel($currCat,'Health / Medical')?>>Health / Medical</option>
    <option <?=sel($currCat,'Gym / Sports')?>>Gym / Sports</option>
  </optgroup>
  <optgroup label="Others">
    <option <?=sel($currCat,'Bills / Electricity')?>>Bills / Electricity</option>
    <option <?=sel($currCat,'Personal Care')?>>Personal Care</option>
    <option <?=sel($currCat,'Other')?>>Other</option>
  </optgroup>
  <optgroup label="Income Sources">
    <option <?=sel($currCat,'Salary')?>>Salary</option>
    <option <?=sel($currCat,'Pocket Money')?>>Pocket Money</option>
    <option <?=sel($currCat,'Scholarship')?>>Scholarship</option>
    <option <?=sel($currCat,'Freelance / Part-time')?>>Freelance / Part-time</option>
    <option <?=sel($currCat,'From Parents')?>>From Parents</option>
    <option <?=sel($currCat,'Internship')?>>Internship</option>
    <option <?=sel($currCat,'Gift')?>>Gift</option>
  </optgroup>
</select>

<label class="lab">Amount (₹)</label>
<input type="number" step="0.01" min="1" name="amount" class="inp" value="<?=h($txn['amount']??'')?>" required>

<label class="lab">Date</label>
<input type="date" name="date" class="inp" value="<?=h(substr($dateVal,0,10))?>" required>

<label class="lab">Description <span style="color:var(--muted);font-weight:400">(optional)</span></label>
<input type="text" name="description" class="inp" value="<?=h($txn['description']??$txn['note']??'')?>" placeholder="e.g. Travel to college">

<div class="btns"><button type="submit" class="save">Update</button><a href="dashboard.php" class="cancel">Cancel</a></div>
</form>
</div>
</div>
<script>
function updateCats(){
 const type=document.querySelector('input[name=type]:checked').value;
 const sel=document.getElementById('cat');
 const curr="<?=h($currCat)?>";
 if(type==='income'){
  sel.innerHTML=`<optgroup label="Income Sources">
    <option>Salary</option><option>Pocket Money</option><option>Scholarship</option>
    <option>Freelance / Part-time</option><option>From Parents</option><option>Internship</option>
    <option>Gift</option><option>Other</option></optgroup>`;
 } else {
  sel.innerHTML=`<optgroup label="Daily Needs">
    <option>Food</option><option>Mess / Canteen</option><option>Transport / Bus</option><option>Accommodation / Rent</option></optgroup>
  <optgroup label="Study"><option>Education</option><option>Books / Stationery</option><option>Internet / Recharge</option><option>Course Fees</option></optgroup>
  <optgroup label="Lifestyle"><option>Travel</option><option>Entertainment / Movies</option><option>Shopping / Clothes</option><option>Health / Medical</option><option>Gym / Sports</option></optgroup>
  <optgroup label="Others"><option>Bills / Electricity</option><option>Personal Care</option><option>Other</option></optgroup>`;
 }
 for(let o of sel.options){ if(o.text.toLowerCase()===curr.toLowerCase()){ o.selected=true; break; } }
}
window.onload=()=>{ if("<?=$currType?>"==="income") updateCats(); };
</script>
</body></html>