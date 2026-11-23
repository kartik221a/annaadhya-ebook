/* Annaadhya Admin — confirm dialogs + niceties */
document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form[data-confirm]').forEach(function (form) {
                form.addEventListener('submit', function (e) {
                        if (!window.confirm(form.getAttribute('data-confirm'))) e.preventDefault();
                });
        });

        /* Home Hero panel: filter the book list while typing + live preview of
         * the selected book (cover, price and link come from the book itself). */
        var heroSearch = document.getElementById('aa-hero-search');
        var heroList = document.getElementById('aa-hero-list');
        var heroPrev = document.getElementById('aa-hero-preview');
        if (heroSearch && heroList && heroPrev) {
                var rows = heroList.querySelectorAll('label.aa-hero-book');
                var noneNote = heroList.querySelector('.aa-hero-none');
                var pWrap = heroPrev.querySelector('.aa-prev');
                var pImg = heroPrev.querySelector('.aa-prev-img');
                var pTitle = heroPrev.querySelector('.aa-prev-title');
                var pPrice = heroPrev.querySelector('.aa-prev-price');
                var pLink = heroPrev.querySelector('.aa-prev-link');
                var pMeta = heroPrev.querySelector('.aa-prev-meta');
                var pEmpty = heroPrev.querySelector('.aa-prev-empty');

                heroSearch.addEventListener('input', function () {
                        var q = heroSearch.value.trim().toLowerCase();
                        var any = false;
                        rows.forEach(function (row) {
                                var hay = (row.getAttribute('data-title') || '').toLowerCase();
                                var hit = !q || hay.indexOf(q) !== -1;
                                row.style.display = hit ? '' : 'none';
                                if (hit) any = true;
                        });
                        if (noneNote) noneNote.style.display = any ? 'none' : '';
                });

                heroList.addEventListener('change', function (e) {
                        var row = e.target.closest('label.aa-hero-book');
                        if (!row) return;
                        rows.forEach(function (r) { r.classList.remove('on'); });
                        row.classList.add('on');
                        if (pWrap) pWrap.style.display = '';
                        if (pEmpty) pEmpty.style.display = 'none';
                        if (pMeta) pMeta.style.display = '';
                        if (pImg) {
                                pImg.style.display = '';
                                pImg.src = row.getAttribute('data-cover') || '';
                                pImg.alt = (row.getAttribute('data-title') || 'book') + ' cover';
                        }
                        if (pTitle) pTitle.textContent = row.getAttribute('data-title') || '';
                        if (pPrice) pPrice.innerHTML = row.getAttribute('data-price') || '';
                        if (pLink) pLink.href = row.getAttribute('data-link') || '#';
                });
        }
});
