<?php
$title = 'New Post — Local Fun';
$errors = [];
$form_title = '';
$form_body = '';

if (is_method('POST')) {
    $form_title = trim(body('title', ''));
    $form_body = trim(body('body', ''));

    if ($form_title === '') $errors[] = 'Title is required.';
    if ($form_body === '') $errors[] = 'Body is required.';

    if (empty($errors)) {
        $slug = slugify($form_title);
        $base = $slug;
        $i = 2;
        while (get_post_by_slug($slug)) {
            $slug = $base . '-' . $i++;
        }
        create_post($form_title, $slug, $form_body);
        redirect('/posts/' . $slug);
    }
}
?>
<?php require 'views/components/header.php'; ?>

<div class="page">
    <a href="/" class="back-link">&larr; Back</a>
    <h1 class="page-title-standalone">New Post</h1>

    <?php if (!empty($errors)): ?>
        <ul class="alert-danger">
            <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="POST" action="/posts/create">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

        <div class="form-group">
            <label for="title" class="form-label">Title</label>
            <input type="text" id="title" name="title" class="form-input" value="<?= htmlspecialchars($form_title) ?>">
        </div>

        <div class="form-group">
            <label for="body" class="form-label">Body</label>
            <textarea id="body" name="body" class="form-textarea" rows="12"><?= htmlspecialchars($form_body) ?></textarea>
        </div>

        <button type="submit" class="btn">Publish</button>
    </form>
</div>

<?php require 'views/components/footer.php'; ?>
