document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll('.gct-btn-create').forEach(btn => {
        btn.addEventListener('click', function() {
            let data = new URLSearchParams();
            data.append('action', 'gct_create_translation');
            data.append('source_id', this.dataset.source);
            data.append('lang', this.dataset.lang);
            data.append('nonce', this.dataset.nonce);
            
            fetch(ajaxurl, { method: 'POST', body: data })
                .then(r => r.json())
                .then(res => { if(res.success) window.location.href = res.data.edit_url; });
        });
    });

    document.querySelectorAll('.gct-btn-translate').forEach(btn => {
        btn.addEventListener('click', function() {
            this.textContent = "Traduciendo...";
            let data = new URLSearchParams();
            data.append('action', 'gct_run_translation');
            data.append('post_id', this.dataset.post);
            data.append('nonce', this.dataset.nonce);
            
            fetch(ajaxurl, { method: 'POST', body: data })
                .then(r => r.json())
                .then(res => { if(res.success) location.reload(); });
        });
    });
});