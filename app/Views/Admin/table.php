<style>
/* --- Datatable Wrapper & Container --- */
.table-modern-wrapper {
    background: var(--bg-surface);
    border-radius: var(--radius-md);
    padding: 20px;
    box-shadow: var(--shadow-md);
    margin-bottom: 30px;
}

/* --- Base Table Styling --- */
table.dataTable {
    border-collapse: separate !important;
    border-spacing: 0 8px !important;
    width: 100% !important;
    border: none !important;
}

table.dataTable thead th {
    background: var(--bg-main) !important;
    color: var(--text-muted) !important;
    font-weight: 700 !important;
    text-transform: uppercase;
    font-size: 11px;
    letter-spacing: 0.5px;
    padding: 12px 15px !important;
    border: none !important;
    border-radius: 4px;
    position: relative;
    cursor: pointer;
}

table.dataTable thead th:hover {
    background-color: rgba(48, 67, 0, 0.05) !important;
}

/* --- Custom Checkbox Checkmarks --- */
.selecting, .select_all {
    appearance: none;
    -webkit-appearance: none;
    width: 18px;
    height: 18px;
    border: 2px solid var(--border-color);
    border-radius: 6px;
    background: var(--bg-surface);
    cursor: pointer;
    position: relative;
    display: inline-block;
    vertical-align: middle;
    transition: all 0.2s ease;
}

.selecting:checked, .select_all:checked {
    background-color: var(--primary);
    border-color: var(--primary);
}

.selecting:checked::after, .select_all:checked::after {
    content: "\f00c";
    font-family: "Font Awesome 5 Free";
    font-weight: 900;
    color: white;
    font-size: 10px;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

/* --- Row Styling --- */
table.dataTable tbody tr {
    transition: all 0.2s ease;
    background: var(--bg-surface);
}

table.dataTable tbody tr:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
    background: var(--bg-main);
}

table.dataTable tbody td {
    padding: 15px !important;
    vertical-align: middle;
    color: var(--text-main);
    border-top: 1px solid var(--border-color) !important;
    border-bottom: 1px solid var(--border-color) !important;
}

table.dataTable tbody td:first-child { 
    border-left: 1px solid var(--border-color) !important; 
    border-top-right-radius: 12px; 
    border-bottom-right-radius: 12px; 
}
table.dataTable tbody td:last-child { 
    border-right: 1px solid var(--border-color) !important; 
    border-top-left-radius: 12px; 
    border-bottom-left-radius: 12px; 
}

/* --- Status Badges --- */
.badge-status {
    padding: 6px 12px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 11px;
    display: inline-block;
}

.status-pending { background: rgba(218, 172, 24, 0.1); color: #d97706; }
.status-active { background: rgba(48, 67, 0, 0.1); color: #304300; }
.status-completed { background: rgba(30, 40, 0, 0.05); color: #64748b; }

/* --- Certificate Options & Custom Checkboxes --- */
.certificate-options {
    display: flex;
    gap: 15px;
    font-size: 11px;
    align-items: center;
}

.certificate-options label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    font-weight: 500;
    color: var(--text-main);
    transition: color 0.2s ease;
}

.certificate-options label:hover {
    color: var(--primary);
}

.certificate-options input[type="checkbox"] {
    appearance: none;
    -webkit-appearance: none;
    width: 16px;
    height: 16px;
    border: 2px solid var(--border-color);
    border-radius: 4px;
    background: var(--bg-surface);
    cursor: pointer;
    position: relative;
    transition: all 0.2s ease;
    margin: 0;
}

.certificate-options input[type="checkbox"]:checked {
    background-color: var(--primary);
    border-color: var(--primary);
}

.certificate-options input[type="checkbox"]:checked::after {
    content: "\f00c";
    font-family: "Font Awesome 5 Free";
    font-weight: 900;
    color: white;
    font-size: 9px;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

/* --- Styled Select Dropdowns --- */
table.dataTable select.form-control-sm {
    height: 32px !important;
    padding: 2px 10px !important;
    font-size: 12px !important;
    border-radius: 8px !important;
    border: 1px solid var(--border-color) !important;
    background-color: var(--bg-surface) !important;
    color: var(--text-main) !important;
    cursor: pointer;
    transition: all 0.2s ease;
    outline: none !important;
    min-width: 120px;
}

table.dataTable select.form-control-sm:hover {
    border-color: var(--primary) !important;
    background-color: var(--bg-main) !important;
}

table.dataTable select.form-control-sm:focus {
    border-color: var(--primary) !important;
    box-shadow: 0 0 0 2px rgba(48, 67, 0, 0.1) !important;
}

/* --- Advanced Filter UI --- */
.column-filter-dropdown {
    position: absolute;
    background: var(--bg-surface);
    border-radius: 12px;
    box-shadow: var(--shadow-xl);
    padding: 15px;
    min-width: 220px;
    z-index: 2000;
    display: none;
    border: 1px solid var(--border-color);
}

.filter-search-input {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    margin-bottom: 12px;
    font-size: 12px;
    outline: none;
    background: var(--bg-main);
}

.filter-checkbox-list {
    max-height: 180px;
    overflow-y: auto;
    margin-bottom: 12px;
}

.filter-checkbox-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 6px 0;
    cursor: pointer;
    font-size: 12px;
    color: var(--text-main);
}

.filter-checkbox-item:hover {
    color: var(--primary);
}

.filter-actions {
    display: flex;
    justify-content: space-between;
    padding-top: 10px;
    border-top: 1px solid var(--border-color);
}

.btn-filter-action {
    background: none;
    border: none;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 4px;
}

.btn-filter-apply { background: var(--primary); color: white; }
.btn-filter-clear { color: var(--text-muted); }

.filter-trigger {
    font-size: 10px;
    color: var(--text-muted);
    margin-right: 6px;
    cursor: pointer;
    transition: color 0.2s;
}

.filter-trigger.active { color: var(--primary); }

.filter-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.2);
    display: none;
    z-index: 1050;
}

/* --- Mobile View Transformation --- */
@media (max-width: 768px) {
    table.dataTable thead { display: none; }
    
    table.dataTable, 
    table.dataTable tbody, 
    table.dataTable tr, 
    table.dataTable td {
        display: block;
        width: 100% !important;
    }

    table.dataTable tr {
        margin-bottom: 20px;
        background: var(--bg-surface);
        border-radius: 12px;
        box-shadow: var(--shadow-md);
        border: 1px solid var(--border-color);
        padding: 15px;
    }

    table.dataTable td {
        display: flex;
        justify-content: space-between !important;
        align-items: center;
        padding: 10px 0 !important;
        border: none !important;
        text-align: left;
        border-bottom: 1px collapsed var(--border-color);
    }

    table.dataTable td:last-child { border-bottom: none !important; }

    table.dataTable td::before {
        content: attr(data-label);
        font-weight: 700;
        color: var(--text-muted);
        font-size: 10px;
        text-transform: uppercase;
    }

    .column-filter-dropdown {
        position: fixed !important;
        top: auto !important;
        bottom: 0 !important;
        left: 0 !important;
        right: 0 !important;
        width: 100% !important;
        border-radius: 20px 20px 0 0 !important;
    }
}
</style>

<!-- Premium DataTable View for Volunteer Activities -->
<div class="table-modern-wrapper">
    <div class="row table-responsive">
        <table class="table" id="example"></table>
    </div>
</div>

<?php 
$my_activities = $entities; 
if (empty($my_activities)): ?>
    <div class="text-center p-4">
        <i class="fa-solid fa-folder-open fa-3x text-muted mb-3"></i>
        <p class="text-muted">لم يتم العثور على نشاطات متطوعين.</p>
    </div>
<?php else:
    $dataset = '[';
    foreach ($my_activities as $row) {
        if (!isset($row->id)) continue;

        $activity = $db->table('activities')->where('id', $row->activity_id)->get()->getRow();
        $volunteer = $db->table('volunteers')->where('id', $row->volunteer_id)->get()->getRow();
        $statusInfo = $db->table('activities_status')->where('id', $row->status)->get()->getRow();
        $statuses = $db->table('activities_status')->get()->getResult();

        // 1. Selection
        $selection = "<input class='selecting' onchange='selectfunction(this.id)' type='checkbox' id='{$row->id}'>";
        
        // 2. Activity & Volunteer Info
        $activityName = "<b>" . esc($activity->name ?? 'Unknown') . "</b>";
        $volunteerName = "<b>" . esc($volunteer->name ?? 'Unknown') . "</b>";
        
        // 3. Status Badge
        $statusClass = $row->status == 0 ? 'status-pending' : ($row->status == 2 ? 'status-completed' : 'status-active');
        $statusBadge = "<span class='badge-status {$statusClass}'>" . esc($statusInfo->name ?? 'N/A') . "</span>";

        // 4. Certificates
        $certs = '<div class="certificate-options">' .
            '<label><input type="checkbox" id="enablePublic_' . $row->id . '" ' . ($row->public_certificate ? 'checked' : '') . ' onclick="handleCertificateChange(' . $row->id . ', \'public_certificate\', this.checked)"> عامة</label>' .
            '<label><input type="checkbox" id="enablePrivate_' . $row->id . '" ' . ($row->private_certificate ? 'checked' : '') . ' onclick="handleCertificateChange(' . $row->id . ', \'private_certificate\', this.checked)"> خاصة</label></div>';

        // 5. Action Dropdowns
        $certAction = "";
        if ($row->status == 2) {
            $certAction = "<select class='form-control-sm' id='certificateType_{$row->id}'></select>";
        }

        $statusAction = "<select class='form-control-sm' onchange='handleStatusChange({$row->id}, this.value)'><option value=''>تغيير الحالة</option>";
        foreach ($statuses as $st) {
            if ($st->id != $row->status) {
                $statusAction .= "<option value='{$st->id}'>" . esc($st->name) . "</option>";
            }
        }
        $statusAction .= "</select>";

        $dataset .= '["' . addslashes($selection) . '",'
            . '"' . $row->id . '",'
            . '"' . addslashes($activityName) . '",'
            . '"' . addslashes($volunteerName) . '",'
            . '"' . addslashes($statusBadge) . '",'
            . '"' . addslashes($certs) . '",'
            . '"' . addslashes($statusAction) . '",'
            . '"' . addslashes($certAction) . '"],';
    }
    $dataset = rtrim($dataset, ',') . ']';
endif; ?>

<div class="row">
    <div class="col-md-12">
        <div class="pagination-container">
            <?php if (isset($pager)): ?>
                <?= $pager->links() ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    const dataSet = <?= $dataset ?? '[]' ?>;
    // Initialize Mobile Filter Backdrop
    if (!$('.filter-backdrop').length) {
        $('<div class="filter-backdrop"></div>').appendTo('body');
    }

    const table = $('#example').DataTable({
        data: dataSet,
        pageLength: -1,
        dom: 't',
        language: {
            emptyTable: "لا توجد بيانات متاحة في الجدول"
        },
        columns: [
            { title: "<input id='select_all' onchange='select_all()' class='select_all' type='checkbox'></input>", orderable: false },
            { title: "م" },
            { title: "النشاط", className: "filter-col" },
            { title: "المتطوع", className: "filter-col" },
            { title: "الحالة", className: "filter-col" },
            { title: "الشهادات", orderable: false },
            { title: "إجراءات", orderable: false },
            { title: "إصدار", orderable: false }
        ],
        drawCallback: function(settings) {
            const api = this.api();
            const headers = api.columns().header().toArray();
            
            // Inject data-label for mobile card responsiveness
            api.rows({page:'current'}).every(function() {
                const row = this.node();
                $(row).find('td').each(function(i) {
                    let title = $(headers[i]).text().trim();
                    title = title.replace(/\s*$/, ''); // Clean title
                    if (title && i > 0) { // Skip checkbox column
                        this.setAttribute('data-label', title);
                    }
                });
            });

            // Re-initialize certificate dropdowns
            $('.certificate-options').parent().each(function() {
                const id = $(this).closest('tr').find('.selecting').attr('id');
                if (id) updateSelectOptions(id);
            });
        },
        initComplete: function () {
            const tableApi = this.api();
            tableApi.columns('.filter-col').every(function () {
                const column = this;
                const columnIdx = column.index();
                const header = $(column.header());
                
                const trigger = $('<i class="fa-solid fa-filter filter-trigger"></i>').appendTo(header);
                const dropdown = $('<div class="column-filter-dropdown" data-col="' + columnIdx + '">' +
                    '<input type="text" class="filter-search-input" placeholder="بحث...">' +
                    '<div class="filter-checkbox-list"></div>' +
                    '<div class="filter-actions">' +
                        '<button class="btn-filter-action btn-filter-clear">إعادة تعيين</button>' +
                        '<button class="btn-filter-action btn-filter-apply">تطبيق</button>' +
                    '</div>' +
                '</div>').appendTo('body');

                function populateCheckboxes() {
                    const list = dropdown.find('.filter-checkbox-list').empty();
                    const uniqueValues = [];
                    column.data().unique().sort().each(function (d) {
                        const text = d.toString().replace(/<[^>]*>?/gm, '').trim();
                        if (text && !uniqueValues.includes(text)) uniqueValues.push(text);
                    });

                    uniqueValues.forEach(val => {
                        list.append('<label class="filter-checkbox-item">' +
                            '<input type="checkbox" value="' + val + '">' +
                            '<span>' + val + '</span>' +
                        '</label>');
                    });
                }
                populateCheckboxes();

                trigger.on('click', function (e) {
                    e.stopPropagation();
                    $('.column-filter-dropdown').not(dropdown).hide();
                    const offset = $(this).offset();
                    dropdown.css({ 
                        top: offset.top + 25, 
                        left: Math.max(10, offset.left - dropdown.width() + 15) 
                    }).fadeToggle(150);
                    
                    if ($(window).width() < 768) {
                        $('.filter-backdrop').fadeIn(150);
                    }
                });

                dropdown.on('click', e => e.stopPropagation());

                dropdown.find('.filter-search-input').on('keyup', function () {
                    const val = this.value.toLowerCase().trim();
                    dropdown.find('.filter-checkbox-item').each(function () {
                        const itemText = $(this).text().toLowerCase();
                        $(this).toggle(itemText.includes(val));
                    });
                });

                dropdown.find('.btn-filter-apply').on('click', function () {
                    const selected = dropdown.find('input:checked').map(function () { 
                        return $.fn.dataTable.util.escapeRegex($(this).val()); 
                    }).get();

                    if (selected.length > 0) {
                        const regex = '^(' + selected.join('|') + ')$';
                        column.search(regex, true, false).draw();
                        trigger.addClass('active');
                    } else {
                        column.search('').draw();
                        trigger.removeClass('active');
                    }
                    dropdown.hide();
                    $('.filter-backdrop').fadeOut(150);
                });

                dropdown.find('.btn-filter-clear').on('click', function () {
                    dropdown.find('input').prop('checked', false);
                    dropdown.find('.filter-search-input').val('');
                    dropdown.find('.filter-checkbox-item').show();
                    column.search('').draw();
                    trigger.removeClass('active');
                    dropdown.hide();
                    $('.filter-backdrop').fadeOut(150);
                });
            });

            $(document).on('click', function() {
                $('.column-filter-dropdown').fadeOut(100);
                $('.filter-backdrop').fadeOut(100);
            });

                $(document).on('click', '.filter-backdrop', function() {
                    $('.column-filter-dropdown').fadeOut(150);
                    $(this).fadeOut(150);
                });
            }
        });

        // Integrated Search from Header
        document.getElementById('globalSearch')?.addEventListener('input', function() {
            table.search(this.value).draw();
        });
    });

    function select_all() {
    const checked = $('#select_all').is(':checked');
    $('.selecting').prop('checked', checked);
    $('.selecting').each(function() {
        let id = this.id;
        if (id) selectfunction(id);
    });
}

function selectfunction(id) {
    let selectedInput = document.getElementById('selected');
    if (!selectedInput) {
        selectedInput = document.createElement('input');
        selectedInput.type = 'hidden';
        selectedInput.id = 'selected';
        document.body.appendChild(selectedInput);
    }
    
    let values = selectedInput.value.split(',').filter(v => v !== "" && v !== "0").map(Number);
    const numId = Number(id);
    
    if (document.getElementById(id).checked) {
        if (!values.includes(numId)) values.push(numId);
    } else {
        values = values.filter(v => v !== numId);
    }
    selectedInput.value = values.join(',');
}

function handleCertificateChange(id, field, isChecked) {
    fetch('<?= base_url("Admin/updateCertificateStatus") ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id, field: field, value: isChecked ? 1 : 0 })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) updateSelectOptions(id);
        else alert('Error updating certificate');
    })
    .catch(err => console.error('Error:', err));
}

function handleStatusChange(id, status) {
    if (!status) return;
    const actionText = ['تعليق', 'الموافقة', 'إنجاز'][status] || 'تغيير';
    if (!confirm(`هل أنت متأكد من رغبتك في ${actionText} هذا النشاط؟`)) return;

    fetch('<?= base_url("Admin/updateStatus") ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id, status: Number(status) })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.showNotification('success', 'تم تحديث حالة الطلب بنجاح');
            // Update the UI cell visually without reload
            const statusLabels = { 0: 'معلق', 1: 'مقبول', 2: 'منجز' };
            const statusClasses = { 0: 'label-warning', 1: 'label-success', 2: 'label-primary' };
            const row = $(`tr:has(.selecting[id="${id}"])`);
            const statusCell = row.find('td').eq(4); // Assuming 5th column is status
            statusCell.html(`<span class="label ${statusClasses[status]}">${statusLabels[status]}</span>`);
        }
    })
    .catch(err => {
        console.error('Error:', err);
        window.showNotification('error', 'فشل تحديث الحالة');
    });
}

function updateSelectOptions(id) {
    const enablePublic = document.getElementById("enablePublic_" + id)?.checked;
    const enablePrivate = document.getElementById("enablePrivate_" + id)?.checked;
    
    const select = $(`#certificateType_${id}`);
    if (!select.length) return;

    select.empty().append($('<option>', { value: '0', text: 'اختر شهادة' }));
    if (enablePublic) select.append($('<option>', { value: 'public', text: 'شهادة عامة' }));
    if (enablePrivate) select.append($('<option>', { value: 'private', text: 'شهادة خاصة' }));

    select.off('change').on('change', function() {
        const val = $(this).val();
        if (val === 'public') window.location.href = "public_certificate?id=" + id;
        else if (val === 'private') window.location.href = "certificate?id=" + id;
    });
}
</script>


