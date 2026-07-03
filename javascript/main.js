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

        // エラーメッセージを溜めるための配列
        let errors = [];

        // サイズチェック
        const MAX_SIZE = 2 * 1024 * 1024;
        if (file.size > MAX_SIZE) {
            errors.push("画像サイズは2MB以内にしてください。");
        }

        // 実際に画像として読み込めるか確認
        // startsWithの中身はデータの種類（MIMEタイプ）
        if (!file.type.startsWith('image/')) {
            errors.push("画像ファイルではありません。");
        }

        // エラーが1つでもあれば表示して選択をキャンセル
        if(errors.length > 0){
            if(errorEl){
                // エラーを繋げて改行して表示
                errorEl.innerHTML = errors.join('<br />');
            }
            e.target.value = ''; // 選択をキャンセル
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
