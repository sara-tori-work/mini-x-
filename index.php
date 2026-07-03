<?php

// config.phpを読み込む
require_once 'config.php';

// config.phpの変数をDBに接続する
try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // エラー時に例外を投げる
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // データを連想配列で取得
    ]);
} catch (PDOException $e) {
    exit('データベース接続失敗：' . $e->getMessage());
}

// 画像アップロードで許可する拡張子
const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
// ファイルサイズ上限定義（2MB）
const MAX_UPLOAD_SIZE = 2 * 1024 * 1024;

// つぶやくボタンが押されたときの処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['message'])) {
    // 名前が空っぽの場合「名無しさん」
    $name = !empty($_POST['name']) ? $_POST['name'] : "名無しさん";
    $message = $_POST['message'];
    // 画像がないときはnull
    $image_name = null;

    // 画像アップロード処理 start-----------------------------
    // ファイルがアップロードされる＆エラーがないかチェック
    if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        // サイズチェック
        if($_FILES['image']['size'] > MAX_UPLOAD_SIZE){
            exit("画像サイズは2MB以内にしてください");
        }

        // 実際に画像として読み込めるか確認
        if(getimagesize($_FILES['image']['tmp_name']) === false){
            exit("画像ファイルではありません");
        }

        // アップロードされたファイルの“元の名前”を取得
        $original_name = $_FILES['image']['name'];

        // 他の人が同名ファイルを上げたときに上書きされないよう、ランダムな名前を作る
        // 拡張子（.jpgや.pngを取得）
        $extension = pathinfo($original_name, PATHINFO_EXTENSION);

        // 拡張子が画像として許可されたものか確認
        if (in_array($extension, ALLOWED_EXTENSIONS, true)) {

            // uniqid()はマイクロ秒単位の現在時刻→ユニーク文字列IDに生成
            $image_name = time() . '_' . uniqid() . '.' . $extension;

            // 一時的に保存されている場所から、作った「uploads」フォルダへ移動させる
            $uploads_path = 'uploads/' . $image_name;

            // move_uploaded_fileの戻り値をチェックし、失敗したらDBに保存しない
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploads_path)) {
                $image_name = null;
            }
        }
    }

    // データベースに保存する命令（SQL文）
    $stmt = $pdo->prepare('INSERT INTO posts (name, message, image, created_at) VALUES (:name, :message, :image, NOW())');
    $stmt->bindValue(':name', $name, PDO::PARAM_STR);
    $stmt->bindValue(':message', $message, PDO::PARAM_STR);
    $stmt->bindValue(':image', $image_name, PDO::PARAM_STR); // 画像名を保存

    // 実行
    $stmt->execute();

    // 画面を読み込み→二重投稿を防ぐ
    header('Location: index.php');
    exit;
}

// 削除ボタンが押されたときの処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];

    // 削除前に画像ファイルを取得し、DB削除後に物理ファイルも削除する
    $stmt = $pdo->prepare('SELECT image FROM posts WHERE id = :id');
    $stmt->bindValue(':id', $delete_id, PDO::PARAM_INT);
    // 実行
    $stmt->execute();
    $target = $stmt->fetch(PDO::FETCH_ASSOC);

    // DBから指定されたIDの投稿を削除するSQL文
    $stmt = $pdo->prepare('DELETE FROM posts WHERE id = :id');
    $stmt->bindValue(':id', $delete_id, PDO::PARAM_INT);

    // 実行
    $stmt->execute();

    // 投稿削除に成功したら紐づく画像ファイルもuploadsフォルダから削除
    if ($target && !empty($target['image'])) {
        $file_path  = 'uploads/' . $target['image'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }

    // 画面を再読み込み
    header('Location: index.php');
    exit;
}

// DBから今までの投稿をすべて取得する
$stmt = $pdo->query('SELECT * FROM posts ORDER BY created_at DESC');
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>簡易版X</title>
    <link rel="stylesheet" href="./css/style.css">
</head>

<body class="site-body">
    <header>
        <h1>簡易版X（Twitterクローン）</h1>
    </header>
    <main>
        <?php // enctype="multipart/form-data"をつけることでPHPに画像を届ける
        ?>
        <form action="index.php" method="POST" enctype="multipart/form-data" class="form-post">
            <input type="text" name="name" placeholder="お名前（省略可）">
            <br>
            <textarea id="postTextarea" name="message" rows="4" cols="40" placeholder="いまどうしてる？" required></textarea>
            <br>
            <input type="file" name="image" accept="image/*">
            <br>
            <button id="submitBtn" type="submit">つぶやく</button>
        </form>

        <hr>

        <h2>タイムライン</h2>
        <?php if (empty($posts)) : ?>
            <p>まだ投稿はありません。</p>
        <?php else: ?>
            <?php foreach ($posts as $post) : ?>
                <div class="post-wrap">
                    <strong><?php echo !empty($post['name']) ? htmlspecialchars($post['name'], ENT_QUOTES, 'UTF-8') : "名無しさん"; ?></strong>
                    <p class="post-text"><?php echo htmlspecialchars($post['message'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php if (!empty($post['image'])) : ?>
                        <?php
                        // 画像URL設定。　.は?：より優先順位が低いためかっこで結合対象を明示する。
                        $scheme = !empty($_SERVER['HTTPS']) ? 'https://' : 'http://';
                        $image_url = $scheme . $_SERVER['HTTP_HOST'] . "/bbs/uploads/" . htmlspecialchars($post['image'], ENT_QUOTES, 'UTF-8');
                        ?>
                        <div class="post-image">
                            <img src="<?php echo $image_url; ?>" alt="投稿画像">
                        </div>
                    <?php endif; ?>
                    <?php // 日時もhtmlspecialcharsを使って出力。一貫性
                    ?>
                    <small class="post-date"><?php echo htmlspecialchars($post['created_at'], ENT_QUOTES, 'UTF-8'); ?></small>

                    <form action="index.php" method="POST" class="delete-wrap">
                        <?php //type="hidden"で画面には見えない入力欄を指定（投稿IDで削除したいポストを指定）
                        ?>
                        <input type="hidden" name="delete_id" value="<?php echo $post['id']; ?>">
                        <?php //onclick="return confirm()"で本当に削除する？の確認をJavaScriptのポップアップを使って表示
                        ?>
                        <button type="submit" onclick="return confirm('本当に削除しますか？');">削除</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
    <script src="./javascript/javascript.js" defer></script>
</body>

</html>
