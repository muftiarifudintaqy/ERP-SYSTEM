<div class="w-100">
    <style>
        .fc table {
            font-size: 0 !important;
        }

        .fc table tr th,
        .fc table tr td {
            padding: 0px !important;
            border-radius: 0 !important;
            background: transparent !important;
        }

        .fc table tr td:first-child,
        .fc table tr td:last-child,
        .fc table tr th:first-child,
        .fc table tr th:last-child {
            border-radius: 0 !important;
        }

        table tr td {
            background: #fff !important;
            padding: 0px 0px !important;
        }

        table tr:first-child th:last-child {
            padding: 0px 0px !important;
        }

        table tr:first-child td:first-child {
            padding: 0px 0px !important;
        }

        /* === Container Layout === */
        #calendar-container {
            display: flex;
            flex-direction: column;
            height: 100%;
            width: 100%;
            flex: 2;
            position: relative;
        }

        #calendar {
            
            overflow: hidden;
        }

        .fc-daygrid-day.selected-date {
            background-color: #fae0e9ff !important;
            border: 2px solid #ffc8daff !important;
        }

        #event-details {
            flex: 1;
            padding: 15px;
            overflow-y: auto;
            background-color: white;
            border-radius: 10px;
        }

        #event-details h4 {
            font-size: 1.3em;
            margin-bottom: 1px;
        }

        #event-details p {
            font-size: 0.9em;
            line-height: 1.4;
        }

        /* === Calendar Toolbar (Month/Year) === */
        .fc-toolbar {
            margin-bottom: 5px !important;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            padding: 10px 0;
        }

        .fc-toolbar-title {
            font-size: 1.1em !important;
            margin: 0 15px !important;
            order: 2;
        }

        .fc-prev-button {
            order: 1;
            background-color: transparent !important;
            color: #333 !important;
            border: none !important;
            box-shadow: none !important;
        }

        .fc-next-button {
            order: 3;
            background-color: transparent !important;
            color: #333 !important;
            border: none !important;
            box-shadow: none !important;
        }

        .fc-button {
            padding: 5px 10px !important;
            font-size: 0.9em !important;
        }

        /* Light theme adjustments */
        .fc-theme-standard {
            background-color: white !important;
        }

        .fc-theme-standard .fc-scrollgrid {
            border: 1px solid #e0e0e0 !important;
        }

        .fc-daygrid-day {
            background-color: white !important;
            border: 1px solid #e0e0e0 !important;
        }

        .fc-col-header-cell {
            background-color: #f5f5f5 !important;
        }

        .fc-daygrid-day-number {
            color: #333 !important;
        }

        /* === Day Header (Min-Sab) === */
        .fc-theme-standard .fc-col-header {
            height: 24px !important;
        }

        .fc-col-header-cell {
            padding: 0 !important;
            margin: 0 !important;
            height: 10px !important;
            vertical-align: top !important;
            border: none !important;
        }

        .fc-col-header-cell-cushion {
            all: unset;
            display: block;
            text-align: center;
            font-size: 12px;
            font-weight: normal;
            color: #000;
        }

        /* === Calendar Grid Cells === */
        th.fc-col-header-cell {
            padding: 0 !important;
            margin: 0 !important;
            height: 0px !important;
            line-height: 0 !important;
            vertical-align: middle !important;
            background-color: #fae0e9ff !important;
        }

        .fc-daygrid-day {
            height: 80px !important;
            padding: 1px !important;
            border: 1px solid #ddd !important;
            position: relative;
            box-sizing: border-box;
            cursor: pointer;
        }

        .fc-daygrid-day-frame {
            height: 100% !important;
            min-height: unset !important;
            padding: 0 !important;
        }

        .fc-daygrid-day-top {
            display: flex;
            flex-direction: row-reverse;
            justify-content: space-between;
            align-items: flex-start;
            padding: 2px 4px !important;
            margin-left: auto !important;
        }

        .fc-daygrid-day-number {
            font-size: 12px !important;
            font-weight: 500 !important;
            padding: 0 2px !important;
            margin: 0 !important;
            color: #000 !important;
            text-decoration: none !important;
        }

        .fc-daygrid-body-balanced .fc-daygrid-day-events {
            min-height: 0 !important;
        }

        .fc-daygrid-day:hover {
            background-color: #fce2ebff !important;
            box-shadow: 0px 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* === Grid Container Borders === */
        .fc-scrollgrid {
            border: 1px solid #ddd !important;
            border-collapse: separate !important;
            border-spacing: 0 !important;
        }

        /* === Responsive Adjustments === */
        @media (max-width: 768px) {
            .fc-toolbar {
                flex-direction: column;
                align-items: flex-start;
            }

            .fc-toolbar-chunk {
                justify-content: flex-start;
                width: 100%;
                margin-bottom: 3px;
            }

            .fc-toolbar-title {
                font-size: 14px !important;
            }

            .fc-button {
                font-size: 10px !important;
                padding: 2px 4px !important;
            }

            #calendar {
                
            }
        }
    </style>

    <div class="container-fluid py-3">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-calendar-event me-2"></i>Kalender Event</h5>
            </div>
            <div class="card-body">
                <?php if ($this->session->flashdata('success')): ?>
                    <div class="alert alert-success">
                        <?= $this->session->flashdata('success') ?>
                    </div>
                <?php endif; ?>
                <?php if ($this->session->flashdata('error')): ?>
                    <div class="alert alert-danger">
                        <?= $this->session->flashdata('error') ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($can_manage)): ?>
                    <div class="d-flex justify-content-end mb-3">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#leaveEventModal" id="btn-create-event">
                            <i class="bi bi-plus-circle me-1"></i> Tambah Event
                        </button>
                    </div>
                <?php endif; ?>

                <div class="row bg-white" style="height: auto; border-radius: 10px; margin-left: 1px; margin-right: 1px">
                    <div class="col-lg-9" id="calendar-container">
                        <div id="calendar" class="p-3" style=" box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); border-radius: 10px; border: 2px solid #e9ecef;"></div>
                    </div>
                    <div class="col-lg-3 bg-white" id="event-details" style="padding: 20px; height: 600px; overflow-y: auto; border: 1px solid #ddd; border-radius: 10px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">
                        <div style="text-align: center;">
                            <img src="<?= base_url() ?>/assets/img/icon/load.gif" alt="Not Found" style="margin-top: 50px; max-width: 600px;">
                            <p style="font-weight: 600; margin-top: 10px; font-size: 25px;">Pilih tanggal terlebih dahulu</p>
                            <p style="color: gray; margin-top: -10px; font-size: 16px;">Klik tanggal di kalender untuk melihat event.</p>
                        </div>
                    </div>
                </div>

                <?php if (!empty($can_manage)): ?>
                    <div class="modal fade" id="leaveEventModal" tabindex="-1" aria-labelledby="leaveEventModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="leaveEventModalLabel">Event</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form id="leave-event-form" method="POST" action="<?= base_url() ?>leave/store_event">
                                    <div class="modal-body">
                                        <input type="hidden" name="id" id="event_id">
                                        <div class="mb-3">
                                            <label class="form-label">Judul Event</label>
                                            <input type="text" name="title" id="event_title" class="form-control" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Tanggal Mulai</label>
                                            <input type="date" name="start_date" id="event_start_date" class="form-control" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Tanggal Selesai</label>
                                            <input type="date" name="end_date" id="event_end_date" class="form-control" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Deskripsi</label>
                                            <textarea name="description" id="event_description" class="form-control" rows="2"></textarea>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="is_blocking" id="event_is_blocking" value="1">
                                            <label class="form-check-label" for="event_is_blocking">Event Blocking</label>
                                        </div>
                                    </div>
                                    <div class="modal-footer d-flex justify-content-between">
                                        <a href="#" class="btn btn-outline-danger d-none" id="event-delete-link" onclick="return confirm('Hapus event ini?')">
                                            Hapus
                                        </a>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-primary">Simpan</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.7/index.global.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var eventDetailsEl = document.getElementById('event-details');

            var initialView = 'dayGridMonth';
            var events = <?php
                $calendar_events = [];
                if (!empty($events)) {
                    foreach ($events as $event) {
                        $end = date('Y-m-d', strtotime($event['end_date'] . ' +1 day'));
                        $calendar_events[] = [
                            'id' => $event['id'],
                            'title' => $event['title'],
                            'start' => $event['start_date'],
                            'end' => $end,
                            'allDay' => true,
                            'backgroundColor' => !empty($event['is_blocking']) ? '#ff4d4f' : '#1890ff',
                            'borderColor' => !empty($event['is_blocking']) ? '#ff4d4f' : '#1890ff',
                            'textColor' => '#ffffff',
                            'extendedProps' => [
                                'description' => $event['description'],
                                'is_blocking' => !empty($event['is_blocking']) ? 1 : 0
                            ]
                        ];
                    }
                }
                echo json_encode($calendar_events);
            ?>;

            function toYMD(date) {
                return date.toISOString().slice(0, 10);
            }

            function escapeHtml(str) {
                return String(str).replace(/[&<>"']/g, function(c) {
                    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
                });
            }

            function formatDate(dateStr) {
                if (!dateStr) return '-';
                var d = new Date(String(dateStr).slice(0, 10) + 'T00:00:00');
                if (isNaN(d.getTime())) return dateStr;
                return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
            }

            function renderEventDetailsPlaceholder() {
                eventDetailsEl.innerHTML = `
                <div style="text-align: center;">
                    <img src="<?= base_url() ?>/assets/img/icon/load.gif" alt="Not Found" style="margin-top: 50px; max-width: 600px;">
                    <p style="font-weight: 600; margin-top: 10px; font-size: 25px;">Pilih tanggal terlebih dahulu</p>
                    <p style="color: gray; margin-top: -10px; font-size: 16px;">Klik tanggal di kalender untuk melihat event.</p>
                </div>
            `;
            }

            function renderEventDetailsForDate(dateString) {
                const list = events.filter(function(event) {
                    const start = event.start;
                    const end = event.end || event.start;
                    return dateString >= start && dateString < end;
                });

                if (list.length === 0) {
                    eventDetailsEl.innerHTML = `
                    <div style="text-align: center;">
                        <img src="<?= base_url() ?>/assets/img/icon/load.gif" alt="Not Found" style="margin-top: 50px; max-width: 600px;">
                        <p style="font-weight: 600; margin-top: 10px; font-size: 25px;">Oops... Data Kosong</p>
                        <p style="color: gray; margin-top: -10px; font-size: 16px;">Tidak ada event pada tanggal yang dipilih</p>
                    </div>
                `;
                    return;
                }

                eventDetailsEl.innerHTML = '';
                const header = document.createElement('div');
                header.style.cssText = 'background-color: #f8f9fa; padding: 8px; margin: 10px 0 5px 0; border-radius: 5px; font-weight: 600; font-size: 13px; text-align: center; color: #495057;';
                header.textContent = new Date(dateString + 'T00:00:00').toLocaleDateString('id-ID', {
                    weekday: 'short',
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                });
                eventDetailsEl.appendChild(header);

                list.forEach(function(event) {
                    const item = document.createElement('div');
                    item.classList.add('leave-event-card');
                    item.setAttribute('data-event-id', event.id || '');
                    const borderColor = event.extendedProps.is_blocking ? '#ff4d4f' : '#1890ff';
                    item.innerHTML = `
                    <div style="border-left: 4px solid ${borderColor}; border-top: 1px solid #E8D8E8; border-bottom: 1px solid #E8D8E8; border-right: 1px solid #E8D8E8; position: relative; padding: 10px; background-color: #fff; border-radius: 8px; margin-bottom: 10px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 14px; font-weight: 600;">${event.title}</span>
                            <span class="badge" style="background-color:${event.extendedProps.is_blocking ? '#ff4d4f' : '#1890ff'}; color:#fff;">${event.extendedProps.is_blocking ? 'BLOCKING' : 'EVENT'}</span>
                        </div>
                        <div style="font-size: 12px; color: gray; margin-top: 4px;">${event.extendedProps.description || '-'}</div>
                    </div>
                    `;
                    if (canManage) {
                        item.style.cursor = 'pointer';
                        item.addEventListener('click', function() {
                            openEditModalById(event.id);
                        });
                    }
                    eventDetailsEl.appendChild(item);
                });
            }

            function clearSelectedDate() {
                document.querySelectorAll('.fc-daygrid-day.selected-date').forEach(function(cell) {
                    cell.classList.remove('selected-date');
                });
            }

            function openEditModalById(eventId) {
                if (!eventId) return;
                var found = events.find(function(ev) { return String(ev.id) === String(eventId); });
                if (found) {
                    openEditModal({
                        id: found.id,
                        title: found.title,
                        startStr: found.start,
                        endStr: found.end,
                        extendedProps: found.extendedProps || {}
                    });
                }
            }

            var canManage = <?= !empty($can_manage) ? 'true' : 'false' ?>;
            var modalEl = document.getElementById('leaveEventModal');
            var modalInstance = modalEl ? new bootstrap.Modal(modalEl) : null;

            function openCreateModal() {
                if (!canManage || !modalInstance) return;
                document.getElementById('leave-event-form').action = '<?= base_url() ?>leave/store_event';
                document.getElementById('leaveEventModalLabel').textContent = 'Tambah Event';
                document.getElementById('event_id').value = '';
                document.getElementById('event_title').value = '';
                document.getElementById('event_start_date').value = '';
                document.getElementById('event_end_date').value = '';
                document.getElementById('event_description').value = '';
                document.getElementById('event_is_blocking').checked = false;
                document.getElementById('event-delete-link').classList.add('d-none');
                modalInstance.show();
            }

            function openEditModal(event) {
                if (!canManage || !modalInstance) return;
                document.getElementById('leave-event-form').action = '<?= base_url() ?>leave/update_event';
                document.getElementById('leaveEventModalLabel').textContent = 'Edit Event';
                document.getElementById('event_id').value = event.id || '';
                document.getElementById('event_title').value = event.title || '';
                document.getElementById('event_start_date').value = event.startStr || '';
                document.getElementById('event_end_date').value = event.endStr ? event.endStr : event.startStr;
                document.getElementById('event_description').value = event.extendedProps.description || '';
                document.getElementById('event_is_blocking').checked = !!event.extendedProps.is_blocking;
                var del = document.getElementById('event-delete-link');
                del.href = '<?= base_url() ?>leave/delete_event?id=' + event.id;
                del.classList.remove('d-none');
                modalInstance.show();
            }

            var createBtn = document.getElementById('btn-create-event');
            if (createBtn) {
                createBtn.addEventListener('click', function() {
                    openCreateModal();
                });
            }

            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: initialView,
                headerToolbar: {
                    left: 'prev',
                    center: 'title',
                    right: 'next'
                },
                locale: 'id',
                height: 'auto',
                contentHeight: 500,
                events: events,
                dateClick: function(info) {
                    clearSelectedDate();
                    const cell = document.querySelector('.fc-daygrid-day[data-date="' + info.dateStr + '"]');
                    if (cell) cell.classList.add('selected-date');
                    renderEventDetailsForDate(info.dateStr);
                },
                eventClick: function(info) {
                    info.jsEvent.preventDefault();
                    openEditModal(info.event);
                },
            });

            calendar.render();
            renderEventDetailsPlaceholder();
        });
    </script>
</div>

<script>
/* FullCalendar HP: paksa hitung ulang lebar setelah layout final */
(function(){
  function hitungUlang(){
    try { window.dispatchEvent(new Event('resize')); } catch(e){}
  }
  function jalan(){
    hitungUlang();
    setTimeout(hitungUlang, 350);
    setTimeout(hitungUlang, 900);
    setTimeout(hitungUlang, 1800);
  }
  if (document.readyState === 'complete') jalan();
  else window.addEventListener('load', jalan);

  window.addEventListener('orientationchange', function(){ setTimeout(hitungUlang, 350); });

  var el = document.getElementById('calendar');
  if (el && window.ResizeObserver){
    var p = el.parentElement;
    if (p){
      var t = null;
      new ResizeObserver(function(){
        clearTimeout(t); t = setTimeout(hitungUlang, 150);
      }).observe(p);
    }
  }
})();
</script>
