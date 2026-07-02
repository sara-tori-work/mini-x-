// 送信ボタン変化
const textarea = document.getElementById('postTextarea');
const submitBtn = document.getElementById('submitBtn');

// 投稿が入力されたら
textarea.addEventListener('input', () => {
    // 文字が入力されたら色を変える
    if (textarea.value.trim() !== '') {
        submitBtn.classList.add('active');
    } else {
        submitBtn.classList.remove('active');
    }
})
