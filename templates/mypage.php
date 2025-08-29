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
    $query_us = "SELECT u.uid, u.uname, u.email, u.profileImage, u.height, f.follow_id, f.follower_uid, f.followee_uid, f.created_at FROM userauth AS u JOIN user_follow AS f ON u.uid = f.follow_id WHERE u.uid =" . $userId . ";"; 
    $query_ps = "SELECT post_id, uid, post_text, coordinateImage_path, created_at FROM post_coordinate WHERE u.uid = " . $userId . ";";
    $result_us = pg_query($query_us) or die('Query failed: ' . pg_last_error()); 

    // ユーザー情報
    $username = $line['uname'];    
    $userprofile_image = $line['profileImage'];    
    $userheight =  $line['height'];  

    // 投稿履歴
    $post_id = $line['post_id'];
    $post_text = $line['post_text'];
    $post_image = $line['coordinateImage_path'];
    $post_date = $line['created_at'];

    // フォロー一覧
    
    ?>
    <div class="maindata">
        <div>
        <p><strong>ユーザー名：</strong></p>
        <p><strong>メール：</strong></p>
        <p><strong>登録日：</strong></p>
        </div>
    </div>
    
