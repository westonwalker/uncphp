<?php
$post = get_post_by_slug(param('slug'));
if (!$post) abort(404);

$title = 'Edit Post — Local Fun';
$errors = [];
$form_title = $post['title'];
$form_body = $post['body'];

if (is_method('POST')) {
    $form_title = trim(body('title', ''));
    $form_body = trim(body('body', ''));

    if ($form_title === '') $errors[] = 'Title is required.';
    if ($form_body === '') $errors[] = 'Body is required.';

    if (empty($errors)) {
        update_post($post['id'], $form_title, $form_body);
        redirect('/posts/' . $post['slug']);
    }
}
?>
<?php require 'views/components/header.php'; ?>

<div class="page">
    <a href="/posts/<?= htmlspecialchars($post['slug']) ?>" class="back-link">&larr; Back</a>
    <h1 class="page-title-standalone">Edit Post</h1>

    <?php if (!empty($errors)): ?>
        <ul class="alert-danger">
            <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="POST" action="/posts/<?= htmlspecialchars($post['slug']) ?>/edit">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

        <div class="form-group">
            <label for="title" class="form-label">Title</label>
            <input type="text" id="title" name="title" class="form-input" value="<?= htmlspecialchars($form_title) ?>">
        </div>

        <div class="form-group">
            <label for="body" class="form-label">Body</label>
            <textarea id="body" name="body" class="form-textarea" rows="12"><?= htmlspecialchars($form_body) ?></textarea>
        </div>

        <button type="submit" class="btn">Save Changes</button>
    </form>
</div>

<?php require 'views/components/footer.php'; ?>
