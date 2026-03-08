<?php
$title = 'Local Fun';
$posts = get_posts();
?>
<?php require 'views/components/header.php'; ?>

<div class="page">
    <div class="page-header">
        <h1 class="page-title">Local Fun</h1>
        <a href="/posts/create" class="btn">+ New Post</a>
    </div>

    <form id="search-form" class="form-group">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="text" name="query" id="search-input" class="form-input" placeholder="Search posts...">
    </form>

    <ul class="post-list" id="post-list">
        <?php if (empty($posts)): ?>
            <li class="post-item" style="color:var(--color-muted);">No posts yet. <a href="/posts/create">Write the first one.</a></li>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <li class="post-item">
                    <a href="/posts/<?= htmlspecialchars($post['slug']) ?>" class="post-item-title">
                        <?= htmlspecialchars($post['title']) ?>
                    </a>
                    <div class="post-meta"><?= format_date($post['created_at']) ?></div>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>
    </ul>
</div>

<script>
    const input = document.getElementById('search-input');
    const form = document.getElementById('search-form');
    const list = document.getElementById('post-list');
    let timer;

    input.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => {
            fetch('/search', { method: 'POST', body: new FormData(form) })
                .then(r => r.text())
                .then(html => { list.innerHTML = html; });
        }, 300);
    });
</script>

<?php require 'views/components/footer.php'; ?>
