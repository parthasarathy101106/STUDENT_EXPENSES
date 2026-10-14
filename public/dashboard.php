<?php
session_start();
require __DIR__.'/config/database.php';
if(!isset($_SESSION['user_id'])){ header('Location: login.php'); exit; }
$uid = $_SESSION['user_id'];
$uname = $_SESSION['user_name'] ?? 'Jackson';

$filterMonth = $_GET['month'] ?? date('Y-m');
$filterCat = $_GET['category'] ?? 'All';
$search = trim($_GET['search'] ?? '');

$transactions = [];
try{
  global $pdo;
  if(isset($pdo)){
    $cols = $pdo->query("SHOW COLUMNS FROM transactions")->fetchAll(PDO::FETCH_COLUMN);
    $dateCol = in_array('date',$cols)?'date':(in_array('transaction_date',$cols)?'transaction_date':'created_at');
    
    $sql = "SELECT * FROM transactions WHERE user_id=? ";
    $params = [$uid];
    if($filterMonth){
      $sql .= " AND DATE_FORMAT($dateCol, '%Y-%m') = ? ";
      $params[] = $filterMonth;
    }
    $sql .= " ORDER BY $dateCol DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
}catch(Exception $e){}

if($filterCat!=='All' && $filterCat!==''){
  $transactions = array_filter($transactions, fn($t)=>strtolower($t['category']??'')===strtolower($filterCat));
}
if($search!==''){
  $transactions = array_filter($transactions, fn($t)=>stripos(($t['description']??'').($t['category']??''), $search)!==false);
}

$totalIncome = 0; $totalExpense = 0;
$byCat = [];
foreach($transactions as $t){
  $amt = floatval($t['amount']??0);
  if(($t['type']??'expense')==='income') $totalIncome += $amt;
  else { $totalExpense += $amt; $byCat[$t['category']??'Other'] = ($byCat[$t['category']??'Other']??0)+$amt; }
}
$balance = $totalIncome - $totalExpense;
$maxCat = $byCat ? max($byCat) : 1;
function h($s){return htmlspecialchars($s??'');}
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard - Student Expense Tracker</title>
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
body{background:var(--bg);color:var(--ink);min-height:100vh}
.top{position:sticky;top:0;z-index:10;background:rgba(10,10,10,0.9);backdrop-filter:blur(12px);border-bottom:1px solid var(--neon);padding:16px 28px;display:flex;justify-content:space-between;align-items:center}
.top b{font-size:15px;letter-spacing:0.5px;display:flex;gap:10px;align-items:center;color:var(--neon);font-weight:800}
.top b i{width:32px;height:32px;background:var(--neon);border-radius:10px;display:grid;place-items:center;color:#000;font-weight:900;font-style:normal;font-size:16px}
.nav{display:flex;gap:28px;align-items:center}
.nav a{color:var(--muted);text-decoration:none;font-size:13px;font-weight:600;transition:0.3s;position:relative;padding-bottom:4px}
.nav a.active,.nav a:hover{color:var(--neon)}
.nav a.active::after{content:'';position:absolute;left:0;right:0;bottom:-4px;height:2px;background:var(--neon);border-radius:2px}
.wrap{max-width:1200px;margin:0 auto;padding:30px 24px}
.head{display:flex;justify-content:space-between;align-items:center;margin-bottom:28px;flex-wrap:wrap;gap:12px}
.head h1{font-size:28px;font-weight:800;letter-spacing:-0.5px}
.head p{font-size:13px;color:var(--muted);margin-top:6px}
.reset{font-size:12px;padding:9px 16px;border:1.5px solid var(--neon);background:transparent;border-radius:10px;color:var(--neon);cursor:pointer;transition:0.3s;font-weight:600}
.reset:hover{background:var(--neon);color:#000;box-shadow:0 0 15px var(--neon)}
.filters{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:25px}
.f input,.f select{width:100%;padding:14px 16px;background:#111;border:1.5px solid var(--line);border-radius:12px;color:var(--ink);font-size:14px;outline:none;transition:0.3s}
.f input:focus,.f select:focus{border-color:var(--neon);background:#151515;box-shadow:0 0 0 3px rgba(0,212,255,0.2)}

/* NEW LAYOUT: 3 STATS IN ROW WITH ICONS */
.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-bottom:25px}
.stat{background:var(--card);border:1px solid var(--line);border-radius:18px;padding:22px;display:flex;align-items:center;gap:16px;transition:0.3s;position:relative;overflow:hidden}
.stat:hover{transform:translateY(-4px);border-color:var(--neon);box-shadow:0 0 20px rgba(0,212,255,0.2)}
.stat-icon{width:50px;height:50px;border-radius:14px;display:grid;place-items:center;font-size:24px}
.stat.income .stat-icon{background:rgba(0,255,157,0.15)}
.stat.expense .stat-icon{background:rgba(255,77,148,0.15)}
.stat.balance .stat-icon{background:rgba(255,215,0,0.15)}
.stat-info small{font-size:11px;letter-spacing:1px;color:var(--muted);font-weight:600;text-transform:uppercase}
.stat-info h2{font-size:26px;font-weight:800;margin-top:4px}
.stat.income h2{color:var(--green)}
.stat.expense h2{color:var(--red)}
.stat.balance h2{color:var(--yellow)}

/* NEW LAYOUT: 2 COLUMN GRID */
.grid-2{display:grid;grid-template-columns:1fr 1.3fr;gap:20px;margin-bottom:20px}
.card{background:var(--card);border:1px solid var(--line);border-radius:18px;padding:24px;transition:0.3s}
.card:hover{border-color:var(--neon)}
.card h3{font-size:15px;font-weight:700;margin-bottom:18px;letter-spacing:0.3px;color:var(--neon)}

.bar-row{display:flex;align-items:center;gap:14px;margin-bottom:14px}
.bar-row:last-child{margin-bottom:0}
.bar-label{width:80px;font-size:12.5px;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-weight:500}
.bar-track{flex:1;height:10px;background:#111;border-radius:10px;overflow:hidden}
.bar-fill{height:100%;background:linear-gradient(90deg,var(--neon),#0099cc);border-radius:10px;transition:1s}
.bar-val{width:70px;text-align:right;font-size:12px;font-weight:700;color:var(--ink)}

.table-wrap{overflow:auto;border-radius:12px}
table{width:100%;border-collapse:collapse}
th{font-size:10px;letter-spacing:1px;color:var(--muted);text-transform:uppercase;font-weight:700;text-align:left;padding:12px;border-bottom:1px solid var(--line);background:#0a0a0a}
td{padding:14px 12px;font-size:13px;border-bottom:1px solid #111;transition:0.2s}
tr:hover td{background:#0f0f0f}
tr:nth-child(even) td{background:#080808}
td:first-child{color:var(--muted);font-weight:500}
.amt-income{color:var(--green);font-weight:700}
.amt-expense{color:var(--red);font-weight:700}
.action{display:flex;gap:14px}
.action a{font-size:12px;text-decoration:none;font-weight:600;transition:0.2s}
.action .edit{color:var(--neon)}
.action .edit:hover{text-shadow:0 0 8px var(--neon)}
.action .del{color:var(--red)}
.action .del:hover{text-shadow:0 0 8px var(--red)}
.empty{padding:40px;text-align:center;color:var(--muted);font-size:13px}

@media(max-width:900px){.filters,.stats,.grid-2{grid-template-columns:1fr}.top{padding:12px 18px}.wrap{padding:20px 16px}}
</style>
</head><body>
<div class="top">
  <b><i>S</i> S-EXPENSE</b>
  <div class="nav">
    <a class="active" href="dashboard.php">Dashboard</a>
    <a href="add_transaction.php">Add transaction</a>
    <a href="monthly_report.php">Monthly report</a>
    <a href="logout.php">Log out</a>
  </div>
</div>

<div class="wrap">
  <div class="head">
    <div>
      <h1>Dashboard</h1>
      <p><?=count($transactions)?> Transactions • User ID: <?=h($uid)?> • <?=h($uname)?></p>
    </div>
    <button class="reset" onclick="if(confirm('Reset demo data?')) location.href='reset.php'">Reset demo data</button>
  </div>

  <form class="filters" method="GET">
    <div class="f"><input type="month" name="month" value="<?=h($filterMonth)?>" onchange="this.form.submit()"></div>
    <div class="f">
      <select name="category" onchange="this.form.submit()">
        <option value="All" <?= $filterCat==='All'?'selected':''?>>All categories</option>
        <option value="Food" <?= $filterCat==='Food'?'selected':''?>>Food</option>
        <option value="Education" <?= $filterCat==='Education'?'selected':''?>>Education</option>
        <option value="Travel" <?= $filterCat==='Travel'?'selected':''?>>Travel</option>
        <option value="Other" <?= $filterCat==='Other'?'selected':''?>>Other</option>
        <option value="Salary" <?= $filterCat==='Salary'?'selected':''?>>Salary</option>
      </select>
    </div>
    <div class="f"><input type="text" name="search" placeholder="Search by description or category" value="<?=h($search)?>"></div>
  </form>

  <!-- NEW STATS LAYOUT WITH ICONS -->
  <div class="stats">
    <div class="stat income">
      <div class="stat-icon">💰</div>
      <div class="stat-info">
        <small>Total Income</small>
        <h2>₹<?=number_format($totalIncome)?></h2>
      </div>
    </div>
    <div class="stat expense">
      <div class="stat-icon">💸</div>
      <div class="stat-info">
        <small>Total Expenses</small>
        <h2>₹<?=number_format($totalExpense)?></h2>
      </div>
    </div>
    <div class="stat balance">
      <div class="stat-icon">📊</div>
      <div class="stat-info">
        <small>Available Balance</small>
        <h2>₹<?=number_format($balance)?></h2>
      </div>
    </div>
  </div>

  <!-- NEW 2 COLUMN LAYOUT -->
  <div class="grid-2">
    <div class="card">
      <h3>Spending by category</h3>
      <?php if(!$byCat):?><div class="empty">No expenses yet</div>
      <?php else: foreach($byCat as $cat=>$amt): $pct=($maxCat?($amt/$maxCat*100):0); ?>
        <div class="bar-row">
          <div class="bar-label"><?=h($cat)?></div>
          <div class="bar-track"><div class="bar-fill" style="width:<?=$pct?>%"></div></div>
          <div class="bar-val">₹<?=number_format($amt)?></div>
        </div>
      <?php endforeach; endif;?>
    </div>

    <div class="card">
      <h3>Recent Transactions</h3>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Date</th><th>Category</th><th>Description</th><th>Amount</th><th>Action</th></tr></thead>
          <tbody>
          <?php if(!$transactions):?><tr><td colspan="5" class="empty">No transactions found</td></tr>
          <?php else: foreach(array_slice($transactions,0,10) as $t):
            $d = $t['date']??$t['transaction_date']??$t['created_at']??'';
            $isInc = ($t['type']??'expense')==='income';
          ?>
            <tr>
              <td><?=h(substr($d,0,10))?></td>
              <td><?=h($t['category']??'Other')?></td>
              <td><?=h($t['description']??$t['note']??'-')?></td>
              <td class="<?=$isInc?'amt-income':'amt-expense'?>"><?=$isInc?'+':'-'?> ₹<?=number_format(floatval($t['amount']??0))?></td>
              <td><div class="action"><a class="edit" href="edit_transaction.php?id=<?=$t['id']?>">Edit</a><a class="del" href="delete_transaction.php?id=<?=$t['id']?>" onclick="return confirm('Delete?')">Delete</a></div></td>
            </tr>
          <?php endforeach; endif;?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
</body></html>