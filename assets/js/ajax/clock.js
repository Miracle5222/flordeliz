(function () {
    function initClockAjax() {
        const inForm = document.getElementById('clock-in-form');
        const outForm = document.getElementById('clock-out-form');

        // Remove any server-rendered alert after a short delay
        try {
            const serverAlert = document.querySelector('#main-content .max-w-4xl > div > .mb-6.p-4.rounded-lg');
            if (serverAlert) setTimeout(() => { if (serverAlert.parentNode) serverAlert.parentNode.removeChild(serverAlert); }, 5000);
        } catch (e) { /* ignore */ }

        function showAlert(message, type) {
            const container = document.querySelector('#main-content .max-w-4xl > div');
            if (!container) return console.warn('Alert container not found');
            const old = container.querySelector('.ajax-alert');
            if (old) old.remove();
            const div = document.createElement('div');
            let cls = 'bg-blue-50 border border-blue-200 text-blue-700';
            if (type === 'success') cls = 'bg-green-50 border border-green-200 text-green-700';
            if (type === 'error') cls = 'bg-red-50 border border-red-200 text-red-700';
            if (type === 'warning') cls = 'bg-yellow-50 border border-yellow-200 text-yellow-700';
            div.className = 'ajax-alert mb-6 p-4 rounded-lg ' + cls;
            div.innerHTML = '<p class="font-semibold">' + message + '</p>';
            container.insertBefore(div, container.firstChild);
            // auto-close after 4 seconds
            setTimeout(() => {
                if (div && div.parentNode) div.parentNode.removeChild(div);
            }, 4000);
        }

        function updateValues(today) {
            const clockInEl = document.querySelector('.bg-gradient-to-br.from-teal-50 p.text-3xl');
            const clockOutEl = document.querySelector('.bg-gradient-to-br.from-orange-50 p.text-3xl');
            const hoursEl = document.querySelector('.bg-gradient-to-br.from-purple-50 p.text-3xl');
            if (!clockInEl || !clockOutEl || !hoursEl) return;
            if (today) {
                // Prefer server-formatted strings to avoid timezone parsing differences
                if (today.clock_in_formatted) {
                    clockInEl.textContent = today.clock_in_formatted;
                } else {
                    clockInEl.textContent = today.clock_in ? (new Date(today.clock_in.replace(' ', 'T'))).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '--:--';
                }

                if (today.clock_out_formatted) {
                    clockOutEl.textContent = today.clock_out_formatted;
                } else {
                    clockOutEl.textContent = today.clock_out ? (new Date(today.clock_out.replace(' ', 'T'))).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '--:--';
                }

                hoursEl.textContent = today.hours_worked_formatted || (today.hours_worked ? parseFloat(today.hours_worked).toFixed(2) : '0.00');
                const inBtn = document.getElementById('clock-in-btn');
                const outBtn = document.getElementById('clock-out-btn');
                if (inBtn) inBtn.disabled = !!today.clock_in;
                if (outBtn) outBtn.disabled = !(today.clock_in && !today.clock_out);
            }
        }

        async function submitFormEvent(e) {
            e.preventDefault && e.preventDefault();
            const form = e.target || e;
            const btn = form.querySelector('button[type="submit"]');
            if (btn) btn.disabled = true;
            const formData = new FormData(form);
            try {
                const res = await fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    body: formData,
                    credentials: 'same-origin'
                });

                const contentType = res.headers.get('content-type') || '';
                if (res.status === 401) {
                    showAlert('Session expired. Please login again.', 'error');
                    return;
                }

                if (contentType.indexOf('application/json') !== -1) {
                    const data = await res.json();
                    if (data.message) showAlert(data.message, data.message_type || 'info');
                    if (data.today) updateValues(data.today);
                    // if server provides global flags, enforce them
                    if (data.today && typeof data.today.can_clock_in !== 'undefined') {
                        const inBtn = document.getElementById('clock-in-btn');
                        const outBtn = document.getElementById('clock-out-btn');
                        if (inBtn) inBtn.disabled = !data.today.can_clock_in;
                        if (outBtn) outBtn.disabled = !data.today.can_clock_out;
                    }
                } else {
                    // got HTML or other response
                    const text = await res.text();
                    console.warn('Expected JSON but got:', text.slice(0, 200));
                    showAlert('Unexpected server response. See console for details.', 'error');
                }

            } catch (err) {
                console.error(err);
                showAlert('Network error. Please try again.', 'error');
            } finally {
                if (btn) btn.disabled = false;
            }
        }

        if (inForm) inForm.addEventListener('submit', submitFormEvent);
        if (outForm) outForm.addEventListener('submit', submitFormEvent);

        // fallback click handlers
        const inBtn = document.getElementById('clock-in-btn');
        const outBtn = document.getElementById('clock-out-btn');
        if (inBtn && inForm) inBtn.addEventListener('click', function (ev) { ev.preventDefault(); submitFormEvent(inForm); });
        if (outBtn && outForm) outBtn.addEventListener('click', function (ev) { ev.preventDefault(); submitFormEvent(outForm); });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initClockAjax);
    } else {
        initClockAjax();
    }
})();
