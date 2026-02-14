document.addEventListener('DOMContentLoaded', function () {
    const sentinel = document.getElementById('sentinel');
    if (!sentinel) return;

    const normalizeNextUrl = (url) => {
        if (!url) return null;

        try {
            const parsed = new URL(url, window.location.href);
            return `${parsed.pathname}${parsed.search}${parsed.hash}`;
        } catch (error) {
            return url;
        }
    };

    let nextUrl = normalizeNextUrl(sentinel.getAttribute('data-next-url'));
    let isLoading = false;

    const observer = new IntersectionObserver((entries) => {
        if (entries[0].isIntersecting && nextUrl && !isLoading) {
            loadMore();
        }
    });

    observer.observe(sentinel);

    function loadMore() {
        isLoading = true;
        const loadingSpinner = document.getElementById('loading-spinner');
        if (loadingSpinner) loadingSpinner.style.display = 'block';

        fetch(nextUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => response.json())
            .then(data => {
                const container = document.getElementById('lazy-container');
                container.insertAdjacentHTML('beforeend', data.html);

                nextUrl = normalizeNextUrl(data.next_page_url);
                if (!nextUrl) {
                    observer.unobserve(sentinel);
                    sentinel.remove();
                }

                // Re-initialize any listeners if needed (e.g. for new cards)
                // For example, if you have specific click listeners on cards that aren't delegated

                // Dispatch a custom event in case other scripts need to know
                document.dispatchEvent(new CustomEvent('newItemsLoaded'));
            })
            .catch(error => {
                console.error('Error loading more items:', error);
            })
            .finally(() => {
                isLoading = false;
                if (loadingSpinner) loadingSpinner.style.display = 'none';
            });
    }
});
