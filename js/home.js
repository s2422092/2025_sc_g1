document.querySelectorAll('.comment-expand-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const commentBox = btn.closest('.comment-box');
        commentBox.classList.toggle('expanded');

        const textArea = commentBox.querySelector('.comment-area');
        const submitBtn = commentBox.querySelector('.comment-submit');

        if(commentBox.classList.contains('expanded')){
            textArea.focus(); // 拡大時に自動フォーカス

            // JSでボタンの高さをコメント欄とコメントリストに合わせる
            const totalHeight = commentBox.clientHeight;
            const usedHeight = textArea.offsetHeight + commentBox.querySelector('.comment-list').offsetHeight + 30; // 余白分足す
            submitBtn.style.height = (totalHeight - usedHeight) + "px";
        } else {
            submitBtn.style.height = ""; // 元に戻す
        }
    });
});
