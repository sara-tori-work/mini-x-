<?php
// セッション開始（CSRFトークンの保存に必須）
session_start();

// CSRFトークンがなければ生成してセッションに保存
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// POST字にトークンを検証する関数
function verifyCsrfToken(): void
{
    $token = $_POST['csrf_token'] ?? '';
    // hash_equalsはタイミング攻撃対策済みの安全な文字列比較
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        exit('不正なリクエストです。');
    }
}

// config.phpを読み込む
/** @var string $dsn */
/** @var string $username */
/** @var string $password */
require_once __DIR__ . '/config.php';

// config.phpの変数をDBに接続する
try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // エラー時に例外を投げる
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // データを連想配列で取得
    ]);
} catch (PDOException $e) {
    exit('データベース接続失敗：' . $e->getMessage());
}

// uploadsディレクトリの存在確認と自動作成
const UPLOAD_DIR = __DIR__ . '/uploads';

if (!is_dir(UPLOAD_DIR)) {
    // 第3引数(true)で親ディレクトリ事再帰的に作成
    // 0755はディレクトリの権限
    mkdir(UPLOAD_DIR, 0755, true);
}

// 画像アップロードで許可する拡張子
const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
// ファイルサイズ上限定義（2MB）
const MAX_UPLOAD_SIZE = 2 * 1024 * 1024;

// つぶやくボタンが押されたときの処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['message'])) {
    // 投稿処理の前にチェック
    verifyCsrfToken();

    // 名前が空っぽの場合「名無しさん」
    $name = !empty($_POST['name']) ? $_POST['name'] : "名無しさん";
    $message = $_POST['message'];
    // 画像がないときはnull
    $image_name = null;

    // 画像アップロード処理 start-----------------------------
    // ファイルがアップロードされる＆エラーがないかチェック
    if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        // サイズチェック
        if ($_FILES['image']['size'] > MAX_UPLOAD_SIZE) {
            $_SESSION['error_message'] = "画像サイズは2MB以内にしてください。";
            // 名前とメッセージをセッションに保存
            $_SESSION['old_input'] = ['name' => $_POST['name'] ?? '', 'message' => $_POST['message'] ?? ''];
            // index.phpにリダイレクト
            header('Location: index.php');
            exit;
        }

        // 実際に画像として読み込めるか確認
        if (getimagesize($_FILES['image']['tmp_name']) === false) {
            $_SESSION['error_message'] = "画像ファイルではありません。";
            // 名前とメッセージをセッションに保存
            $_SESSION['old_input'] = ['name' => $_POST['name'] ?? '', 'message' => $_POST['message'] ?? ''];
            // index.phpにリダイレクト
            header('Location: index.php');
            exit;
        }

        // アップロードされたファイルの“元の名前”を取得
        $original_name = $_FILES['image']['name'];

        // 他の人が同名ファイルを上げたときに上書きされないよう、ランダムな名前を作る
        // 拡張子（.jpgや.pngを取得）
        $extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

        // 拡張子が画像として許可されたものか確認
        if (in_array($extension, ALLOWED_EXTENSIONS, true)) {

            // uniqid()はマイクロ秒単位の現在時刻→ユニーク文字列IDに生成
            $image_name = time() . '_' . uniqid() . '.' . $extension;

            // 一時的に保存されている場所から、作った「uploads」フォルダへ移動させる
            $uploads_path = UPLOAD_DIR . '/' . $image_name;

            // move_uploaded_fileの戻り値をチェックし、失敗したらDBに保存しない
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploads_path)) {
                $image_name = null;
            }
        } else {
            // 拡張子エラーのメッセージを残してリダイレクト
            $_SESSION['error_message'] = "対応していない画像形式です。（jpg, jpeg, png, gif, webpのみ）";
            header('Location: index.php');
            exit;
        }
    }
    // 画像アップロード処理 end------------------------------

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
    // 削除処理の前にチェック
    verifyCsrfToken();

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
        $file_path  = UPLOAD_DIR . '/' . $target['image'];
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

    <?php // PWA化のためのタグを追加 ?>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#1da1f2">
    <link rel="apple-touch-icon" href="/app-icons/icon-192.png">
</head>

<body class="site-body">
    <header>
        <h1>簡易版X（Twitterクローン）</h1>
    </header>
    <main>
        <?php // enctype="multipart/form-data"をつけることでPHPに画像を届ける
        ?>
        <form action="index.php" method="POST" enctype="multipart/form-data" class="form-post">
            <?php // 変更点：CSRFトークンを埋め込む
            ?>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
            <input type="text" name="name" placeholder="お名前（省略可）" value="<?php echo htmlspecialchars($_SESSION['old_input']['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            <br>
            <textarea id="postTextarea"
                name="message"
                rows="4" cols="40"
                placeholder="いまどうしてる？"
                required><?php
                            echo htmlspecialchars($_SESSION['old_input']['message'] ?? '', ENT_QUOTES, 'UTF-8');
                            unset($_SESSION['old_input']); // 表示したら破棄
                            ?></textarea>
            <br>
            <input type="file" id="postImageInput" name="image" accept="image/*">
            <?php if (!empty($_SESSION['error_message'])) : ?>
                <br>
                <p class="img-error-message">
                    <?php
                    echo htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8');
                    // 表示したエラーは破棄
                    unset($_SESSION['error_message']);
                    ?>
                </p>
            <?php endif; ?>
            <br>
            <p id="imgErrorMessage" class="img-error-message"></p>
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
                        $image_url = $scheme . $_SERVER['HTTP_HOST'] . "/uploads/" . htmlspecialchars($post['image'], ENT_QUOTES, 'UTF-8');
                        ?>
                        <div class="post-image">
                            <img src="<?php echo $image_url; ?>" alt="投稿画像">
                        </div>
                    <?php endif; ?>
                    <?php // 日時もhtmlspecialcharsを使って出力。一貫性
                    ?>
                    <small class="post-date"><?php echo htmlspecialchars($post['created_at'], ENT_QUOTES, 'UTF-8'); ?></small>

                    <form action="index.php" method="POST" class="delete-wrap">
                        <?php // 変更点：CSRFトークンを埋め込む
                        ?>
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
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
    <script src="./javascript/main.js" defer></script>
</body>

</html>
