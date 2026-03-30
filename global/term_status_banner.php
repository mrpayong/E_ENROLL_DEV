<?php
/**
 * Shared banner for indicating whether the currently selected
 * school year / semester is open for enrollment or read-only.
 *
 * Expected variables from the including scope:
 * - $is_term_open (bool) : true when enrollment is allowed
 * - $current_sy (array)  : should contain 'school_year' and 'sem'
 */

$sy_text  = isset($current_sy['school_year']) ? (string)$current_sy['school_year'] : '';
$sem_text = isset($current_sy['sem']) ? (string)$current_sy['sem'] : '';
$term_label = trim($sy_text . ' - ' . $sem_text);

$is_open = !empty($is_term_open);
// Use the custom blue .enroll-alert style only when the term is
// open; for closed terms rely on the standard Bootstrap danger
// styling so the banner is clearly red.
if ($is_open) {
    $banner_class = 'alert enroll-alert d-flex align-items-center p-4 mb-4';
    $icon_class = 'fas fa-info-circle';
} else {
    // For closed terms, keep the standard danger styling so text
    // remains readable on a light background.
    $banner_class = 'alert alert-danger d-flex align-items-center p-4 mb-4';
    $icon_class = 'fas fa-exclamation-triangle';
}
?>

<div class="<?php echo $banner_class; ?>" role="alert">
    <i class="<?php echo $icon_class; ?> fa-2x me-3"></i>
    <div>
        <?php if ($is_open): ?>
            <h5 class="alert-heading mb-1 fw-bold">Enrollment Period is Ongoing</h5>
            <p class="mb-0">
                You may enlist your subjects for the current school year and semester.
            </p>
        <?php else: ?>
            <h5 class="alert-heading mb-1 fw-bold">Enrollment for This Term is Closed</h5>
            <p class="mb-0">
                You are viewing a closed school year and semester. This page is read-only;
                you can review your information for this term, but new enrollment changes
                are not allowed.
            </p>
        <?php endif; ?>
        <?php if ($term_label !== ''): ?>
            <p class="mb-0 small mt-1 text-muted">
                Term: <strong><?php echo htmlspecialchars($term_label, ENT_QUOTES, 'UTF-8'); ?></strong>
            </p>
        <?php endif; ?>
    </div>
</div>
