<?php
session_start();
require __DIR__.'/config/database.php';
if(!isset($_SESSION['user_id'])){ header('Location: login.php'); exit; }
$uid=$_SESSION['user_id'];
$id=$_GET['id']??0;
if($id){
  try{
    global $pdo;
    if(isset($pdo)){
      $stmt=$pdo->prepare("DELETE FROM transactions WHERE id=? AND user_id=?");
      $stmt->execute([$id,$uid]);
    }
  }catch(Exception $e){}
}
header('Location: dashboard.php'); exit;
?>