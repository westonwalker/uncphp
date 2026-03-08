<?php
$post = get_post_by_slug(param('slug'));
if (!$post) abort(404);

$title = $post['title'] . ' — Local Fun';
?>
<?php require 'views/components/header.php'; ?>

<div class="page">
    <a href="/" class="back-link">&larr; Back</a>

    <div class="page-header">
        <h1 class="page-title"><?= htmlspecialchars($post['title']) ?></h1>
        <a href="/posts/<?= htmlspecialchars($post['slug']) ?>/edit" class="btn btn-outline">Edit</a>
    </div>

    <div class="post-meta mb-6"><?= format_date($post['created_at']) ?></div>

    <div class="post-body">
        <?= nl2br(htmlspecialchars($post['body'])) ?>
    </div>
</div>

<?php require 'views/components/footer.php'; ?>
