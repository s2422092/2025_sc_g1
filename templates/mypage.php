<?php session_start(); //セッション確認
if (!isset($_SESSION['user_id'])) {;
    // 未ログインならログインページへ
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

// データベース接続
$dbconn = pg_connect("host=localhost dbname=s_yogo user=s_yogo password=9fjrtvAy") or die('Could not connect: ' . pg_last_error());
    
// ユーザー情報
$query_us = "SELECT uid, uname, email, profileImage, height, frame, created_at FROM userauth WHERE uid =  $userId;"; 
$result_us = pg_query($query_us) or die('Query failed: ' . pg_last_error()); 

if (!$dbconn) {
die("データベース接続に失敗しました");
}

while ($line = pg_fetch_array($result_us)) {
$username = $line['uname']; //ユーザー名
$userstart_date = $line['created_at']; //登録日
$userprofile_image = $line['profileImage']; //ユーザーアイコン   
$userheight =  $line['height']; //ユーザー身長
$userframe =  $line['frame']; //ユーザー体格
}

// フォロー一覧
$query_fl = "SELECT u.uid, u.uname, u.profileImage, f.follow_id, f.follower_uid, f.followee_uid, f.created_at FROM userauth AS u JOIN user_follow AS f ON u.uid =  f.followee_uid WHERE f.follower_uid = $userId;";
$result_fl = pg_query($query_fl) or die('Query failed: ' . pg_last_error()); 
    
if (!$dbconn) {
die("データベース接続に失敗しました");
}

while ($line = pg_fetch_array($result_ls)) {
$followee_profileImage = $line['profileImage'];//アイコン
$followee_name = $line['uname'];//フォロー名
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
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ja" lang="ja">
<head>
<meta charset="UTF-8">
    <title>夏合宿</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.6.0/dist/leaflet.css" integrity="sha512-xwE/Az9zrjBIphAcBb3F6JVqxf46+CDLwfLMHloNu6KEQCAWi6HcDUbeOfBIptF7tcCzusKFjFw2yuvEpDL9wQ==" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.6.0/dist/leaflet.js" integrity="sha512-gZwIG9x3wUXg2hdXF6+rVkLF/0Vi9U8D2Ntg4Ga5I5BZpVkVxlJWbSQtXPSiUTtC0TjtGOmxa1AJPuV0CPthew==" crossorigin=""></script>
    <link href="mypage.css" rel="stylesheet" type="text/css" media="all">
</head>
<body>
    <header>
        <h1 class="test">mypage</h1>
    </header>
    <div class="userdata">
        <section class="profile">
            <div class="profile-header">
                <p><strong><img src="<?php echo htmlspecialchars($userprofile_image); ?>" alt="プロフィールアイコン" class="profile-icon"></p><!--アイコン-->
                <div>
                    <p><strong><?php echo htmlspecialchars($username); ?></strong></p><!--ユーザー名-->
                    <p class="detail-info"><sub><?php echo htmlspecialchars($userstart_date); ?></sub></p><!--登録日-->
                </div>
            </div>
            <div style="display: flex; margin-top: 15px;">
                <div class="box3">
                    <p><strong>身長：<?php echo htmlspecialchars($userheight); ?></strong></p> <!--身長-->
                </div>
                <div class="box4">
                    <p><strong>体格：<?php echo htmlspecialchars($userframe); ?></strong></p> <!--体格-->
                </div>
            </div>
        </section>
        <div class="follow-stats">
            <div class="follow-item">
                <?php
                $f_sql = "SELECT count(*) FROM user_follow WHERE follower_uid = $userId;";
                $r = pg_query($f_sql) or die('Error: ' . pg_last_error());
                $row=pg_fetch_row($r);
                $count = $row[0];
                echo"<p class=\"follow-count\">" . $count . "</p>";//フォロー数計算
                ?>
                <p class="follow-label">フォロー</p>
                <?php
                $fe_sql = "SELECT count(*) FROM user_follow WHERE followee_uid = $userId;";
                $r_fe = pg_query($fe_sql) or die('Error: ' . pg_last_error());
                $rowf=pg_fetch_row($r_fe);
                $count_fe = $rowf[0];
                echo"<p class=\"follow-count\">" . $count_fe . "</p>";//フォロワー数計算
                ?>
                <p class="follow-label">フォロワー</p>
            </div>
            <details close>
                <summary>フォロー一覧</summary>
                    <p><img src="<?php echo htmlspecialchars($followee_profileImage); ?>" alt="NO DATA"></p><!--フォロー相手のアイコン-->
                    <p><?php echo htmlspecialchars($followee_name); ?></p><!--フォロー相手の名前-->
            </details>
        </div>
        <div class="post_history">
            <details>
                <summary>投稿履歴</summary>
                <p><img src="<?php echo htmlspecialchars($post_image); ?>" alt="NO DATA"></p><!--投稿画像-->
            </details>    
        </div>
    </div>  
</div>
    
</body>
</html>