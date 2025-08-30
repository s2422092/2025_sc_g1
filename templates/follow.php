<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'ログインしてください']);
    exit;
}

$follower_uid = $_SESSION['user_id'];  // 自分
$followee_uid = $_POST['target_id'] ?? null;

if (!$followee_uid) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'フォロー対象が不明です']);
    exit;
}

// DB接続
$host = 'localhost';
$dbname = 's_yugo';
$user = 's_yugo';
$password = '9fjrtvAy';

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // すでにフォローしているか確認
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_follow WHERE follower_uid = ? AND followee_uid = ?");
    $stmt->execute([$follower_uid, $followee_uid]);
    $exists = $stmt->fetchColumn();

    if ($exists) {
        echo json_encode(['status' => 'already', 'message' => 'すでにフォロー済みです']);
    } else {
        // フォロー登録
        $stmt = $pdo->prepare("INSERT INTO user_follow (follower_uid, followee_uid) VALUES (?, ?)");
        $stmt->execute([$follower_uid, $followee_uid]);

        echo json_encode(['status' => 'success', 'message' => 'フォローしました']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
