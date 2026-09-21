<?php
$start_date = $this->input->get('start_date') ?? date('Y-m-01');
$until_date = $this->input->get('until_date') ?? date('Y-m-d');
?>
<!-- Tippy.js for tooltips -->
<script src="https://unpkg.com/@popperjs/core@2"></script>
<script src="https://unpkg.com/tippy.js@6"></script>

<style>
    .recruitment-custom-scrollbar {
        width: 100%;
        height: 12px;
        background: rgba(248, 249, 250, 0.8);
        border: 1px solid rgba(222, 226, 230, 0.6);
        border-radius: 6px;
        margin-top: 8px;
        position: relative;
        cursor: pointer;
        display: none;
    }

    .recruitment-custom-scrollbar .scrollbar-track {
        width: 100%;
        height: 100%;
        position: relative;
    }

    .recruitment-custom-scrollbar .scrollbar-thumb {
        height: 100%;
        background: linear-gradient(180deg, rgba(173, 181, 189, 0.7) 0%, rgba(134, 142, 150, 0.7) 100%);
        border-radius: 6px;
        cursor: grab;
        position: absolute;
        left: 0;
        transition: background 0.2s;
        min-width: 50px;
    }

    .recruitment-custom-scrollbar .scrollbar-thumb:hover {
        background: linear-gradient(180deg, rgba(134, 142, 150, 0.8) 0%, rgba(108, 117, 125, 0.8) 100%);
    }

    .recruitment-custom-scrollbar .scrollbar-thumb:active {
        cursor: grabbing;
        background: linear-gradient(180deg, rgba(108, 117, 125, 0.9) 0%, rgba(73, 80, 87, 0.9) 100%);
    }

    .recruitment-custom-scrollbar .scrollbar-thumb::before {
        content: '\22ee\22ee\22ee';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        color: rgba(255, 255, 255, 0.5);
        font-size: 8px;
        letter-spacing: 2px;
    }

    /* Table Responsive for Custom Scrollbar */
    .table-responsive[data-custom-scrollbar] {
        overflow-x: auto;
        white-space: nowrap;
    }

    .table-responsive[data-custom-scrollbar] table {
        min-width: max-content;
    }
    
    /* Sticky Left Columns */
    .table thead th:nth-child(1),
    .table tbody td:nth-child(1) {
        position: sticky;
        left: 0;
        background: #fafafa;
        z-index: 6;
        box-shadow: 2px 0 4px rgba(0,0,0,0.05);
    }
    
    .table thead th:nth-child(2),
    .table tbody td:nth-child(2) {
        position: sticky;
        left: 50px;
        background: #fafafa;
        z-index: 6;
        box-shadow: 2px 0 4px rgba(0,0,0,0.05);
    }
    
    .table thead th:nth-child(3),
    .table tbody td:nth-child(3) {
        position: sticky;
        left: 170px;
        background: #fafafa;
        z-index: 6;
        box-shadow: 2px 0 4px rgba(0,0,0,0.05);
    }
    
    .table tbody td:nth-child(1),
    .table tbody td:nth-child(2),
    .table tbody td:nth-child(3) {
        background: #fff;
    }
    
    .table thead th:nth-child(1),
    .table thead th:nth-child(2),
    .table thead th:nth-child(3) {
        z-index: 11;
    }
    
    /* Action Column Sticky Right */
    .table thead th:last-child,
    .table tbody td:last-child {
        position: sticky;
        right: 0;
        background: #fff;
        z-index: 5;
        box-shadow: -2px 0 8px rgba(0,0,0,0.05);
    }
    
    .table thead th:last-child {
        z-index: 10;
        background: #fafafa;
    }
    
    /* Action Buttons Hover Effects */
    .btn-reject:hover {
        background: #ff4d4f !important;
        color: white !important;
        border-color: #ff4d4f !important;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(255, 77, 79, 0.3);
    }
    
    .btn-notes:hover {
        background: #1890ff !important;
        color: white !important;
        border-color: #1890ff !important;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(24, 144, 255, 0.3);
    }
    
    .btn-reject, .btn-notes {
        transition: all 0.2s ease;
    }
    
    /* Tippy Theme */
    .tippy-box[data-theme~='recruitment-notes'] {
        background-color: #ffffff;
        color: #333333;
        font-size: 13px;
        border-radius: 6px;
        padding: 8px 12px;
        max-width: 350px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        border: 1px solid #e0e0e0;
    }
    
    .tippy-box[data-theme~='recruitment-notes'][data-placement^='top'] > .tippy-arrow::before {
        border-top-color: #ffffff;
    }
    
    .tippy-box[data-theme~='recruitment-notes'][data-placement^='bottom'] > .tippy-arrow::before {
        border-bottom-color: #ffffff;
    }
    
    .tippy-box[data-theme~='recruitment-notes'][data-placement^='left'] > .tippy-arrow::before {
        border-left-color: #ffffff;
    }
    
    .tippy-box[data-theme~='recruitment-notes'][data-placement^='right'] > .tippy-arrow::before {
        border-right-color: #ffffff;
    }
    
    /* Tab styling enhancements */
    .recruitment-tabs .nav-link {
        transition: all 0.3s ease;
    }
    
    .recruitment-tabs .nav-link:hover:not(.active) {
        background: #f5f5f5;
        transform: translateY(-2px);
    }

    /* Select2 Styling */
    .select2-container .select2-selection--single {
        box-sizing: border-box;
        cursor: pointer;
        display: block;
        height: 32px;
        user-select: none;
        -webkit-user-select: none;
        border: 1px solid #d9d9d9;
        border-radius: 2px;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: rgba(0, 0, 0, 0.85);
        line-height: 32px;
        padding-left: 11px;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 32px;
        position: absolute;
        top: 0px;
        right: 1px;
        width: 20px;
    }

    .select2 {
        height: 32px !important;
        min-width: 100% !important;
        margin-bottom: 8px;
    }

    /* Ant Design-like Table Styling */
    .table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        border: 1px solid #f0f0f0;
        border-radius: 2px;
    }

    .table thead th {
        background-color: #fafafa;
        color: rgba(0, 0, 0, 0.85);
        font-weight: 500;
        text-align: left;
        padding: 12px 8px;
        font-size: 14px;
        border-bottom: 1px solid #f0f0f0;
        transition: background 0.3s ease;
    }

    .table tbody td {
        padding: 12px 8px !important;
        font-size: 14px;
        color: rgba(0, 0, 0, 0.65);
        border-bottom: 1px solid #f0f0f0;
        transition: background 0.3s ease;
    }

    .table-hover tbody tr:hover {
        background-color: #fafafa;
    }

    /* Flatpickr mini calendar styling */
    #leave-mini-calendar .flatpickr-calendar.inline {
        box-shadow: none;
        border: 1px solid #e9ecef;
        border-radius: 10px;
        width: 100%;
    }
    #leave-mini-calendar .flatpickr-months {
        border-bottom: 1px solid #eee;
        padding: 6px 0;
    }
    #leave-mini-calendar .flatpickr-weekdays {
        border-bottom: 1px solid #f0f0f0;
        background: #fafafa;
    }
    #leave-mini-calendar .flatpickr-day {
        border-radius: 6px;
        position: relative;
    }
    #leave-mini-calendar .flatpickr-day.today {
        border-color: #1890ff;
    }
    #leave-mini-calendar .flatpickr-day.selected,
    #leave-mini-calendar .flatpickr-day.startRange,
    #leave-mini-calendar .flatpickr-day.endRange,
    #leave-mini-calendar .flatpickr-day.inRange {
        background: #1890ff;
        color: #fff;
        border-color: #1890ff;
    }
    .mini-calendar-leave-badge {
        position: absolute;
        top: 2px;
        right: 2px;
        background-color: #ff69b4;
        color: white;
        font-size: 8px;
        font-weight: 700;
        padding: 1px 4px;
        border-radius: 8px;
        line-height: 1.2;
        z-index: 5;
    }

    /* Card Styling - Ant Design-like */
    .card {
        border-radius: 2px;
        border: 1px solid #f0f0f0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.09);
        margin-bottom: 16px;
    }

    .history-card {
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .history-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
    }

    .card-header {
        background-color: #fff;
        border-bottom: 1px solid #f0f0f0;
        padding: 16px;
        height: 56px;
    }

    .card-body {
        padding: 16px;
    }

    /* Button Styling - Ant Design-like */
    .btn {
        border-radius: 2px;
        padding: 4px 15px;
        font-size: 14px;
        height: 32px;
        line-height: 1.5;
        transition: all 0.3s cubic-bezier(0.645, 0.045, 0.355, 1);
    }

    .btn-primary {
        background-color: #1890ff;
        border-color: #1890ff;
    }

    .btn-primary:hover {
        background-color: #40a9ff;
        border-color: #40a9ff;
    }

    .btn-outline-secondary {
        color: rgba(0, 0, 0, 0.65);
        border-color: #d9d9d9;
        background: #fff;
    }

    .btn-outline-secondary:hover {
        color: #40a9ff;
        border-color: #40a9ff;
    }

    /* Badge Styling - Ant Design-like */
    .badge {
        font-size: 12px;
        height: 22px;
        padding: 0 8px;
        line-height: 22px;
        border-radius: 2px;
        font-weight: normal;
    }

    /* Pagination Styling - Ant Design-like */
    .pagination {
        margin-top: 16px;
        justify-content: flex-end;
    }

    .page-item {
        margin-right: 8px;
    }

    .page-item:last-child {
        margin-right: 0;
    }

    .page-item.active .page-link {
        background-color: #1890ff;
        border-color: #1890ff;
    }

    .page-link {
        min-width: 32px;
        height: 32px;
        line-height: 30px;
        text-align: center;
        color: rgba(0, 0, 0, 0.65);
        border-radius: 2px;
        padding: 0;
        margin: 0;
        border: 1px solid #d9d9d9;
    }

    .page-link:hover {
        color: #40a9ff;
        border-color: #40a9ff;
    }

    /* Search Form Styling - Ant Design-like */
    .search-form {
        margin-bottom: 16px;
    }

    .search-form .input-group {
        border-radius: 2px;
        display: flex;
        justify-content: space-between;
    }

    .form-control {
        height: 32px;
        padding: 4px 11px;
        font-size: 14px;
        border: 1px solid #d9d9d9;
        border-radius: 2px;
        transition: all 0.3s;
    }

    .form-control:hover {
        border-color: #40a9ff;
    }

    .form-control:focus {
        border-color: #40a9ff;
        box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.2);
    }

    .form-select {
        height: 32px;
        padding: 4px 11px;
        font-size: 14px;
        border: 1px solid #d9d9d9;
        border-radius: 2px;
        transition: all 0.3s;
    }

    .form-select:hover {
        border-color: #40a9ff;
    }

    .form-select:focus {
        border-color: #40a9ff;
        box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.2);
    }

    /* Alert Styling - Ant Design-like */
    .alert {
        padding: 8px 15px;
        border-radius: 2px;
        font-size: 14px;
    }

    .alert-info {
        background-color: #e6f7ff;
        border-color: #91d5ff;
        color: rgba(0, 0, 0, 0.65);
    }

    /* Status Badges */
    .badge-primary {
        background-color: #1890ff;
        color: white;
    }
    .badge-success {
        background-color: #52c41a;
        color: white;
    }
    .badge-warning {
        background-color: #faad14;
        color: white;
    }
    .badge-danger {
        background-color: #f5222d;
        color: white;
    }
    .badge-default {
        background-color: #d9d9d9;
        color: rgba(0, 0, 0, 0.65);
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .table-responsive {
            border-radius: 2px;
            overflow: hidden;
        }
    }
</style>
<style>
    :root {
        --primary-color: #3b82f6;
        --secondary-color: #6b7280;
        --success-color: #10b981;
        --warning-color: #f59e0b;
        --danger-color: #ef4444;
        --light-bg: #f8fafc;
        --border-color: #e5e7eb;
        --card-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
    }

    .card {
        border: none;
        border-radius: 12px;
        box-shadow: var(--card-shadow);
        margin-bottom: 2rem;
        overflow: hidden;
        background: white;
    }

    .card-header {
        background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
        border-bottom: 1px solid var(--border-color);
        padding: 1.5rem 2rem;
    }

    .card-header h5 {
        font-size: 1.125rem;
        font-weight: 600;
        color: #1f2937;
        margin: 0;
        display: flex;
        align-items: center;
    }

    .card-header h5 i {
        color: var(--primary-color);
        margin-right: 0.75rem;
    }

    .card-body {
        padding: 2rem;
    }

    .info-group {
        margin-bottom: 2rem;
    }

    .info-group:last-child {
        margin-bottom: 0;
    }

    .info-label {
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--secondary-color);
        margin-bottom: 0.5rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .info-value {
        font-size: 1rem;
        color: #1f2937;
        margin: 0 0 1rem 0;
        line-height: 1.5;
    }

    .contact-info {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .contact-item {
        display: flex;
        align-items: center;
        font-size: 0.95rem;
        color: #374151;
    }

    .contact-item i {
        color: var(--primary-color);
        margin-right: 0.75rem;
        width: 16px;
    }

    .status-badges {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
        align-items: center;
    }

    .badge {
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
        font-weight: 500;
        border-radius: 20px;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .bg-info {
        background-color: #dbeafe;
        color: #1e40af;
        border: 1px solid #93c5fd;
    }

    .bg-warning {
        background-color: #fef3c7;
        color: #92400e;
        border: 1px solid #fcd34d;
    }

    .bg-danger {
        background-color: #fee2e2;
        color: #b91c1c;
        border: 1px solid #fca5a5;
    }

    .bg-light {
        background-color: #f3f4f6;
        color: #4b5563;
        border: 1px solid var(--border-color);
    }

    .demographics {
        color: #4b5563;
        line-height: 1.6;
    }

    .work-preference {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
        color: #4b5563;
    }

    .salary {
        font-weight: 600;
        color: var(--success-color);
        font-size: 1.125rem;
    }

    .keyword-tags {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .keyword-tag {
        background: #f0f9ff;
        color: #0369a1;
        padding: 0.375rem 0.75rem;
        border-radius: 16px;
        font-size: 0.875rem;
        font-weight: 500;
        border: 1px solid #bae6fd;
    }

    .hr-notes-section {
        background: linear-gradient(135deg, #fafbfc 0%, #f3f4f6 100%);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 1.5rem;
    }

    .timestamp {
        font-size: 0.875rem;
        color: var(--secondary-color);
        font-weight: 500;
    }

    .section-divider {
        height: 1px;
        background: linear-gradient(90deg, transparent, var(--border-color), transparent);
        margin: 1.5rem 0;
    }

    .empty-state {
        color: #9ca3af;
        font-style: italic;
    }

    @media (max-width: 768px) {
        .card-header {
            padding: 1rem 1.5rem;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .d-flex.justify-content-between {
            flex-direction: column;
            gap: 1rem;
        }
        
        .status-badges {
            justify-content: flex-start;
        }
    }

    .badge-testcase_1 {
        background-color: #d7bdf2;
        color: #4b0082;
    }
    .badge-interview_hr {
        background-color: #ffe7a0;
        color: #7a5900;
    }
    .badge-interview_user {
        background-color: #cde8ff;
        color: #0d6efd;
    }
    .badge-selected {
        background-color: #2e7d32;
        color: white;
    }
    .badge-rejected {
        background-color: #b71c1c;
        color: white;
    }
    .badge-pertimbangan {
        background-color: #f9c998;
        color: #8b4513;
    }
    .badge-default {
        background-color: #e0e0e0;
        color: #666;
    }

</style>
<style>
    /* Leave table: garis kolom + thead biru muda */
    #leave-table th,
    #leave-table td {
        border-right: 1px solid #e8e8e8 !important;
        border-bottom: 1px solid #e8e8e8 !important;
    }
    #leave-table th:last-child,
    #leave-table td:last-child {
        border-right: none !important;
    }
    #leave-table thead th {
        background-color: #e6f7ff !important;
        color: #0050b3 !important;
    }
    .leave-main-tabs {
        border-bottom: 1px solid #e5e7eb;
        margin-bottom: 16px;
    }
    .leave-main-tabs .nav-link {
        border: 0;
        color: #6b7280;
        font-weight: 600;
        padding: 10px 16px;
    }
    .leave-main-tabs .nav-link.active {
        color: #0d6efd;
        border-bottom: 3px solid #0d6efd;
        background: transparent;
    }

    #work-calendar-container {
        display: flex;
        flex-direction: column;
        height: 100%;
        width: 100%;
        flex: 2;
        position: relative;
    }

    #work-calendar {
        height: 600px !important;
        overflow: hidden;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        border-radius: 10px;
        border: 2px solid #e9ecef;
        background: #ffffff;
    }

    #work-calendar table {
        font-size: 0 !important;
    }

    #work-calendar table tr th,
    #work-calendar table tr td {
        padding: 0 !important;
        border-radius: 0 !important;
        background: transparent !important;
    }

    #work-calendar .fc-toolbar {
        margin-bottom: 5px !important;
        display: flex;
        justify-content: flex-end;
        align-items: center;
        padding: 10px 0;
    }

    #work-calendar .fc-toolbar-title {
        font-size: 1.1em !important;
        margin: 0 15px !important;
        order: 2;
    }

    #work-calendar .fc-prev-button {
        order: 1;
        background-color: transparent !important;
        color: #333 !important;
        border: none !important;
        box-shadow: none !important;
    }

    #work-calendar .fc-next-button {
        order: 3;
        background-color: transparent !important;
        color: #333 !important;
        border: none !important;
        box-shadow: none !important;
    }

    #work-calendar .fc-button {
        padding: 5px 10px !important;
        font-size: 0.9em !important;
    }

    #work-calendar .fc-theme-standard {
        background-color: white !important;
    }

    #work-calendar .fc-theme-standard .fc-scrollgrid,
    #work-calendar .fc-scrollgrid {
        border: 1px solid #ddd !important;
        border-collapse: separate !important;
        border-spacing: 0 !important;
    }

    #work-calendar .fc-theme-standard .fc-col-header {
        height: 24px !important;
    }

    #work-calendar .fc-col-header-cell {
        padding: 0 !important;
        margin: 0 !important;
        height: 10px !important;
        vertical-align: top !important;
        border: 1px solid #ddd !important;
        background-color: #fae0e9ff !important;
    }

    #work-calendar .fc-col-header-cell-cushion {
        all: unset;
        display: block;
        text-align: center;
        font-size: 12px;
        font-weight: normal;
        color: #000;
    }

    #work-calendar .fc-daygrid-day {
        height: 80px !important;
        padding: 1px !important;
        border: 1px solid #ddd !important;
        background-color: white !important;
        position: relative;
        box-sizing: border-box;
        cursor: pointer;
    }

    #work-calendar .fc-daygrid-day-frame {
        height: 100% !important;
        min-height: unset !important;
        padding: 0 !important;
        background: transparent !important;
    }

    #work-calendar .fc-daygrid-day-top {
        display: flex;
        flex-direction: row-reverse;
        justify-content: space-between;
        align-items: flex-start;
        padding: 2px 4px !important;
        margin-left: auto !important;
    }

    #work-calendar .fc-daygrid-day-number {
        font-size: 12px !important;
        font-weight: 500 !important;
        padding: 0 2px !important;
        margin: 0 !important;
        color: #000 !important;
        text-decoration: none !important;
    }

    #work-calendar .fc-daygrid-body-balanced .fc-daygrid-day-events {
        min-height: 0 !important;
    }

    #work-calendar .fc-daygrid-day:hover {
        background-color: #fce2ebff !important;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    #work-calendar .fc-daygrid-day.selected-date {
        background-color: #fae0e9ff !important;
        border: 2px solid #ffc8daff !important;
    }

    #work-calendar .fc-daygrid-day.selected-date .fc-daygrid-day-frame {
        background: transparent !important;
        outline: none !important;
    }

    .work-calendar-legend {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        align-items: center;
    }
    .work-calendar-legend .work-calendar-pill {
        min-width: 68px;
        justify-content: center;
        font-size: 12px;
        padding: 5px 9px;
        border-radius: 999px;
    }
    #work-calendar .fc-daygrid-day-events {
        display: flex;
        gap: 3px;
        flex-wrap: nowrap;
        align-items: center;
        min-height: 18px !important;
        padding: 0 3px 2px !important;
        margin-top: 0 !important;
    }
    #work-calendar .fc-daygrid-event-harness {
        flex: 0 0 auto;
        margin: 0 !important;
    }
    #work-calendar .fc-daygrid-event {
        width: 22px;
        min-width: 22px;
        min-height: 16px;
        margin: 0 !important;
        padding: 0 4px !important;
        border-radius: 999px !important;
        border-width: 1px !important;
        box-shadow: none !important;
    }
    #work-calendar .fc-event-main {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 14px;
        line-height: 1;
    }
    #work-calendar .work-event-count {
        display: block;
        width: auto;
        font-size: 10px;
        font-weight: 700;
        line-height: 14px;
        text-align: center;
        color: inherit;
    }
    .work-calendar-pill {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 4px;
        border-radius: 4px;
        padding: 2px 5px;
        font-size: 10px;
        font-weight: 700;
        line-height: 1.2;
        white-space: nowrap;
    }
    .work-calendar-pill.wfo {
        background: #dcfce7;
        color: #166534;
        border: 1px solid #86efac;
    }
    .work-calendar-pill.izin {
        background: #dbeafe;
        color: #1e40af;
        border: 1px solid #93c5fd;
    }
    .work-calendar-pill.cuti {
        background: #ffe4e6;
        color: #9f1239;
        border: 1px solid #fda4af;
    }
    .work-calendar-detail {
        height: 600px;
        overflow-y: auto;
        border: 1px solid #ddd;
        border-radius: 10px;
        padding: 20px;
        background: #ffffff;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    .work-calendar-section {
        border-radius: 8px;
        border: 1px solid #e5e7eb;
        padding: 10px 12px;
        margin-bottom: 12px;
    }
    .work-calendar-section.wfo {
        border-color: #86efac;
        background: #f0fdf4;
    }
    .work-calendar-section.izin {
        border-color: #93c5fd;
        background: #eff6ff;
    }
    .work-calendar-section.cuti {
        border-color: #fda4af;
        background: #fff1f2;
    }
    .work-calendar-person {
        font-size: 13px;
        color: #374151;
        padding: 4px 0;
        border-bottom: 1px dashed rgba(0,0,0,0.08);
    }
    .work-calendar-person:last-child {
        border-bottom: 0;
    }
    @media (max-width: 768px) {
        #work-calendar {
            height: 500px !important;
        }
        #work-calendar .fc-toolbar {
            flex-direction: column;
            align-items: flex-start;
        }
        #work-calendar .fc-toolbar-title {
            font-size: 14px !important;
        }
        #work-calendar .fc-daygrid-day {
            height: 64px !important;
        }
        #work-calendar .fc-button {
            font-size: 10px;
            padding: 2px 4px !important;
        }
        #work-calendar .fc-daygrid-event {
            width: 20px;
            min-width: 20px;
            min-height: 14px;
            padding: 0 3px !important;
        }
        #work-calendar .work-event-count {
            font-size: 9px;
            line-height: 12px;
        }
        .work-calendar-detail {
            height: auto;
            max-height: 420px;
        }
    }
</style>
<div class="container-fluid py-3">
    <div class="row g-3">
        <div class="col-12">
            <ul class="nav nav-tabs leave-main-tabs" id="leaveMainTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="leave-management-tab" data-bs-toggle="tab" data-bs-target="#leave-management-pane" type="button" role="tab" aria-controls="leave-management-pane" aria-selected="true">
                        Leave Management
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="leave-calendar-tab" data-bs-toggle="tab" data-bs-target="#leave-calendar-pane" type="button" role="tab" aria-controls="leave-calendar-pane" aria-selected="false">
                        Kalender
                    </button>
                </li>
            </ul>
            <div class="tab-content" id="leaveMainTabContent">
                <div class="tab-pane fade show active" id="leave-management-pane" role="tabpanel" aria-labelledby="leave-management-tab">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-clipboard-check me-2"></i>Leave Management</h5>
                    <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#leaveTypeModal">
                            <i class="bi bi-tags me-1"></i> Tipe Cuti
                        </button>
                        <span class="text-muted" style="font-size:12px;">Akses HR/Leader/CEO</span>
                    </div>
                </div>
                <div class="card-body">
                    <form method="GET" class="mb-3">
                        <div class="row g-2 justify-content-end">
                            <div class="col-md-3">
                                <input type="text"
                                       class="form-control form-control-sm"
                                       name="name"
                                       value="<?= htmlspecialchars($name ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                       placeholder="Cari nama karyawan">
                            </div>
                            <div class="col-md-3">
                                <select name="leave_type_id" class="form-select form-select-sm">
                                    <option value="">Semua tipe pengajuan</option>
                                    <?php foreach (($leave_types ?? []) as $type): ?>
                                        <?php
                                            $type_id = (string) ($type['id'] ?? '');
                                            $selected_type = ((string) ($leave_type_id ?? '') === $type_id) ? 'selected' : '';
                                            $type_label = $type['display_name'] ?? ($type['name'] ?? '-');
                                        ?>
                                        <option value="<?= htmlspecialchars($type_id, ENT_QUOTES, 'UTF-8') ?>" <?= $selected_type ?>>
                                            <?= htmlspecialchars($type_label, ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control" id="tanggal" placeholder="Pilih rentang tanggal...">
                                    <button class="btn btn-primary" type="submit">Cari</button>
                                </div>

                                <input type="hidden" name="start_date" id="start_date" value="<?= $start_date ?? '' ?>">
                                <input type="hidden" name="until_date" id="end_date" value="<?= $until_date ?? $end_date ?? '' ?>">
                            </div>
                        </div>

                    </form>
                    <script>
                        get_filter();
                        function get_filter() {
                            $.ajax({
                                dataType: "json",
                                url: '<?= base_url() ?>/ajax/get-filter',
                                data: {
                                    start_date: "<?= $start_date ?? '' ?>",
                                    until_date: "<?= $until_date ?? $end_date ?? '' ?>",
                                },
                                success: function(response) {
                                    $("#tanggal").after(response.html);
                                },
                                error: function(xhr, status, error) {
                                    console.error("Error loading filter:", error);
                                }
                            });
                        }
                    </script>

                    <div class="table-responsive">
                        <table id="leave-table" class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Karyawan</th>
                                    <th>Tipe Pengajuan</th>
                                    <th>Tanggal</th>
                                    <th>Durasi</th>
                                    <th>Alasan</th>
                                    <th>Lampiran</th>
                                    <th>Approval Leader</th>
                                    <th>Approval HR</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($requests)): ?>
                                    <?php foreach ($requests as $index => $row): ?>
                                        <?php
                                            $approval = $approvals_map[$row['id']] ?? ['leader' => 'pending', 'hr' => 'pending'];
                                            $attachment_url = '';
                                            if (!empty($row['attachment_path'])) {
                                                $is_absolute_attachment = (strpos($row['attachment_path'], 'http://') === 0 || strpos($row['attachment_path'], 'https://') === 0);
                                                if ($is_absolute_attachment) {
                                                    $attachment_url = $row['attachment_path'];
                                                } else {
                                                    $attachment_relative = ltrim($row['attachment_path'], '/');
                                                    if (file_exists(FCPATH . $attachment_relative)) {
                                                        $attachment_url = base_url($attachment_relative);
                                                    }
                                                }
                                            }
                                        ?>
                                        <tr class="leave-row"
                                            data-request-id="<?= $row['id'] ?>"
                                            data-status="<?= $row['status'] ?>">
                                            <td><?= $index + 1 ?></td>
                                            <td><?= $row['user_name'] ?></td>
                                            <td><?= htmlspecialchars($row['leave_type_name']) ?></td>
                                            <td><?= date('d M Y', strtotime($row['start_date'])) ?> - <?= date('d M Y', strtotime($row['end_date'])) ?></td>
                                            <td><?= intval($row['total_days']) ?> hari</td>
                                            <td style="white-space: normal; min-width: 200px; max-width: 320px;"><?= htmlspecialchars($row['reason'] ?? '-') ?: '-' ?></td>
                                            <td>
                                                <?php if (!empty($attachment_url)): ?>
                                                    <a href="<?= $attachment_url ?>" target="_blank" rel="noopener">Lihat Lampiran</a>
                                                <?php endif; ?>
                                                <?php if (!empty($row['attachment2_path'])): ?>
                                                    <br><a href="<?= base_url($row['attachment2_path']) ?>" target="_blank" rel="noopener">Struk obat</a>
                                                <?php endif; ?>
                                                <?php if (empty($attachment_url) && empty($row['attachment2_path'])): ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <select class="form-select form-select-sm leave-approval-select"
                                                        data-request-id="<?= $row['id'] ?>"
                                                        data-role="leader">
                                                    <option value="pending" <?= $approval['leader'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                                    <option value="approved" <?= $approval['leader'] == 'approved' ? 'selected' : '' ?>>Approved</option>
                                                    <option value="rejected" <?= $approval['leader'] == 'rejected' ? 'selected' : '' ?>>Rejected</option>
                                                </select>
                                            </td>
                                            <td>
                                                <select class="form-select form-select-sm leave-approval-select"
                                                        data-request-id="<?= $row['id'] ?>"
                                                        data-role="hr">
                                                    <option value="pending" <?= $approval['hr'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                                    <option value="approved" <?= $approval['hr'] == 'approved' ? 'selected' : '' ?>>Approved</option>
                                                    <option value="rejected" <?= $approval['hr'] == 'rejected' ? 'selected' : '' ?>>Rejected</option>
                                                </select>
                                            </td>
                                            <td>
                                                <?php
                                                    $status_map = [
                                                        'pending_leader' => 'Menunggu Leader',
                                                        'pending_hr' => 'Menunggu HR',
                                                        'approved' => 'Disetujui',
                                                        'rejected' => 'Ditolak'
                                                    ];
                                                    $status_label = $status_map[$row['status']] ?? $row['status'];
                                                ?>
                                                <span class="badge bg-secondary leave-status-badge" data-request-id="<?= $row['id'] ?>"><?= $status_label ?></span>
                                            </td>
                                            <td>
                                                <form action="<?= base_url() ?>leave/hapus_admin" method="POST"
                                                      onsubmit="return confirm('Hapus pengajuan ini? Data yang dihapus tidak bisa dikembalikan.')">
                                                    <input type="hidden" name="request_id" value="<?= intval($row['id']) ?>">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm">Hapus</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="11" class="text-center text-muted">Belum ada data pengajuan.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?= $pagination ?>
                </div>
            </div>
                </div>
                <div class="tab-pane fade" id="leave-calendar-pane" role="tabpanel" aria-labelledby="leave-calendar-tab">
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-calendar3 me-2"></i>Kalender Izin/Cuti</h5>
                        </div>
                        <div class="card-body">
                            <div class="row bg-white" style="height: auto; border-radius: 10px; margin-left: 1px; margin-right: 1px">
                                <div class="col-lg-9" id="work-calendar-container">
                                    <div id="work-calendar" class="p-3"></div>
                                </div>
                                <div class="col-lg-3 bg-white">
                                    <div id="work-calendar-detail" class="work-calendar-detail">
                                        <div style="text-align: center;">
                                            <img src="<?= base_url() ?>/assets/img/icon/load.gif" alt="Not Found" style="margin-top: 50px; max-width: 100%;">
                                            <p style="font-weight: 600; margin-top: 10px; font-size: 22px;">Pilih tanggal terlebih dahulu</p>
                                            <p style="color: gray; margin-top: -8px; font-size: 14px;">Klik tanggal di kalender untuk melihat daftar izin dan cuti.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="leaveTypeModal" tabindex="-1" aria-labelledby="leaveTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="leaveTypeModalLabel">
                    <i class="bi bi-tags me-2"></i>Kelola Tipe Cuti
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url() ?>leave/save_leave_type" method="post" id="leaveTypeForm">
                    <div class="mb-3">
                        <input type="text" class="form-control" id="leave_type_name" name="name" placeholder="Tambah Tipe Cuti Baru" required>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" value="1" id="leave_type_requires_attachment" name="requires_attachment">
                        <label class="form-check-label" for="leave_type_requires_attachment">Wajib lampiran</label>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" value="1" id="leave_type_deducts_leave" name="deducts_leave">
                        <label class="form-check-label" for="leave_type_deducts_leave">Mengurangi sisa cuti</label>
                    </div>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </form>

                <div class="mt-4">
                    <h6>List Tipe Cuti</h6>
                    <div class="d-flex flex-wrap gap-2 mt-3" id="leave-types-list">
                        <!-- Leave types will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.7/index.global.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        function getStatusLabel(status) {
            var statusMap = {
                pending_leader: 'Menunggu Leader',
                pending_hr: 'Menunggu HR',
                approved: 'Disetujui',
                rejected: 'Ditolak'
            };
            return statusMap[status] || status || '-';
        }

        function getStatusBadgeClass(status) {
            if (status === 'approved') return 'bg-success';
            if (status === 'rejected') return 'bg-danger';
            if (status === 'pending_hr' || status === 'pending_leader') return 'bg-warning';
            return 'bg-secondary';
        }

        document.querySelectorAll('.leave-approval-select').forEach(function(select) {
            select.addEventListener('change', function() {
                var self = this;
                var previous = self.dataset.prev || self.value;
                var requestId = this.dataset.requestId;
                var role = this.dataset.role;
                var status = this.value;
                self.disabled = true;
                fetch('<?= base_url() ?>leave/update_approval_inline', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'request_id=' + encodeURIComponent(requestId) + '&role=' + encodeURIComponent(role) + '&status=' + encodeURIComponent(status)
                }).then(function(res) {
                    if (!res.ok) throw new Error('failed');
                    return res.json();
                }).then(function(resp) {
                    if (!resp.success) {
                        alert('Gagal update approval');
                        self.value = previous;
                    } else {
                        self.dataset.prev = status;
                        var row = document.querySelector('.leave-row[data-request-id="' + requestId + '"]');
                        var nextStatus = resp.status || null;
                        var nextStatusLabel = resp.status_label || getStatusLabel(nextStatus);

                        if (row && nextStatus) {
                            row.dataset.status = nextStatus;
                        }

                        var statusBadge = document.querySelector('.leave-status-badge[data-request-id="' + requestId + '"]');
                        if (statusBadge && nextStatusLabel) {
                            statusBadge.textContent = nextStatusLabel;
                            statusBadge.classList.remove('bg-secondary', 'bg-warning', 'bg-success', 'bg-danger');
                            statusBadge.classList.add(getStatusBadgeClass(nextStatus));
                        }
                    }
                    self.disabled = false;
                }).catch(function() {
                    alert('Gagal update approval');
                    self.value = previous;
                    self.disabled = false;
                });
            });
            select.dataset.prev = select.value;
        });

        var workCalendarData = <?= json_encode(!empty($work_calendar_summary) ? $work_calendar_summary : [], JSON_UNESCAPED_UNICODE) ?>;
        var workCalendar = null;
        var workCalendarEl = document.getElementById('work-calendar');
        var workCalendarDetailEl = document.getElementById('work-calendar-detail');
        var workCalendarColors = {
            total: { background: '#f3e8ff', border: '#d8b4fe', text: '#6b21a8' },
            izin: { background: '#dbeafe', border: '#93c5fd', text: '#1e40af' },
            cuti: { background: '#ffe4e6', border: '#fda4af', text: '#9f1239' }
        };

        function workCalendarYmd(date) {
            var year = date.getFullYear();
            var month = String(date.getMonth() + 1).padStart(2, '0');
            var day = String(date.getDate()).padStart(2, '0');
            return year + '-' + month + '-' + day;
        }

        function escapeHtml(str) {
            return String(str || '').replace(/[&<>"']/g, function(c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        }

        function formatCalendarDate(dateStr) {
            var date = new Date(dateStr + 'T00:00:00');
            if (isNaN(date.getTime())) return dateStr;
            return date.toLocaleDateString('id-ID', {
                weekday: 'long',
                day: 'numeric',
                month: 'long',
                year: 'numeric'
            });
        }

        function getWorkCalendarSummary(dateStr) {
            return workCalendarData[dateStr] || {
                wfo: { count: 0, people: [] },
                izin: { count: 0, people: [] },
                cuti: { count: 0, people: [] }
            };
        }

        function buildWorkCalendarEvents() {
            var events = [];

            Object.keys(workCalendarData || {}).forEach(function(dateStr) {
                var summary = getWorkCalendarSummary(dateStr);
                var izinCount = summary.izin && summary.izin.count ? parseInt(summary.izin.count, 10) : 0;
                var cutiCount = summary.cuti && summary.cuti.count ? parseInt(summary.cuti.count, 10) : 0;
                var totalCount = izinCount + cutiCount;
                if (totalCount <= 0) return;

                events.push({
                    title: String(totalCount),
                    start: dateStr,
                    allDay: true,
                    backgroundColor: workCalendarColors.total.background,
                    borderColor: workCalendarColors.total.border,
                    textColor: workCalendarColors.total.text,
                    classNames: ['work-calendar-count-event', 'work-calendar-count-total'],
                    extendedProps: {
                        category: 'total',
                        label: 'Izin/Cuti'
                    }
                });
            });

            return events;
        }

        function selectWorkCalendarDate(dateStr) {
            document.querySelectorAll('#work-calendar .fc-daygrid-day.selected-date').forEach(function(cell) {
                cell.classList.remove('selected-date');
            });
            var cell = document.querySelector('#work-calendar .fc-daygrid-day[data-date="' + dateStr + '"]');
            if (cell) {
                cell.classList.add('selected-date');
            }
        }

        function renderPeopleSection(title, key, summary) {
            var data = summary[key] || { count: 0, people: [] };
            var html = '<div class="work-calendar-section ' + key + '">';
            html += '<div class="d-flex justify-content-between align-items-center mb-2">';
            html += '<strong>' + title + '</strong>';
            html += '<span class="work-calendar-pill ' + key + '">' + (data.count || 0) + '</span>';
            html += '</div>';

            if (!data.people || data.people.length === 0) {
                html += '<div class="text-muted small">Tidak ada data.</div>';
            } else {
                data.people.forEach(function(person) {
                    html += '<div class="work-calendar-person">';
                    html += '<div class="fw-semibold">' + escapeHtml(person.name || '-') + '</div>';
                    if (person.leave_type) {
                        html += '<div class="small text-muted">' + escapeHtml(person.leave_type) + '</div>';
                    }
                    html += '</div>';
                });
            }

            html += '</div>';
            return html;
        }

        function renderWorkCalendarDetail(dateStr) {
            if (!workCalendarDetailEl) return;

            var summary = getWorkCalendarSummary(dateStr);
            var total = (summary.izin.count || 0) + (summary.cuti.count || 0);
            var html = '<div class="mb-3">';
            html += '<div class="fw-bold">' + escapeHtml(formatCalendarDate(dateStr)) + '</div>';
            html += '<div class="text-muted small">Total izin/cuti: ' + total + ' orang</div>';
            html += '</div>';
            html += renderPeopleSection('Izin', 'izin', summary);
            html += renderPeopleSection('Cuti', 'cuti', summary);
            workCalendarDetailEl.innerHTML = html;
        }

        function initWorkCalendar() {
            if (!workCalendarEl || workCalendar) return;

            workCalendar = new FullCalendar.Calendar(workCalendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev',
                    center: 'title',
                    right: 'next'
                },
                locale: 'id',
                height: 'auto',
                contentHeight: 500,
                events: buildWorkCalendarEvents(),
                dayMaxEvents: false,
                eventContent: function(arg) {
                    return { html: '<span class="work-event-count">' + escapeHtml(arg.event.title) + '</span>' };
                },
                eventDidMount: function(arg) {
                    var props = arg.event.extendedProps || {};
                    arg.el.setAttribute('title', (props.label || '') + ': ' + arg.event.title);
                },
                eventClick: function(info) {
                    info.jsEvent.preventDefault();
                    var dateStr = workCalendarYmd(info.event.start);
                    selectWorkCalendarDate(dateStr);
                    renderWorkCalendarDetail(dateStr);
                },
                dateClick: function(info) {
                    selectWorkCalendarDate(info.dateStr);
                    renderWorkCalendarDetail(info.dateStr);
                }
            });

            workCalendar.render();
        }

        var leaveCalendarTab = document.getElementById('leave-calendar-tab');
        if (leaveCalendarTab) {
            leaveCalendarTab.addEventListener('shown.bs.tab', function() {
                initWorkCalendar();
                if (workCalendar) {
                    workCalendar.updateSize();
                }
            });
        }

        var leaveTypeModal = document.getElementById('leaveTypeModal');
        var leaveTypeForm = document.getElementById('leaveTypeForm');
        var leaveTypeList = document.getElementById('leave-types-list');
        var leaveTypeName = document.getElementById('leave_type_name');
        var leaveTypeRequiresAttachment = document.getElementById('leave_type_requires_attachment');
        var leaveTypeDeductsLeave = document.getElementById('leave_type_deducts_leave');

        function loadLeaveTypesManage() {
            if (!leaveTypeList) return;
            fetch('<?= base_url() ?>leave/get_leave_types_manage')
                .then(function(res) {
                    if (!res.ok) throw new Error('failed');
                    return res.text();
                })
                .then(function(html) {
                    leaveTypeList.innerHTML = html || '<div class="text-muted">Belum ada tipe cuti.</div>';
                })
                .catch(function() {
                    leaveTypeList.innerHTML = '<div class="text-danger">Gagal memuat data tipe cuti.</div>';
                });
        }

        if (leaveTypeModal) {
            leaveTypeModal.addEventListener('show.bs.modal', function() {
                loadLeaveTypesManage();
            });
        }

        if (leaveTypeForm) {
            leaveTypeForm.addEventListener('submit', function(e) {
                e.preventDefault();
                var formData = new FormData(leaveTypeForm);

                fetch(leaveTypeForm.action, {
                    method: 'POST',
                    body: new URLSearchParams(formData)
                })
                    .then(function(res) {
                        if (!res.ok) throw new Error('failed');
                        return res.json();
                    })
                    .then(function(response) {
                        if (response.status === 'success') {
                            if (leaveTypeName) leaveTypeName.value = '';
                            if (leaveTypeRequiresAttachment) leaveTypeRequiresAttachment.checked = false;
                            if (leaveTypeDeductsLeave) leaveTypeDeductsLeave.checked = false;
                            loadLeaveTypesManage();
                            alert('Tipe cuti berhasil ditambahkan');
                        } else {
                            alert(response.message || 'Gagal menambahkan tipe cuti.');
                        }
                    })
                    .catch(function() {
                        alert('Terjadi kesalahan saat menyimpan tipe cuti.');
                    });
            });
        }

        document.addEventListener('click', function(e) {
            var deleteBtn = e.target.closest('.delete-leave-type');
            if (!deleteBtn) return;
            e.preventDefault();

            var item = deleteBtn.closest('.leave-type-item');
            var leaveTypeId = item ? item.dataset.id : '';
            if (!leaveTypeId) return;

            if (!confirm('Apakah Anda yakin ingin menghapus tipe cuti ini?')) {
                return;
            }

            fetch('<?= base_url() ?>leave/delete_leave_type/' + encodeURIComponent(leaveTypeId), {
                method: 'POST'
            })
                .then(function(res) {
                    if (!res.ok) throw new Error('failed');
                    return res.json();
                })
                .then(function(response) {
                    if (response.status === 'success') {
                        if (item) {
                            item.remove();
                        }
                    } else {
                        alert(response.message || 'Gagal menghapus tipe cuti.');
                    }
                })
                .catch(function() {
                    alert('Terjadi kesalahan saat menghapus tipe cuti.');
                });
        });

    });
</script>
