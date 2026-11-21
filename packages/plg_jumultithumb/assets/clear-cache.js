(() => {
	const button = document.querySelector('[data-jumt-clear-cache]');
	const status = document.querySelector('[data-jumt-clear-cache-status]');

	if (!button || !status) return;

	button.addEventListener('click', async () => {
		if (!window.confirm(button.dataset.confirm)) return;

		button.disabled = true;
		status.textContent = button.dataset.working;

		try {
			const body = new URLSearchParams();
			body.set('task', 'clearCache');
			body.set(button.dataset.tokenName, '1');

			const response = await fetch(button.dataset.url, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'X-Requested-With': 'XMLHttpRequest' },
				body,
			});
			const result = await response.json();

			if (!response.ok || !result.success) {
				throw new Error(result.message || button.dataset.error);
			}

			status.textContent = result.message;
		} catch (error) {
			status.textContent = error instanceof Error ? error.message : button.dataset.error;
		} finally {
			button.disabled = false;
		}
	});
})();
