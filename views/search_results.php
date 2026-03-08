<?php
$posts = search_posts(trim(body('query', '')));

if (empty($posts)): ?>
    <li class="post-item" style="color:var(--color-muted);">No posts found.</li>
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
