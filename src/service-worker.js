// キャッシュに使う名前とバージョン
// 更新のたびに数字を挙げる
const CACHE_NAME = 'simple-x-cache-v1';

// オフラインでも表示したい静的ファイル一覧
const STATIC_ASSETS = [
    '/css/style.css',
    '/javascript/main.js'
];

// インストール時：静的ファイルを事前にキャッシュしておく
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(STATIC_ASSETS))
    );
});

// 古いキャッシュの掃除（Verが変わったら前のキャッシュを削除）
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(
                keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
            )
        )
    );
});

// リクエストが来た時の挙動
self.addEventListener('fetch', (event) =>{
    // v1.7.2追加：ET以外のリクエスト（POST送信・投稿・削除など）はService Workerが横取りせず、ブラウザにそのまま処理させる。event.respondWith()を呼ばなければ、ブラウザの標準動作になる。
    if (event.request.method !== 'GET') {
        return;
    }

    const url = new URL(event.request.url);

    // index.phpのような動的ページは常に最新をネットワークから取得する
    if(url.pathname === 'index.php' || url.pathname === '/'){
        event.respondWith(
            fetch(event.request).catch(() => caches.match(event.request))
        );
        return;
    }

    // css、jsなどの静的ファイルはキャッシュ優先
    // 高速表示に対応させる
    event.respondWith(
        caches.match(event.request).then((cached) => cached || fetch(event.request))
    );
});
