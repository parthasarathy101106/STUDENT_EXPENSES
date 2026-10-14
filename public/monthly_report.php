<?php
session_start();
require __DIR__.'/config/database.php';
if(!isset($_SESSION['user_id'])){ header('Location: login.php'); exit; }
$uid=$_SESSION['user_id'];

// Fetch transactions - supports getTxns(), getTransactions(), or PDO
$txns = function_exists('getTxns') ? getTxns($uid) : (function_exists('getTransactions') ? getTransactions($uid) : []);
if(empty($txns)){
  try{
    global $pdo;
    if(isset($pdo)){
      $cols=$pdo->query("SHOW COLUMNS FROM transactions")->fetchAll(PDO::FETCH_COLUMN);
      $dateCol='date';
      if(in_array('transaction_date',$cols)) $dateCol='transaction_date';
      elseif(in_array('txn_date',$cols)) $dateCol='txn_date';
      elseif(!in_array('date',$cols) && in_array('created_at',$cols)) $dateCol='created_at';
      $stmt=$pdo->prepare("SELECT * FROM transactions WHERE user_id=? ORDER BY $dateCol DESC");
      $stmt->execute([$uid]);
      $txns=$stmt->fetchAll(PDO::FETCH_ASSOC);
    }
  }catch(Exception $e){}
}

// Month by month aggregation
$byMonth = [];
foreach($txns as $t){
  $d = $t['date']??$t['transaction_date']??$t['txn_date']??$t['created_at']??date('Y-m-d');
  $m = substr($d,0,7);
  $mName = date('F Y', strtotime($d));
  if(!isset($byMonth[$m])) $byMonth[$m]=['name'=>$mName,'income'=>0,'expense'=>0,'count'=>0];
  $amt=floatval($t['amount']??0);
  if(($t['type']??'expense')==='income') $byMonth[$m]['income']+=$amt;
  else $byMonth[$m]['expense']+=$amt;
  $byMonth[$m]['count']++;
}
krsort($byMonth);

// All-time by category
$byCat = [];
foreach($txns as $t){
  if(($t['type']??'expense')==='income') continue;
  $cat=$t['category']??'Other';
  if(!isset($byCat[$cat])) $byCat[$cat]=['entries'=>0,'total'=>0];
  $byCat[$cat]['entries']++;
  $byCat[$cat]['total']+=floatval($t['amount']??0);
}
arsort($byCat);

$totalInc=array_sum(array_column($byMonth,'income'));
$totalExp=array_sum(array_column($byMonth,'expense'));
$totalSaved=$totalInc-$totalExp;
function h($s){return htmlspecialchars($s??'');}
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Monthly report - Student Expense Tracker</title>
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
  --green:#00ff9d;
  --red:#ff4d94;
  --yellow:#ffd700;
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
.top b{font-size:14px;display:flex;gap:10px;align-items:center;letter-spacing:0.5px;color:var(--neon);font-weight:800}
.top b i{width:30px;height:30px;background:var(--neon);border-radius:8px;display:grid;place-items:center;color:#000;font-weight:900;font-style:normal;animation:spin 4s linear infinite}
@keyframes spin{0%{transform:rotate(0)}100%{transform:rotate(360deg)}}
.nav{display:flex;gap:24px;align-items:center}
.nav a{color:var(--muted);text-decoration:none;font-size:13px;font-weight:600;position:relative;padding-bottom:3px;transition:0.3s}
.nav a.active,.nav a:hover{color:var(--neon);text-shadow:0 0 8px var(--neon)}
.nav a.active::after{content:'';position:absolute;left:0;right:0;bottom:-8px;height:2px;background:var(--neon);border-radius:2px}

.wrap{max-width:800px;margin:0 auto;padding:40px 24px;position:relative;z-index:1;animation:fadeIn 0.8s}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}

h1{font-size:44px;font-weight:800;letter-spacing:-1.5px;margin-bottom:8px;
  background:linear-gradient(90deg,var(--neon),#fff,var(--neon));background-size:200% 100%;
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;animation:shimmer 3s infinite}
@keyframes shimmer{0%{background-position:0%}100%{background-position:200%}}
.sub{font-size:13.5px;color:var(--muted);margin-bottom:28px;animation:fadeInUp 0.8s 0.2s backwards}
@keyframes fadeInUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}

.grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:24px}
.stat{background:var(--card);border:1px solid var(--line);border-radius:18px;padding:20px;position:relative;overflow:hidden;
  transition:0.4s;animation:popIn 0.6s backwards}
.stat:nth-child(1){animation-delay:0.1s}.stat:nth-child(2){animation-delay:0.2s}.stat:nth-child(3){animation-delay:0.3s}
@keyframes popIn{from{opacity:0;transform:scale(0.9)}to{opacity:1;transform:scale(1)}}
.stat:hover{transform:translateY(-5px);border-color:var(--neon);box-shadow:0 0 25px rgba(0,212,255,0.3)}
.stat::before{content:'';position:absolute;left:0;top:0;bottom:0;width:4px}
.stat:nth-child(1)::before{background:var(--green)}.stat:nth-child(2)::before{background:var(--red)}.stat:nth-child(3)::before{background:var(--yellow)}
.stat small{font-size:10px;letter-spacing:1px;color:var(--muted);font-weight:600;text-transform:uppercase}
.stat h2{font-size:26px;font-weight:800;letter-spacing:-1px;margin-top:8px}
.stat:nth-child(1) h2{color:var(--green)}.stat:nth-child(2) h2{color:var(--red)}.stat:nth-child(3) h2{color:var(--yellow)}

.box{background:var(--card);border:1px solid var(--line);border-radius:20px;padding:26px;margin-bottom:22px;
  box-shadow:0 0 30px rgba(0,212,255,0.1);transition:0.3s;animation:fadeInUp 0.8s 0.4s backwards}
.box:hover{border-color:var(--neon);box-shadow:0 0 40px rgba(0,212,255,0.2)}
.box h3{font-size:14px;font-weight:700;margin-bottom:18px;display:flex;align-items:center;gap:8px;color:var(--neon)}
.box h3 span{width:28px;height:28px;background:#111;border:1px solid var(--neon);border-radius:8px;display:grid;place-items:center;font-size:13px}

table{width:100%;border-collapse:collapse}
th{font-size:10px;letter-spacing:1px;color:var(--muted);text-transform:uppercase;font-weight:700;text-align:left;padding:12px 8px;border-bottom:1px solid var(--line)}
th:nth-child(2),th:nth-child(3),th:nth-child(4){text-align:right}
td{padding:14px 8px;border-bottom:1px solid #111;font-size:13.5px;transition:0.2s}
tr:hover td{background:#0f0f0f;transform:translateX(3px)}
td:nth-child(2),td:nth-child(3),td:nth-child(4){text-align:right}
.inc{color:var(--green);font-weight:700}.exp{color:var(--red);font-weight:700}
.saved{color:var(--yellow);font-weight:700;background:rgba(255,215,0,0.1);padding:4px 10px;border-radius:20px;font-size:12.5px;border:1px solid rgba(255,215,0,0.3)}

.cat-bar{height:8px;background:#111;border-radius:10px;margin-top:8px;overflow:hidden}
.cat-bar i{display:block;height:100%;background:linear-gradient(90deg,var(--neon),var(--neon-dark));border-radius:10px;
  animation:fillBar 1.5s ease-out forwards;transform-origin:left}
@keyframes fillBar{from{transform:scaleX(0)}to{transform:scaleX(1)}}
.empty{padding:28px;text-align:center;color:var(--muted);font-size:13px}

@media(max-width:700px){.grid{grid-template-columns:1fr}.wrap{padding:24px 16px}h1{font-size:34px}}
</style></head><body>
<div class="top"><b><i>S</i> S-EXPENSE</b><div class="nav"><a href="dashboard.php">Dashboard</a><a href="add_transaction.php">Add transaction</a><a class="active" href="monthly_report.php">Monthly report</a><a href="logout.php">Log out</a></div></div>

<div class="wrap">
<h1>Monthly report</h1>
<div class="sub">Analyze your spending trends and category breakdown.</div>

<div class="grid">
  <div class="stat"><small>Total Income</small><h2>₹<?=number_format($totalInc)?></h2></div>
  <div class="stat"><small>Total Expenses</small><h2>₹<?=number_format($totalExp)?></h2></div>
  <div class="stat"><small>Total Saved</small><h2>₹<?=number_format($totalSaved)?></h2></div>
</div>

<div class="box">
<h3><span>📅</span> Month by month</h3>
<table>
<tr><th>Month</th><th>Income</th><th>Expenses</th><th>Saved</th></tr>
<?php if(!$byMonth):?>
<tr><td colspan="4" class="empty">No transactions found. Start by adding your first transaction.</td></tr>
<?php else: foreach($byMonth as $data): $saved=$data['income']-$data['expense']; ?>
<tr>
<td style="font-weight:600"><?=h($data['name'])?></td>
<td class="inc">₹<?=number_format($data['income'])?></td>
<td class="exp">₹<?=number_format($data['expense'])?></td>
<td><span class="saved">₹<?=number_format($saved)?></span></td>
</tr>
<?php endforeach; endif; ?>
</table>
</div>

<div class="box">
<h3><span>📊</span> All-time expenses by category</h3>
<table>
<tr><th>Category</th><th>Entries</th><th>Total</th></tr>
<?php if(!$byCat):?>
<tr><td colspan="3" class="empty">No expenses recorded yet.</td></tr>
<?php else: 
  $max = $byCat ? max(array_column($byCat,'total')) : 1;
  foreach($byCat as $cat=>$info): $pct=$max?($info['total']/$max*100):0;
?>
<tr>
<td>
  <div style="font-weight:600"><?=h($cat)?></div>
  <div class="cat-bar"><i style="width:<?=$pct?>%"></i></div>
</td>
<td style="text-align:right;color:var(--muted)"><?=h($info['entries'])?></td>
<td style="text-align:right;font-weight:700">₹<?=number_format($info['total'])?></td>
</tr>
<?php endforeach; endif; ?>
</table>
</div>

</div>
</body></html>