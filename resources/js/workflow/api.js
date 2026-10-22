// Thin fetch wrapper: session auth + CSRF (standard for Blade pages). Errors carry .status and .body.
export function createApi(root) {
  const d = root.dataset;
  async function req(method, url, body) {
    const res = await fetch(url, {
      method,
      credentials: 'same-origin',
      headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': d.csrf, 'X-Requested-With': 'XMLHttpRequest' },
      body: body ? JSON.stringify(body) : undefined,
    });
    let json = null;
    try { json = await res.json(); } catch (_) { /* empty body */ }
    if (!res.ok) { const e = new Error('HTTP ' + res.status); e.status = res.status; e.body = json; throw e; }
    return json;
  }
  return {
    save: (b) => req('PUT', d.updateUrl, b),
    publish: () => req('POST', d.publishUrl),
    simulate: (b) => req('POST', d.simulateUrl, b),
    latestRun: () => req('GET', d.runsUrl),
  };
}
