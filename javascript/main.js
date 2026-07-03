// ==========================================
//  画像確認
//  HTMLから持ってくる要素はキャメルケースで書く
// ==========================================
// ID:postImageInputを確認
const postImageInput = document.getElementById('postImageInput');
if (postImageInput) {
    postImageInput.addEventListener('change', function (e) {
        const file = e.target.files[0];
        const errorEl = document.getElementById('imgErrorMessage');

        // エラーメッセージを一時クリア
        if (errorEl) errorEl.textContent = '';
        if (!file) return;

        // サイズチェック
        const MAX_SIZE = 2 * 1024 * 1024;
        if (file.size > MAX_SIZE) {
            if (errorEl) errorEl.textContent = "画像サイズは2MB以内にしてください";
            e.target.value = ''; // 選択をキャンセル
            return;
        }

        // 実際に画像として読み込めるか確認
        // startsWithの中身はデータの種類（MIMEタイプ）
        if (!file.type.startsWith('image/')) {
            if (errorEl) errorEl.textContent = "画像ファイルではありません";
            e.target.value = ''; // 選択をキャンセル
            return;
        }
    });
}

// ==========================================
//  送信ボタン変化
// ==========================================
const postTextarea = document.getElementById('postTextarea');
const submitButton = document.getElementById('submitBtn');

if (postTextarea && submitButton) {
    // 投稿が入力されたら
    postTextarea.addEventListener('input', () => {
        // 文字が入力されたら色を変える
        if (postTextarea.value.trim() !== '') {
            submitButton.classList.add('active');
        } else {
            submitButton.classList.remove('active');
        }
    });
}
