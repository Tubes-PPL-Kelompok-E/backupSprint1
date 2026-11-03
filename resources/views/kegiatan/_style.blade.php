<style>
    .kd-wrap { max-width: 760px; margin: 32px auto; padding: 0 16px; }
    .kd-wrap.kd-wide { max-width: 1100px; }
    .kd-card { background: #fff; border-radius: 14px; box-shadow: 0 1px 3px rgba(0,0,0,.08); padding: 28px; }
    .kd-title { font-size: 22px; font-weight: 700; color: #1f2937; margin: 0; }
    .kd-sub { color: #6b7280; font-size: 14px; margin: 4px 0 24px; }

    .kd-field { margin-bottom: 18px; }
    .kd-field label { display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 6px; }
    .kd-field input, .kd-field textarea {
        width: 100%; box-sizing: border-box; padding: 10px 12px;
        border: 1px solid #d1d5db; border-radius: 8px; font: inherit; background: #fff; color: #111827;
    }
    .kd-field input:focus, .kd-field textarea:focus {
        outline: none; border-color: #aa2b24; box-shadow: 0 0 0 3px rgba(170,43,36,.15);
    }
    .kd-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .kd-error { color: #b91c1c; font-size: 13px; margin-top: 4px; }
    .kd-info { background: #fef2f2; color: #7f1d1d; border-radius: 8px; padding: 10px 14px; font-size: 14px; }
    .kd-actions { display: flex; gap: 10px; margin-top: 24px; flex-wrap: wrap; }

    .kd-btn {
        display: inline-block; padding: 10px 20px; border-radius: 8px; border: 1px solid transparent;
        background: #aa2b24; color: #fff; font: inherit; font-weight: 600; cursor: pointer; text-decoration: none;
    }
    .kd-btn:hover { background: #8f221c; }
    .kd-btn-outline { background: #fff; color: #374151; border-color: #d1d5db; }
    .kd-btn-outline:hover { background: #f3f4f6; }
    .kd-btn-danger { background: #fff; color: #b91c1c; border-color: #fca5a5; }
    .kd-btn-danger:hover { background: #fef2f2; }
    .kd-btn-sm { padding: 6px 12px; font-size: 13px; }

    .kd-alert { padding: 10px 14px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
    .kd-alert-success { background: #ecfdf5; color: #065f46; }
    .kd-alert-error { background: #fef2f2; color: #991b1b; }

    .kd-badge { display: inline-block; padding: 2px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; }
    .kd-badge-terbuka { background: #dcfce7; color: #166534; }
    .kd-badge-ditutup { background: #e5e7eb; color: #374151; }

    .kd-head-card { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 20px; }
    .kd-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
    .kd-event { background: #fff; border-radius: 14px; box-shadow: 0 1px 3px rgba(0,0,0,.08); padding: 20px; display: flex; flex-direction: column; gap: 8px; }
    .kd-event-top { display: flex; justify-content: space-between; align-items: center; }
    .kd-event-title { font-size: 16px; font-weight: 700; color: #111827; margin: 4px 0; }
    .kd-time { font-size: 13px; color: #6b7280; }
    .kd-meta { font-size: 13px; color: #4b5563; }
    .kd-event-foot { display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f3f4f6; margin-top: 8px; padding-top: 12px; font-size: 13px; color: #6b7280; }
    .kd-red { color: #dc2626; }
    .kd-event-actions { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 8px; }

    .kd-detail { display: grid; grid-template-columns: 160px 1fr; gap: 10px 16px; font-size: 15px; margin: 0; }
    .kd-detail dt { color: #6b7280; font-weight: 600; }
    .kd-detail dd { margin: 0; color: #111827; }

    @media (max-width: 600px) {
        .kd-row { grid-template-columns: 1fr; }
        .kd-detail { grid-template-columns: 1fr; }
    }
</style>