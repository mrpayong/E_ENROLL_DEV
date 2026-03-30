<?php
// Shared Fiscal Year selection modal for all roles (Dean, Student, etc.)
// Include this once on pages that use the main layout, typically via
// global/include_bottom.php so it appears before the closing </body>.
?>
<div class="modal fade" id="fiscalYearModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <label class="modal-title fs-4 fw-bold">Set Fiscal Year</label>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="fiscal_year_select" class="form-label fw-bold">Fiscal Year</label>
                    <select id="fiscal_year_select" class="form-select">
                        <option value="">Loading...</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btn_set_fiscal_year">Set</button>
            </div>
        </div>
    </div>
</div>
