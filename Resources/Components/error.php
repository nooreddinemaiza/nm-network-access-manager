<?php
if (!empty($error)) {
    $errorMsg = is_array($error) ? reset($error) : $error;
    if (is_string($errorMsg) && trim($errorMsg) !== '') {
?>
        <div class="p-2"><?= $view->e($errorMsg) ?></div>
<?php
    }
}
?>