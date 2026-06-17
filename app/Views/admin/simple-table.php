<?php
$headings = array_keys($rows[0] ?? ['record' => '']);
$tableId = trim('table-' . strtolower(preg_replace('/[^a-z0-9]+/i', '-', (string) $title)), '-') ?: 'table-records';
$colspan = count($headings) + 1;
$tableName = $tableName ?? strtolower(str_replace(' ', '_', (string) $title));
$filterDefinitions = $filterDefinitions ?? [];
$savedViews = $savedViews ?? [];
$activeSavedView = $activeSavedView ?? null;
$tablePreferences = $tablePreferences ?? [];
$bulkActions = $bulkActions ?? [['key' => 'export_selected', 'label' => 'Export selected CSV', 'icon' => 'ti-download']];
$activeViewConfig = $activeSavedView ? [
    'filters' => json_decode((string) ($activeSavedView['filters_json'] ?? '[]'), true) ?: [],
    'columns' => json_decode((string) ($activeSavedView['columns_json'] ?? '[]'), true) ?: [],
    'sort' => json_decode((string) ($activeSavedView['sort_json'] ?? '[]'), true) ?: [],
] : [];
$statusClass = static function ($value): string {
    $value = strtolower((string) $value);
    return match ($value) {
        'active', 'issued', 'passed', 'paid', 'sent', 'verified', '1' => 'bg-success-lt',
        'submitted', 'in_review', 'awaiting_evidence', 'draft', 'pending', 'invited' => 'bg-blue-lt',
        'failed', 'expired', 'cancelled', 'inactive', 'disabled', 'bounced', '0' => 'bg-danger-lt',
        'changes_requested', 'archived', 'requested' => 'bg-warning-lt',
        default => 'bg-secondary-lt',
    };
};
$formatValue = static function (string $key, $value): string {
    if ($key === 'amount_cents' && is_numeric($value)) {
        return '$' . number_format(((int) $value) / 100, 2);
    }
    if ($key === 'is_active') {
        return ((int) $value) === 1 ? 'active' : 'inactive';
    }
    if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}:\d{2})?$/', $value)) {
        return date('d M Y', strtotime($value));
    }
    return (string) $value;
};
?>
<div class="card" data-enhanced-table data-table-name="<?= e($tableName) ?>" data-table-preferences="<?= e(json_encode($tablePreferences, JSON_UNESCAPED_SLASHES)) ?>" data-active-view="<?= e(json_encode($activeViewConfig, JSON_UNESCAPED_SLASHES)) ?>">
    <div class="card-table">
        <div class="card-header">
            <div class="row w-100 g-2 align-items-center">
                <div class="col">
                    <h3 class="card-title mb-0"><?= e($title) ?></h3>
                    <p class="text-secondary m-0"><span data-table-total><?= e((string) count($rows)) ?></span> records</p>
                </div>
                <div class="col-md-auto col-sm-12">
                    <div class="ms-auto d-flex flex-wrap btn-list">
                        <div class="input-group input-group-flat table-search">
                            <span class="input-group-text"><i class="ti ti-search"></i></span>
                            <input type="text" class="form-control" autocomplete="off" placeholder="Search" data-table-search>
                            <span class="input-group-text"><kbd>ctrl + K</kbd></span>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-icon" data-bs-toggle="dropdown" type="button" aria-label="Table options">
                                <i class="ti ti-dots"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <button class="dropdown-item" type="button" data-table-clear><i class="ti ti-filter-off me-2"></i>Clear filters</button>
                                <button class="dropdown-item" type="button" data-table-save-view><i class="ti ti-device-floppy me-2"></i>Save current view</button>
                                <div class="dropdown-divider"></div>
                                <h6 class="dropdown-header">Columns</h6>
                                <?php foreach ($headings as $heading): ?>
                                    <label class="dropdown-item">
                                        <input class="form-check-input m-0 me-2" type="checkbox" checked data-table-column-toggle="<?= e((string) $heading) ?>">
                                        <?= e(ucwords(str_replace('_', ' ', $heading))) ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="dropdown">
                            <button class="btn dropdown-toggle" data-bs-toggle="dropdown" type="button" data-table-bulk-button disabled>
                                <i class="ti ti-checklist me-1"></i>Selected
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <?php foreach ($bulkActions as $action): ?>
                                    <button class="dropdown-item" type="button" data-table-bulk-action="<?= e($action['key']) ?>"><i class="ti <?= e($action['icon']) ?> me-2"></i><?= e($action['label']) ?></button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="dropdown">
                            <button class="btn dropdown-toggle" data-bs-toggle="dropdown" type="button">
                                <i class="ti ti-download me-1"></i>Download
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <button class="dropdown-item" type="button" data-table-export="csv">Export CSV</button>
                                <button class="dropdown-item" type="button" data-table-export="excel">Export Excel</button>
                                <button class="dropdown-item" type="button" data-table-export="copy">Copy CSV</button>
                            </div>
                        </div>
                        <form method="post" action="/admin/imports" enctype="multipart/form-data" class="m-0" data-table-import-form>
                            <?= csrf_field() ?>
                            <input type="hidden" name="redirect_to" value="<?= e(parse_url($_SERVER['REQUEST_URI'] ?? '/admin/clients', PHP_URL_PATH) ?: '/admin/clients') ?>">
                            <input type="hidden" name="table" value="<?= e($tableName) ?>">
                            <input class="d-none" type="file" name="import_file" accept=".xlsx,.xls,.csv" data-table-import-file>
                            <button class="btn" type="button" data-table-import-trigger><i class="ti ti-upload me-1"></i>Import</button>
                        </form>
                        <?php if (!empty($createUrl)): ?>
                            <a class="btn btn-primary" href="<?= e($createUrl) ?>">
                                <i class="ti ti-plus me-1"></i>New
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php if ($savedViews || $filterDefinitions): ?>
            <div class="card-header py-2">
                <div class="w-100">
                    <?php if ($savedViews): ?>
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <a class="btn btn-sm <?= $activeSavedView ? 'btn-outline-secondary' : 'btn-primary' ?>" href="<?= e(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '#') ?>">All</a>
                            <?php foreach ($savedViews as $view): ?>
                                <a class="btn btn-sm <?= $activeSavedView && (int) $activeSavedView['id'] === (int) $view['id'] ? 'btn-primary' : 'btn-outline-secondary' ?>" href="?saved_view=<?= e((string) $view['id']) ?>"><?= e($view['name']) ?></a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($filterDefinitions): ?>
                        <div class="row g-2" data-table-filter-bar>
                            <?php foreach ($filterDefinitions as $filter): ?>
                                <div class="col-sm-6 col-md-3 col-xl-2">
                                    <label class="form-label mb-1"><?= e($filter['label']) ?></label>
                                    <?php if (($filter['type'] ?? 'select') === 'select'): ?>
                                        <select class="form-select form-select-sm" data-table-filter="<?= e($filter['key']) ?>" data-filter-column="<?= e($filter['column']) ?>" data-filter-type="select">
                                            <option value="">Any</option>
                                            <?php foreach (($filter['options'] ?? []) as $value => $label): ?>
                                                <option value="<?= e((string) $value) ?>"><?= e((string) $label) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php elseif (($filter['type'] ?? '') === 'date_range'): ?>
                                        <div class="input-group input-group-sm">
                                            <input class="form-control" type="date" data-table-filter="<?= e($filter['key']) ?>_from" data-filter-column="<?= e($filter['column']) ?>" data-filter-type="date_from">
                                            <input class="form-control" type="date" data-table-filter="<?= e($filter['key']) ?>_to" data-filter-column="<?= e($filter['column']) ?>" data-filter-type="date_to">
                                        </div>
                                    <?php else: ?>
                                        <input class="form-control form-control-sm" type="text" data-table-filter="<?= e($filter['key']) ?>" data-filter-column="<?= e($filter['column']) ?>" data-filter-type="text" placeholder="Contains">
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        <div class="table-responsive">
            <table class="table table-vcenter table-selectable card-table text-nowrap" id="<?= e($tableId) ?>">
                <thead>
                <tr>
                    <th class="w-1">
                        <input class="form-check-input m-0 align-middle" type="checkbox" data-table-select-all aria-label="Select all records">
                    </th>
                    <?php foreach ($headings as $index => $heading): ?>
                        <th class="<?= $heading === 'id' ? 'w-1 text-secondary' : '' ?>" data-column-key="<?= e((string) $heading) ?>">
                            <button class="table-sort" type="button" data-table-sort="<?= e((string) $index) ?>">
                                <?= e(ucwords(str_replace('_', ' ', $heading))) ?>
                            </button>
                        </th>
                    <?php endforeach; ?>
                </tr>
                </thead>
                <tbody class="table-tbody">
                <?php foreach ($rows as $row): ?>
                    <?php $recordUrl = !empty($detailPrefix) && isset($row['id']) ? $detailPrefix . $row['id'] : null; ?>
                    <tr class="table-row-link" <?= $recordUrl ? 'data-href="' . e($recordUrl) . '"' : '' ?>>
                        <td>
                            <input class="form-check-input m-0 align-middle table-selectable-check" type="checkbox" aria-label="Select record">
                        </td>
                        <?php foreach ($row as $key => $value): ?>
                            <?php $displayValue = $formatValue((string) $key, $value); ?>
                            <td class="<?= $key === 'id' ? 'text-secondary text-nowrap' : '' ?>" data-column-key="<?= e((string) $key) ?>" data-filter-value="<?= e(strtolower((string) $value)) ?>">
                                <?php if (in_array($key, ['status', 'stored_status', 'computed_status', 'pdf_status', 'result', 'role', 'is_active'], true)): ?>
                                    <span class="badge <?= e($statusClass($displayValue)) ?> status-badge"><?= e(str_replace('_', ' ', $displayValue)) ?></span>
                                <?php elseif (str_contains((string) $key, 'email') && $displayValue !== ''): ?>
                                    <a href="mailto:<?= e($displayValue) ?>" class="text-reset"><?= e($displayValue) ?></a>
                                <?php elseif ($key === array_key_first($row) && $recordUrl): ?>
                                    <a href="<?= e($recordUrl) ?>" class="text-reset"><?= e($displayValue) ?></a>
                                <?php else: ?>
                                    <?= e($displayValue) ?>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$rows): ?>
                    <tr data-empty-row>
                        <td colspan="<?= e((string) $colspan) ?>">
                            <div class="empty">
                                <div class="empty-icon"><i class="ti ti-database-off"></i></div>
                                <p class="empty-title">No records found</p>
                                <p class="empty-subtitle text-secondary">Create a record or adjust the current filters.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex align-items-center">
            <div class="dropdown">
                <button class="btn dropdown-toggle" data-bs-toggle="dropdown" type="button">
                    <span data-table-page-size-label>20</span>
                    <span class="ms-1">records</span>
                </button>
                <div class="dropdown-menu">
                    <?php foreach ([10, 20, 50, 100] as $size): ?>
                        <button class="dropdown-item" type="button" data-table-page-size="<?= e((string) $size) ?>"><?= e((string) $size) ?> records</button>
                    <?php endforeach; ?>
                </div>
            </div>
            <p class="m-0 ms-3 text-secondary d-none d-sm-block" data-table-summary>Showing records</p>
            <ul class="pagination m-0 ms-auto" data-table-pagination></ul>
        </div>
    </div>
</div>
