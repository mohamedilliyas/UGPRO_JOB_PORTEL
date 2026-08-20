<?php
/**
 * Alert Flash Component - UgPro
 */
$flash = get_flash();
if ($flash): 
    $type = $flash['type'] ?? 'info';
    $message = $flash['message'] ?? '';
    $icon = 'bi-info-circle-fill';
    if ($type === 'success') $icon = 'bi-check-circle-fill';
    if ($type === 'danger') $icon = 'bi-exclamation-triangle-fill';
    if ($type === 'warning') $icon = 'bi-exclamation-circle-fill';
?>
<div class="container mt-3">
    <div class="alert alert-<?= htmlspecialchars($type) ?> alert-dismissible fade show d-flex align-items-center shadow-sm" role="alert" style="border-radius: 12px;">
        <i class="bi <?= $icon ?> me-2 fs-5"></i>
        <div><?= htmlspecialchars($message) ?></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
</div>
<?php endif; ?>
