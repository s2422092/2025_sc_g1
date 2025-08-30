-- ========================
-- ユーザー認証 / プロフィール
-- ========================
DROP TABLE IF EXISTS userauth CASCADE;
CREATE TABLE userauth (
    uid SERIAL PRIMARY KEY,
    uname TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    pw TEXT NOT NULL, --ハッシュ化して保存してね
    profileImage TEXT, --画像パスがここに入る
    height TEXT, --身長選択式(150cm代　とかなので、文字列)
    frame TEXT, --体型選択式(細め、普通、がっちり等) 
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ========================
-- 投稿（コーディネート）
-- ========================
DROP TABLE IF EXISTS post_coordinate CASCADE;
CREATE TABLE post_coordinate (
    post_id SERIAL PRIMARY KEY,
    uid INT NOT NULL REFERENCES userauth(uid) ON DELETE CASCADE, --投稿者id。もし、投稿者のaccountが削除されたら投稿も消える
    post_text VARCHAR(40), --投稿内容
    coordinateImage_path TEXT[5] NOT NULL, -- 可変長配列（5枚まで）
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ========================
-- 褒め言葉リスト（事前定義）
-- ========================
DROP TABLE IF EXISTS compliment_list CASCADE;
CREATE TABLE compliment_list (
    compliment_id SERIAL PRIMARY KEY,
    compliment_text TEXT NOT NULL UNIQUE
);

-- ========================
-- 褒め言葉（ユーザーが投稿につける）
-- ========================
DROP TABLE IF EXISTS post_compliment CASCADE;
CREATE TABLE post_compliment (
    post_compliment_id SERIAL PRIMARY KEY,
    post_id INT NOT NULL REFERENCES post_coordinate(post_id) ON DELETE CASCADE,
    uid INT NOT NULL REFERENCES userauth(uid) ON DELETE CASCADE,
    compliment_id INT REFERENCES compliment_list(compliment_id), -- リスト選択も可
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ========================
-- フォロー機能
-- ========================
DROP TABLE IF EXISTS user_follow CASCADE;
CREATE TABLE user_follow (
    follow_id SERIAL PRIMARY KEY,
    follower_uid INT NOT NULL REFERENCES userauth(uid) ON DELETE CASCADE, -- フォローする人
    followee_uid INT NOT NULL REFERENCES userauth(uid) ON DELETE CASCADE, -- フォローされる人
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(follower_uid, followee_uid), -- 重複禁止
    CHECK (follower_uid <> followee_uid) -- 自分自身をフォロー禁止
);


-- ========================
-- 褒め言葉事前挿入
-- ========================
INSERT INTO compliment_list (compliment_text) VALUES
('素敵！'),
('かわいい！'),
('かっこいい！'),
('おしゃれ！'),
('似合ってる！'),
('最高！'),
('いいセンス！'),
('爽やか！'),
('スタイリッシュ！'),
('大人っぽい！'),
('かわいらしい！'),
('イケてる！'),
('上品！'),
('クール！'),
('明るい雰囲気！'),
('元気が出る！'),
('センス抜群！'),
('お似合いです！'),
('キマってる！'),
('素晴らしい！');

?// これでデータベースのセットアップは完了です。