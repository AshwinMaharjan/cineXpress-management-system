<!-- ═══════════════════════════════════════════════════════════
     TICKET MODAL
     Replace the existing #ticketModal block and its <script> section
     in my_bookings.php with this snippet.
     Also add this line in <head>:
         <link rel="stylesheet" href="../css/ticket.css">
     ═══════════════════════════════════════════════════════════ -->

<!-- ── Ticket Modal ─────────────────────────────────────────── -->
<div id="ticketModal" onclick="closeTicketModal(event)">
    <div class="modal--ticket-shell">

        <!-- Loading state -->
        <div id="ticketLoading" style="text-align:center;padding:2.5rem 1rem;color:#555;">
            <i class="fa fa-circle-notch fa-spin" style="font-size:1.8rem;color:#e0b84e;"></i>
            <p style="margin-top:.75rem;font-size:.85rem;letter-spacing:.06em;text-transform:uppercase;">Loading ticket…</p>
        </div>

        <!-- Ticket content injected here -->
        <div id="ticketContent" style="display:none;"></div>

        <!-- Actions -->
        <div class="ticket-modal-actions" id="ticketActions" style="display:none;">
            <button class="btn-action btn-details" onclick="closeTicketModal()">
                <i class="fa fa-xmark"></i> Close
            </button>
            <button class="btn-action btn-ticket" onclick="printTicket()">
                <i class="fa fa-print"></i> Print Ticket
            </button>
        </div>

    </div>
</div>


<!-- ── Scripts ───────────────────────────────────────────────── -->
<script>
/* ── Ticket Modal ───────────────────────────── */
function openTicketModal(id) {
    const modal   = document.getElementById('ticketModal');
    const loading = document.getElementById('ticketLoading');
    const content = document.getElementById('ticketContent');
    const actions = document.getElementById('ticketActions');

    // Reset to loading state
    loading.style.display = 'block';
    content.style.display = 'none';
    content.innerHTML     = '';
    actions.style.display = 'none';

    modal.classList.add('active');
    document.body.style.overflow = 'hidden';

    fetch('ticket.php?id=' + id)
        .then(res => {
            if (!res.ok) throw new Error('Network error');
            return res.text();
        })
        .then(html => {
            content.innerHTML     = html;
            loading.style.display = 'none';
            content.style.display = 'block';
            actions.style.display = 'flex';
        })
        .catch(() => {
            content.innerHTML     = '<p style="color:#ef4444;padding:1rem;">Failed to load ticket. Please try again.</p>';
            loading.style.display = 'none';
            content.style.display = 'block';
            actions.style.display = 'flex';
        });
}

function closeTicketModal(e) {
    if (!e || e.target === document.getElementById('ticketModal')) {
        document.getElementById('ticketModal').classList.remove('active');
        document.body.style.overflow = '';
    }
}

function printTicket() {
    const content = document.getElementById('ticketContent').innerHTML;

    // Gather all stylesheets from the current page
    const links = Array.from(document.querySelectorAll('link[rel="stylesheet"]'))
        .map(l => `<link rel="stylesheet" href="${l.href}">`)
        .join('\n');

    const win = window.open('', '_blank', 'width=860,height=620');
    win.document.write(`<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print Ticket</title>
    ${links}
    <style>
        /* Print-window overrides */
        body {
            margin: 0;
            padding: 2rem;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            font-family: 'DM Sans', sans-serif;
        }
        .ticket-wrap {
            width: 680px;
            border: 2px solid #ddd;
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
        }
        .ticket-poster { width: 160px; min-width: 160px; }
        .ticket-poster__gradient { display: none; }
        .ticket-meta-pill { background: #f5f5f5; color: #a07c00; border-color: #ddd; }
        .ticket-details { background: #fff; padding: 1.4rem 1.6rem; }
        .ticket-ref { color: #999; }
        .ticket-ref strong { color: #a07c00; }
        .ticket-title { color: #111; font-size: 1.8rem; }
        .info-label { color: #999; }
        .info-value { color: #111; }
        .seat-chip { background: #f9f6ed; color: #a07c00; border-color: #e8d99a; }
        .pricing-row { color: #666; }
        .pricing-row--total { color: #a07c00; border-top-color: #eee; }
        .ticket-divider__line { border-color: #ddd; }
        .ticket-divider__notch { background: #fff; border-color: #ddd; }
        .ticket-status-bar.status--confirmed { background:#f0fdf4; color:#16a34a; border-color:#bbf7d0; }
        .ticket-status-bar.status--pending   { background:#fefce8; color:#a16207; border-color:#fde68a; }
        .ticket-status-bar.status--cancelled { background:#fef2f2; color:#dc2626; border-color:#fecaca; }
    </style>
</head>
<body>
    ${content}
</body>
</html>`);

    win.document.close();
    // Small delay to let stylesheets load before printing
    setTimeout(() => { win.focus(); win.print(); }, 600);
}

/* ── Toast auto-dismiss ─────────────────────── */
const toast = document.getElementById('toast');
if (toast) setTimeout(() => toast.classList.add('toast--hide'), 4000);

/* ── Cancel Modal ───────────────────────────── */
function openCancelModal(id, title, ref) {
    document.getElementById('modalBookingId').value = id;
    document.getElementById('modalBody').textContent =
        `Cancel booking #${ref} for "${title}"? This action cannot be undone.`;
    document.getElementById('cancelModal').classList.add('active');
}
function closeCancelModal(e) {
    if (!e || e.target === document.getElementById('cancelModal')) {
        document.getElementById('cancelModal').classList.remove('active');
    }
}

/* ── Expand/collapse detail panel ───────────── */
function toggleDetails(btn, id) {
    const panel = document.getElementById('panel-' + id);
    const isOpen = panel.classList.toggle('open');
    btn.innerHTML = isOpen
        ? '<i class="fa fa-eye-slash"></i> Hide'
        : '<i class="fa fa-eye"></i> Details';
}

/* ── Stagger card entrance animations ───────── */
document.querySelectorAll('.booking-card').forEach((card, i) => {
    card.style.opacity   = 0;
    card.style.transform = 'translateY(24px)';
    setTimeout(() => {
        card.style.transition = 'opacity 0.45s ease, transform 0.45s ease';
        card.style.opacity    = 1;
        card.style.transform  = 'translateY(0)';
    }, 80 + i * 70);
});
</script>