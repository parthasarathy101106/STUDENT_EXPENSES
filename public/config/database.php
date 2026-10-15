<?php
// BASE_URL for ByteXL proxy
if (!defined('BASE_URL')) {
    $h = $_SERVER['HTTP_HOST'] ?? '';
    define('BASE_URL', str_contains($h, '5300.') ? '/proxy/' . ($_SERVER['SERVER_PORT'] ?? '8080') : '');
}

// ===== DATABASE CONFIG =====
// ByteXL > Left DB Icon > db_455s4f2n6 > Connect button la password paaru
$DB_HOST = 'db01.dbhost.dev';
$DB_PORT = '5051'; 
$DB_NAME = 'db_455nxvdeg'; // un workspace DB name
$DB_USER = 'user_455nxvdeg'; // ByteXL la user = db_name than
$DB_PASS = 'p455nxvdeg'; // IMPORTANT: idha maathanum

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_NAME;charset=utf8mb4", 
        $DB_USER, 
        $DB_PASS, 
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// ===== HELPER FUNCTIONS =====

// 1. USERS TABLE - Register & Login
function getUserByEmail($email){
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch();
}

function createUser($username, $full_name, $email, $password_hash){
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO users (username, full_name, email, password) VALUES (?, ?, ?)");
    return $stmt->execute([$username, $full_name, $email, $password_hash]);
}

// 2. EXPENSE_USERS TABLE - extra table
function createExpenseUser($full_name, $email, $password_hash){
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO expense_users (full_name, email, password) VALUES (?, ?, ?)");
    return $stmt->execute([$full_name, $email, $password_hash]);
}

// 3. TRANSACTIONS TABLE
function getTxns($user_id){
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY date DESC, id DESC");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

function addTxn($user_id, $type, $category, $amount, $date, $description){
    global $pdo;
    // un SQL la column name: date, description
    $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, category, amount, date, description) VALUES (?, ?, ?)");
    return $stmt->execute([$user_id, $type, $category, $amount, $date, $description]);
}

function deleteTxn($id, $user_id){
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");
    return $stmt->execute([$id, $user_id]);
}
?>
