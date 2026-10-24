// JSON requests outside Inertia (the score grid saves cells without a page visit).
// Laravel sets the XSRF-TOKEN cookie on every response and accepts it back as a header.
function xsrfToken() {
    const cookie = document.cookie.split('; ').find((c) => c.startsWith('XSRF-TOKEN='));
    return cookie ? decodeURIComponent(cookie.slice('XSRF-TOKEN='.length)) : '';
}

/** Resolves to { status, body } for any HTTP status; rejects only when the network fails. */
export async function sendJson(method, url, data) {
    const response = await fetch(url, {
        method,
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': xsrfToken(),
        },
        body: JSON.stringify(data),
    });
    const body = await response.json().catch(() => ({}));
    return { status: response.status, body };
}
