<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'ログインしてください']);
    exit;
}

$uid = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_id = intval($_POST['post_id']);
    $compliment_text = trim($_POST['compliment']);

    // DB接続
    $host = 'localhost';
    $dbname = 's_yugo';
    $user = 's_yugo';
    $password = '9fjrtvAy';

    try {
        $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 褒め言葉IDを取得（compliment_list に存在する場合）
        $stmt = $pdo->prepare("SELECT compliment_id FROM compliment_list WHERE compliment_text = ?");
        $stmt->execute([$compliment_text]);
        $compliment_id = $stmt->fetchColumn();

        // 挿入
        $stmt = $pdo->prepare("INSERT INTO post_compliment (post_id, uid, compliment_id) VALUES (?, ?, ?)");
        $stmt->execute([$post_id, $uid, $compliment_id]);

        echo json_encode(['status' => 'success', 'message' => 'コメントを追加しました']);

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
