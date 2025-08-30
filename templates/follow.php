<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'ログインしてください']);
    exit;
}

$host = 'localhost';
$dbname = 's_yugo';
$user = 's_yugo';
$password = '9fjrtvAy';

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $follower_uid = $_SESSION['user_id']; // 自分
    $followee_uid = intval($_POST['followee_uid']); // 相手

    if ($follower_uid === $followee_uid) {
        http_response_code(400);
        echo json_encode(['error' => '自分自身はフォローできません']);
        exit;
    }

    // すでにフォローしてるか確認
    $stmt = $pdo->prepare("SELECT 1 FROM user_follow WHERE follower_uid = ? AND followee_uid = ?");
    $stmt->execute([$follower_uid, $followee_uid]);

    if ($stmt->fetch()) {
        // フォロー解除
        $stmt = $pdo->prepare("DELETE FROM user_follow WHERE follower_uid = ? AND followee_uid = ?");
        $stmt->execute([$follower_uid, $followee_uid]);
        echo json_encode(['status' => 'unfollow']);
    } else {
        // フォロー
        $stmt = $pdo->prepare("INSERT INTO user_follow (follower_uid, followee_uid) VALUES (?, ?)");
        $stmt->execute([$follower_uid, $followee_uid]);
        echo json_encode(['status' => 'follow']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
