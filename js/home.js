document.querySelectorAll('.comment-expand-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const commentBox = btn.closest('.comment-box');
        commentBox.classList.toggle('expanded');
        const textArea = commentBox.querySelector('.comment-area');
        if(commentBox.classList.contains('expanded')){
            textArea.focus(); // 拡大時に自動フォーカス
        }
    });
});
