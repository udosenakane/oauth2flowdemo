<?php require_once 'header.php' ?>

<main>
    <h1>Welcome Home</h1>

    <?php if (!empty($_SESSION['access_token'])): ?>
        <a href="<?php echo profile(); ?>">Profile</a>
    <?php else: ?>
        <a href="<?php echo login(); ?>">Login</a>
    <?php endif ?>

</main>

<?php require_once 'footer.php' ?>