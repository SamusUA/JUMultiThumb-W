(function () {
	const script = document.currentScript;
	const mode = script && script.dataset.jumtMode;
	const selector = `a[data-jumt-lightbox="${mode}"]`;
	const articleImages = '.article-full-image img, .article-intro-image img, .com-content-article__full-image img, .com-content-article__intro-image img';

	function start() {
		document.querySelectorAll(articleImages).forEach((image) => {
			if (image.closest('a')) return;
			const link = document.createElement('a');
			link.href = image.currentSrc || image.src;
			link.dataset.jumtLightbox = mode;
			link.dataset.jumtGroup = 'jumultithumb-article-fields';
			link.title = image.alt;
			image.before(link);
			link.append(image);
		});

		const groups = new Map();
		document.querySelectorAll(selector).forEach((link) => {
			const gallery = link.closest('.juphotogallery');
			const key = gallery || link.dataset.jumtGroup || link;
			if (!groups.has(key)) groups.set(key, []);
			groups.get(key).push(link);
		});

		const escapeHtml = (text) => {
			const node = document.createElement('span');
			node.textContent = text;
			return node.innerHTML;
		};

		if (mode === 'zoomora' || mode === 'glightbox') {
			let index = 0;
			groups.forEach((links) => {
				const group = `jumultithumb-${mode}-${index++}`;
				links.forEach((link) => {
					link.dataset.gallery = group;
					if (mode === 'glightbox') {
						link.dataset.type = 'image';
						link.dataset.title = escapeHtml(link.title);
					}
				});
			});
			if (mode === 'zoomora') {
				window.Zoomora.bind(selector);
			} else {
				window.GLightbox({ selector });
			}
		} else if (mode === 'baguettebox') {
			const containers = [];
			groups.forEach((links) => {
				const container = document.createElement('div');
				container.hidden = true;
				container.className = 'jumt-baguette-group';
				links.forEach((link) => {
					const copy = document.createElement('a');
					copy.href = link.href;
					copy.dataset.caption = escapeHtml(link.title);
					container.append(copy);
					link.addEventListener('click', (event) => {
						event.preventDefault();
						copy.click();
					});
				});
				document.body.append(container);
				containers.push(container);
			});
			if (containers.length) window.baguetteBox.run('.jumt-baguette-group', { filter: /./ });
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', start, { once: true });
	} else {
		start();
	}
}());
