<?php $volunteer_city = $db->table('volunteers')->where('id', $volunteer_id)->get()->getRow()->city_id;?>

<div class="my-activities-wrapper fade-in-up">
    <div class="counter-panel">
        <div class="white-box" style="padding: 25px;">
            <?php 
                $my_activities = $db->table('volunteer_activities')
                    ->select('volunteer_activities.*')
                    ->where('volunteer_id', $volunteer_id)
                    ->get()->getResult();

                if (empty($my_activities)) : ?>
                    <div class="empty-state-container">
                        <i class="fa-solid fa-person-running empty-state-icon"></i>
                        <h3 class="empty-state-title">لم تقم بالتسجيل في أي نشاط بعد</h3>
                        <p class="empty-state-desc">استعرض النشاطات المتاحة وساهم في بناء مجتمعك. ستظهر النشاطات التي قمت بالتسجيل بها هنا متابعةً لحالتها.</p>
                        <a href="<?= base_url('Volunteer/activities') ?>" class="btn-primary-premium" style="text-decoration: none; margin-top: 20px;">
                            <span>استعراض النشاطات المتاحة</span>
                        </a>
                    </div>
                <?php else : 
                    $dataset = '[';
                    foreach ($my_activities as $row) {
                        $activity = $db->table('activities')->where('id', $row->activity_id)->get()->getRow();
                        if (!$activity) continue;

                        $link1 = base_url('Volunteer/activity?id=' . $row->activity_id);            
                        
                        $transportation = $activity->transportation ? '<span class="status-icon success"><i class="fa-solid fa-check"></i></span>' : '<span class="status-icon muted"><i class="fa-solid fa-xmark"></i></span>';
                        $residency = $activity->residency ? '<span class="status-icon success"><i class="fa-solid fa-check"></i></span>' : '<span class="status-icon muted"><i class="fa-solid fa-xmark"></i></span>';
                        $expenses = $activity->expenses ? '<span class="status-icon success"><i class="fa-solid fa-check"></i></span>' : '<span class="status-icon muted"><i class="fa-solid fa-xmark"></i></span>';
                        $training = $activity->training ? '<span class="status-icon success"><i class="fa-solid fa-check"></i></span>' : '<span class="status-icon muted"><i class="fa-solid fa-xmark"></i></span>';
                        
                        $city = $db->table('cities')->where('id', $activity->city_id)->get()->getRow();
                        $city_name = $city ? $city->name : 'غير محدد';

                        $status_label = '';
                        if ($row->status == 0) $status_label = '<span class="status-badge warning">قيد المراجعة</span>';
                        elseif ($row->status == 1) $status_label = '<span class="status-badge success">تمت الموافقة</span>';
                        else $status_label = '<span class="status-badge primary">تم الانجاز</span>';

                        $dataset .= '["<input class=\'selecting\' onchange=\'selectfunction(this.id)\' type=\'checkbox\' id=' . $row->activity_id . '></input>",'
                                . '"<a class=\'table-action-link\' href=\'' . $link1 . '\'>' . $activity->name . '</a>",'
                                . '"' . $transportation . '",'
                                . '"' . $residency . '",'
                                . '"' . $expenses . '",'
                                . '"' . $training . '",'
                                . '"' . $activity->organisation . '",'
                                . '"' . $activity->date_from . '",'
                                . '"' . $activity->date_to . '",'
                                . '"' . $activity->hours . '",'
                                . '"' . $city_name . '",'
                                . '"' . $status_label . '",'
                                . '],';
                    }
                    $dataset = rtrim($dataset, ',') . ']';
            ?>
                <div class="table-responsive">
                    <table class="table premium-table datatable" id="myActivitiesTable">
                        <!-- DataTable logic builds this -->
                    </table>
                </div>

                <script>
                    $(document).ready(function () {
                        var dataSet = <?php echo $dataset; ?>;
                        $('#myActivitiesTable').DataTable({
                            data: dataSet,                
                            "pageLength": 10,
                            "language": {
                                "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/ar.json"
                            },
                            columns: [
                                { title: "<input id='select_all' onchange='select_all()' class='select_all' type='checkbox'></input>", orderable: false },                    
                                { title: "النشاط" },
                                { title: "المواصلات" },
                                { title: "الإقامة" },
                                { title: "الإعاشة" },
                                { title: "التدريب" },
                                { title: "المنظمة" },
                                { title: "من" },
                                { title: "إلى" },
                                { title: "الساعات" },
                                { title: "المدينة" },
                                { title: "الحالة" }
                            ]
                        });
                    });

                    function select_all() {
                        var allcheckboxes = document.querySelectorAll('input[class="selecting"]');            
                        var checkedall_id = document.getElementById('select_all').checked;
                        if(checkedall_id == true) {
                            allcheckboxes.forEach(checkbox => {checkbox.checked = true;selectfunction(checkbox.id)});
                        } else {                                                
                            allcheckboxes.forEach(checkbox => {checkbox.checked = false;}); 
                        }
                    }

                    function selectfunction(id) {
                        // Standard selection logic if needed for bulk actions
                    }
                </script>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .my-activities-wrapper {
        margin-top: 1rem;
    }
    
    .status-icon {
        font-size: 1.1rem;
    }
    .status-icon.success { color: #10b981; }
    .status-icon.muted { color: #e2e8f0; opacity: 0.5; }
    
    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 700;
    }
    .status-badge.warning { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
    .status-badge.success { background: rgba(16, 185, 129, 0.1); color: #10b981; }
    .status-badge.primary { background: rgba(48, 67, 0, 0.1); color: var(--primary); }

    .table-action-link {
        color: var(--primary);
        font-weight: 700;
        text-decoration: none;
        transition: color 0.15s ease;
    }
    .table-action-link:hover { color: var(--primary-light); text-decoration: underline; }

    .premium-table thead th {
        background: rgba(48, 67, 0, 0.02);
        color: var(--text-main);
        font-weight: 700;
        border-bottom: 2px solid var(--border-color);
        padding: 15px !important;
    }
    
    .premium-table tbody td {
        padding: 15px !important;
        vertical-align: middle;
        border-bottom: 1px solid var(--border-color);
    }
</style>



