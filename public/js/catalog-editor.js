(() => {
    const form = document.querySelector('[data-catalog-editor]');
    if (!form) return;
    const gallery = form.querySelector('[data-gallery-rows]');
    const status = form.querySelector('[data-upload-status]');
    const save = form.querySelector('[data-catalog-save]');
    let uploading = false;
    const rows = () => [...gallery.querySelectorAll('[data-image-row]')];
    const empty = row => !row.querySelector('[data-image-name="id"], [data-image-name="ticket"]') && !row.querySelector('[data-image-name="url"]')?.value.trim() && !row.querySelector('[data-image-name="alt"]')?.value.trim();
    const renumber = () => {
        rows().forEach((row, index) => {
            row.querySelectorAll('[data-image-name]').forEach(input => { input.name = `images[${index}][${input.dataset.imageName}]`; });
            row.querySelector('[name="primary_index"]').value = index;
        });
        if (!rows().some(row => row.querySelector('[name="primary_index"]').checked) && rows()[0]) rows()[0].querySelector('[name="primary_index"]').checked = true;
        form.querySelector('[data-gallery-count]').textContent = `${rows().length} / 10`;
    };
    const bind = row => {
        const image = row.querySelector('[data-image-preview]');
        const placeholder = row.querySelector('[data-image-placeholder]');
        image.addEventListener('error', () => { image.hidden = true; placeholder.hidden = false; placeholder.textContent = 'Gambar belum bisa dibuka'; });
        image.addEventListener('load', () => { image.hidden = false; placeholder.hidden = true; });
        row.querySelector('[data-image-name="url"]')?.addEventListener('change', event => {
            if (event.target.value.startsWith('https://')) { image.src = event.target.value; image.hidden = false; placeholder.hidden = true; }
        });
        row.querySelector('[data-image-remove]').addEventListener('click', () => { row.remove(); renumber(); });
        row.querySelector('[data-image-up]').addEventListener('click', () => { if(row.previousElementSibling) gallery.insertBefore(row, row.previousElementSibling); renumber(); });
        row.querySelector('[data-image-down]').addEventListener('click', () => { if(row.nextElementSibling) gallery.insertBefore(row.nextElementSibling, row); renumber(); });
    };
    const createRow = () => {
        const row = form.querySelector('[data-image-template]').content.firstElementChild.cloneNode(true);
        gallery.append(row); bind(row); renumber(); return row;
    };
    rows().forEach(bind); renumber();
    form.querySelector('[data-add-image-link]').addEventListener('click', () => {
        const draft = rows().find(empty);
        if (draft) { draft.querySelector('[data-image-name="url"]').focus(); return; }
        if (rows().length >= 10) { status.textContent = 'Maksimal 10 foto. Hapus salah satu untuk menambah foto baru.'; return; }
        createRow().querySelector('[data-image-name="url"]').focus();
    });
    form.querySelector('[data-gallery-file]')?.addEventListener('change', async event => {
        if (uploading) return;
        rows().filter(empty).forEach(row => row.remove()); renumber();
        uploading = true; save.disabled = true;
        try {
            for (const [index, file] of [...event.target.files].entries()) {
                if (rows().length >= 10) { status.textContent = 'Maksimal 10 foto sudah tercapai.'; break; }
                if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 4 * 1024 * 1024) {
                    status.textContent = `${file.name}: pilih JPG, PNG, atau WEBP maksimal 4 MB.`; break;
                }
                status.textContent = `Mengunggah foto ${index + 1}: ${file.name}…`;
                const body = new FormData(); body.append('image', file); body.append('_token', form.querySelector('[name="_token"]').value);
                const response = await fetch(form.dataset.uploadUrl, { method: 'POST', body, headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                const data = await response.json();
                if (!response.ok) throw new Error(Object.values(data.errors ?? {}).flat()[0] ?? 'Upload belum berhasil. Coba lagi.');
                const row = createRow(); const url = row.querySelector('[data-image-name="url"]');
                const ticket = document.createElement('input'); ticket.type = 'hidden'; ticket.dataset.imageName = 'ticket'; ticket.value = data.ticket;
                url.closest('label').replaceWith(ticket);
                row.querySelector('[data-image-preview]').src = data.preview_url;
                row.querySelector('[data-image-preview]').hidden = false; row.querySelector('[data-image-placeholder]').hidden = true;
                renumber(); status.textContent = 'Foto tersimpan. Pilih cover, lalu simpan template untuk menyelesaikan.';
            }
        } catch(error) { status.textContent = error.message || 'Koneksi terputus. Coba unggah ulang foto.'; }
        finally { uploading = false; save.disabled = false; event.target.value = ''; }
    });
    const specs = form.querySelector('[data-specification-rows]');
    const numberSpecs = () => [...specs.querySelectorAll('[data-spec-row]')].forEach((row, index) => row.querySelectorAll('[data-spec-name]').forEach(input => { input.name = `specifications[${index}][${input.dataset.specName}]`; }));
    const bindSpec = row => row.querySelector('[data-remove-spec]').addEventListener('click', () => { row.remove(); numberSpecs(); });
    specs.querySelectorAll('[data-spec-row]').forEach(bindSpec);
    form.querySelector('[data-add-spec]').addEventListener('click', () => { if(specs.children.length >= 30) return; const row = form.querySelector('[data-spec-template]').content.firstElementChild.cloneNode(true); specs.append(row); bindSpec(row); numberSpecs(); row.querySelector('input').focus(); });
    const price = form.querySelector('[name="base_price"]');
    const pricePreview = () => { form.querySelector('[data-catalog-price]').textContent = `Rp${Math.max(0, Number(price.value) || 0).toLocaleString('id-ID')}`; };
    price.addEventListener('input', pricePreview); pricePreview();
    form.addEventListener('submit', event => {
        if (uploading) { event.preventDefault(); status.textContent = 'Tunggu hingga upload selesai sebelum menyimpan.'; return; }
        rows().filter(empty).forEach(row => row.remove()); renumber();
        [...specs.querySelectorAll('[data-spec-row]')].filter(row => [...row.querySelectorAll('input')].every(input => !input.value.trim())).forEach(row => row.remove()); numberSpecs();
    });
})();
