<style>
    .crm-chat-wrapper {
        display: flex;
        height: calc(100vh - 160px);
        min-height: 520px;
        border: 1px solid #f0f0f0;
        border-radius: 8px;
        overflow: hidden;
        background: #fff;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    }

    .crm-chat-sidebar {
        width: 300px;
        border-right: 1px solid #f0f0f0;
        display: flex;
        flex-direction: column;
        background: #fafafa;
    }

    .crm-chat-sidebar-header {
        padding: 14px 16px;
        border-bottom: 1px solid #f0f0f0;
        background: #fff;
    }

    .crm-chat-search {
        width: 100%;
        padding: 6px 10px;
        border: 1px solid #d9d9d9;
        border-radius: 6px;
        font-size: 13px;
    }

    .crm-chat-contacts {
        flex: 1;
        overflow-y: auto;
    }

    .crm-chat-contact {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 16px;
        cursor: pointer;
        border-bottom: 1px solid #f0f0f0;
        transition: background 0.15s ease;
    }

    .crm-chat-contact:hover {
        background: #f0f5ff;
    }

    .crm-chat-contact.active {
        background: #e6f7ff;
    }

    .crm-chat-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #1890ff;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 14px;
        flex-shrink: 0;
    }

    .crm-chat-contact-info {
        flex: 1;
        min-width: 0;
    }

    .crm-chat-contact-name {
        font-size: 13px;
        font-weight: 600;
        color: rgba(0, 0, 0, 0.85);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .crm-chat-contact-preview {
        font-size: 12px;
        color: rgba(0, 0, 0, 0.45);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .crm-chat-contact-meta {
        font-size: 10px;
        color: rgba(0, 0, 0, 0.45);
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 4px;
    }

    .crm-chat-unread {
        background: #ff4d4f;
        color: #fff;
        border-radius: 10px;
        padding: 1px 6px;
        font-size: 10px;
        font-weight: 600;
        min-width: 18px;
        text-align: center;
    }

    .crm-chat-channel {
        width: 14px;
        height: 14px;
        border-radius: 3px;
    }

    .crm-chat-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: #fff;
    }

    .crm-chat-main-header {
        padding: 12px 18px;
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        align-items: center;
        gap: 10px;
        background: #fff;
    }

    .crm-chat-main-title {
        font-size: 14px;
        font-weight: 600;
        color: rgba(0, 0, 0, 0.85);
    }

    .crm-chat-main-sub {
        font-size: 12px;
        color: rgba(0, 0, 0, 0.45);
    }

    .crm-chat-body {
        flex: 1;
        overflow-y: auto;
        padding: 18px;
        background: #f5f5f5;
    }

    .crm-chat-bubble-row {
        display: flex;
        margin-bottom: 10px;
    }

    .crm-chat-bubble-row.me {
        justify-content: flex-end;
    }

    .crm-chat-bubble {
        max-width: 65%;
        padding: 8px 12px;
        border-radius: 12px;
        font-size: 13px;
        line-height: 1.4;
        word-wrap: break-word;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.06);
    }

    .crm-chat-bubble.them {
        background: #fff;
        color: rgba(0, 0, 0, 0.85);
        border-top-left-radius: 2px;
    }

    .crm-chat-bubble.me {
        background: #1890ff;
        color: #fff;
        border-top-right-radius: 2px;
    }

    .crm-chat-bubble-time {
        font-size: 10px;
        opacity: 0.7;
        margin-top: 4px;
        text-align: right;
    }

    .crm-chat-footer {
        padding: 12px 16px;
        border-top: 1px solid #f0f0f0;
        background: #fff;
        display: flex;
        gap: 8px;
        align-items: flex-end;
    }

    .crm-chat-textarea {
        flex: 1;
        resize: none;
        min-height: 40px;
        max-height: 120px;
        padding: 8px 12px;
        border: 1px solid #d9d9d9;
        border-radius: 8px;
        font-size: 13px;
        outline: none;
        transition: border-color 0.2s;
    }

    .crm-chat-textarea:focus {
        border-color: #40a9ff;
        box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.15);
    }

    .crm-chat-send {
        background: #1890ff;
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 0 16px;
        height: 40px;
        font-size: 13px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: background 0.2s;
    }

    .crm-chat-send:hover {
        background: #40a9ff;
    }

    .crm-chat-quick {
        display: flex;
        gap: 6px;
        padding: 8px 16px 0;
        flex-wrap: wrap;
    }

    .crm-chat-quick-btn {
        background: #f0f5ff;
        border: 1px solid #d6e4ff;
        color: #1890ff;
        border-radius: 14px;
        padding: 3px 10px;
        font-size: 11px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .crm-chat-quick-btn:hover {
        background: #1890ff;
        color: #fff;
    }

    .crm-chat-empty {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: rgba(0, 0, 0, 0.35);
        font-size: 14px;
    }

    .crm-chat-empty i {
        font-size: 48px;
        margin-bottom: 12px;
    }

    @media (max-width: 768px) {
        .crm-chat-sidebar {
            width: 220px;
        }
    }
</style>

<div class="mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
        <h5 class="mb-1 fw-600"><i class="bi bi-chat-dots me-1"></i> Customer Chat</h5>
        <div class="text-muted" style="font-size: 12px;">Balas pesan customer TikTok dari semua toko di satu tempat.</div>
    </div>
    <a href="<?= base_url() ?>crm" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Kembali ke CRM</a>
</div>

<div class="crm-chat-wrapper">
    <div class="crm-chat-sidebar">
        <div class="crm-chat-sidebar-header">
            <input type="text" id="crmChatSearch" class="crm-chat-search" placeholder="Cari customer...">
        </div>
        <div class="crm-chat-contacts" id="crmChatContacts"></div>
    </div>
    <div class="crm-chat-main" id="crmChatMain">
        <div class="crm-chat-empty" id="crmChatEmpty">
            <i class="bi bi-chat-square-text"></i>
            <div>Pilih customer untuk mulai membalas chat</div>
        </div>
    </div>
</div>

<script>
(function() {
    var chatContacts = <?= json_encode(isset($chat_contacts) ? $chat_contacts : array(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    var activeId = null;

    function escapeHtml(str) {
        if (str == null) return '';
        return String(str)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function renderContacts(filter) {
        filter = (filter || '').toLowerCase().trim();
        var html = chatContacts
            .filter(function(c) {
                var searchText = [
                    c.name || '',
                    c.sender_id || '',
                    c.shop || '',
                    c.preview || '',
                    c.channel || ''
                ].join(' ').toLowerCase();
                return !filter || searchText.indexOf(filter) !== -1;
            })
            .map(function(c) {
                var activeCls = c.id === activeId ? ' active' : '';
                var unreadHtml = c.unread > 0 ? '<span class="crm-chat-unread">' + c.unread + '</span>' : '';
                var channelColor = c.channel_color || '#1890ff';
                var channel = c.channel || '-';
                return '<div class="crm-chat-contact' + activeCls + '" data-id="' + c.id + '">'
                    + '<div class="crm-chat-avatar" style="background:' + channelColor + '">' + escapeHtml(c.initials) + '</div>'
                    + '<div class="crm-chat-contact-info">'
                    +   '<div class="crm-chat-contact-name">' + escapeHtml(c.name) + '</div>'
                    +   '<div class="crm-chat-contact-preview"><i class="bi bi-shop" style="font-size:10px;"></i> ' + escapeHtml(c.shop) + '</div>'
                    +   '<div class="crm-chat-contact-preview">' + escapeHtml(c.preview) + '</div>'
                    + '</div>'
                    + '<div class="crm-chat-contact-meta">'
                    +   '<span>' + escapeHtml(c.time) + '</span>'
                    +   '<span style="font-size:10px;color:' + channelColor + ';font-weight:600;">' + escapeHtml(channel) + '</span>'
                    +   unreadHtml
                    + '</div>'
                    + '</div>';
            }).join('');
        if (!html) {
            html = '<div class="p-4 text-center text-muted" style="font-size:12px;">Tidak ada customer ditemukan.</div>';
        }
        document.getElementById('crmChatContacts').innerHTML = html;
    }

    function renderChat(contact) {
        var bubbles = contact.messages.map(function(m) {
            var cls = m.from === 'me' ? 'me' : 'them';
            return '<div class="crm-chat-bubble-row ' + cls + '">'
                + '<div class="crm-chat-bubble ' + cls + '">'
                +   escapeHtml(m.text)
                +   '<div class="crm-chat-bubble-time">' + escapeHtml(m.time_full || m.time || '-') + '</div>'
                + '</div>'
                + '</div>';
        }).join('');
        if (!bubbles) {
            bubbles = '<div class="text-center text-muted" style="font-size:12px;">Belum ada isi chat.</div>';
        }

        var channelColor = contact.channel_color || '#1890ff';
        var channel = contact.channel || '-';

        var html = ''
            + '<div class="crm-chat-main-header">'
            +   '<div class="crm-chat-avatar" style="background:' + channelColor + '">' + escapeHtml(contact.initials) + '</div>'
            +   '<div>'
            +     '<div class="crm-chat-main-title">' + escapeHtml(contact.name) + '</div>'
            +     '<div class="crm-chat-main-sub">via ' + escapeHtml(channel) + ' &middot; <i class="bi bi-shop"></i> ' + escapeHtml(contact.shop) + ' &middot; sender_id: ' + escapeHtml(contact.sender_id || '-') + '</div>'
            +   '</div>'
            + '</div>'
            + '<div class="crm-chat-body" id="crmChatBody">' + bubbles + '</div>'
            + '<div class="crm-chat-footer">'
            +   '<div class="text-muted small">Mode baca saja. Halaman ini menampilkan histori chat dari tabel <code>webhook_chat</code>.</div>'
            + '</div>';

        document.getElementById('crmChatMain').innerHTML = html;
        scrollChatToBottom();
    }

    function scrollChatToBottom() {
        var body = document.getElementById('crmChatBody');
        if (body) body.scrollTop = body.scrollHeight;
    }

    document.addEventListener('click', function(e) {
        var contactEl = e.target.closest('.crm-chat-contact');
        if (contactEl) {
            activeId = contactEl.getAttribute('data-id');
            var contact = chatContacts.find(function(c) { return String(c.id) === String(activeId); });
            if (contact) {
                contact.unread = 0;
                renderContacts(document.getElementById('crmChatSearch').value);
                renderChat(contact);
            }
            return;
        }
    });

    document.addEventListener('input', function(e) {
        if (e.target && e.target.id === 'crmChatSearch') {
            renderContacts(e.target.value);
        }
    });

    renderContacts('');
    if (chatContacts.length > 0) {
        activeId = chatContacts[0].id;
        renderContacts('');
        renderChat(chatContacts[0]);
    }
})();
</script>
