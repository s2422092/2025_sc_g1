<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ja" lang="ja">
<head>
<meta charset="UTF-8">
    <title>夏合宿</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.6.0/dist/leaflet.css" integrity="sha512-xwE/Az9zrjBIphAcBb3F6JVqxf46+CDLwfLMHloNu6KEQCAWi6HcDUbeOfBIptF7tcCzusKFjFw2yuvEpDL9wQ==" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.6.0/dist/leaflet.js" integrity="sha512-gZwIG9x3wUXg2hdXF6+rVkLF/0Vi9U8D2Ntg4Ga5I5BZpVkVxlJWbSQtXPSiUTtC0TjtGOmxa1AJPuV0CPthew==" crossorigin=""></script>
    <!--link href="mythologymap.css" rel="stylesheet" type="text/css" media="all" /-->
</head>

<body>
    <header>
        <h1 class="test">mypage</h1>
    </header>
    <?php
    session_start(); //セッション確認
    if (isset($_SESSION['user_id'])) {
        //ログインしていたらマイページへ
        return header("Location: mypage.php");
    }else{// 未ログインならログインページへ
        header("Location: login.php");
        exit();
    }

    $userId = $_SESSION['user_id'];

    // データベース接続
    $dbconn = pg_connect("host=localhost dbname=yuisuga user=yuisuga password=R9ixwMq0") or die('Could not connect: ' . pg_last_error());
    
    // ユーザー情報
    $query_us = "SELECT uid, uname, email, profileImage, height FROM userauth WHERE uid =  $userId;"; 
    $result_us = pg_query($query_us) or die('Query failed: ' . pg_last_error()); 

    if (!$dbconn) {
    die("データベース接続に失敗しました");
    }

    while ($line = pg_fetch_array($result_us)) {
    $username = $line['uname'];    
    $userprofile_image = $line['profileImage'];    
    $userheight =  $line['height'];
    }

    // フォロー一覧
    $query_fl = "SELECT u.uid, u.uname, u.profileImage, f.follow_id, f.follower_uid, f.followee_uid, f.created_at FROM userauth AS u JOIN user_follow AS f ON u.uid =  f.followee_uid WHERE f.follower_uid = $userId;";
    $result_fl = pg_query($query_fl) or die('Query failed: ' . pg_last_error()); 
    
    if (!$dbconn) {
    die("データベース接続に失敗しました");
    }

    while ($line = pg_fetch_array($result_ls)) {
    $followee_profileImage = $line['profileImage']
    $followee_name = $line['uname']//フォロー名
    }

    // 投稿履歴
    $query_ps = "SELECT u.uid, u.uname, p.post_id, p.uid, p.post_text, p.coordinateImage_path, p.created_at FROM  userauth AS u JOIN post_coordinate AS p ON u.uid = p.uid WHERE u.uid = $userId;";
    $result_ps = pg_query($query_ps) or die('Query failed: ' . pg_last_error()); 

    if (!$dbconn) {
    die("データベース接続に失敗しました");
    }

    while ($line = pg_fetch_array($result_ps)) {
    $uname = $line['uname']; //名前
    $post_text = $line['post_text']; //投稿内容
    $post_image = $line['coordinateImage_path'];//投稿画像
    $post_date = $line['created_at'];//投稿日時
    }

    
    pg_close($dbconn);
    ?>
    <div class="userdata">
        <div>
        <p><strong>ユーザー名：</strong></p>
        <p><strong>メール：</strong></p>
        <p><strong>登録日：</strong></p>
        </div>
    </div>
    <div class="post_history">
        <details>
            <summary>投稿履歴</summary>
            <p>name</p>
            <p>text</p>
            <p>image</p>
            <p>date</p>
        </details>    
    </div>
    <div class="follow_list">
        <details>
            <summary>フォロー一覧</summary>
            <p>image</p>
            <p>name</p>
        </details>    
    </div>
    
