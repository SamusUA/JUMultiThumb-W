import PhotoSwipeLightbox from './photoswipe-lightbox.esm.js';

const imageLinks = 'a[data-pswp-width][data-pswp-height]';

function bindLightbox(gallery, children) {
	const lightbox = new PhotoSwipeLightbox({
		gallery,
		children,
		pswpModule: () => import('./photoswipe.esm.js')
	});

	lightbox.init();
}

const bodyGalleries = [
	{
		group: 'jumultithumb-inline',
		selector: `${imageLinks}[data-pswp-gallery="jumultithumb-inline"]`
	},
	{
		group: 'jumultithumb-article-fields',
		selector: `${imageLinks}[data-pswp-gallery="jumultithumb-article-fields"]`
	}
];

bodyGalleries.forEach(({ group, selector }) => {
	const articleImageSelector = '.article-full-image img, .article-intro-image img, .com-content-article__full-image img, .com-content-article__intro-image img';
	const hasImages = document.querySelector(selector)
		|| (group === 'jumultithumb-article-fields' && document.querySelector(articleImageSelector));

	if (!hasImages) return;

	bindLightbox(document.body, `a[data-pswp-width][data-pswp-height][data-pswp-gallery="${group}"]`);
});

document.querySelectorAll('.article-full-image img, .article-intro-image img, .com-content-article__full-image img, .com-content-article__intro-image img').forEach((image) => {
	const wrapForPhotoSwipe = () => {
		if (!image.naturalWidth || !image.naturalHeight || image.closest('a')) return;

		const link = document.createElement('a');
		link.href = image.currentSrc || image.src;
		link.dataset.pswpWidth = String(image.naturalWidth);
		link.dataset.pswpHeight = String(image.naturalHeight);
		link.dataset.pswpGallery = 'jumultithumb-article-fields';
		image.before(link);
		link.append(image);
	};

	if (image.complete) {
		wrapForPhotoSwipe();
	} else {
		image.addEventListener('load', wrapForPhotoSwipe, { once: true });
	}
});

document.querySelectorAll('.juphotogallery').forEach((gallery) => {
	bindLightbox(gallery, imageLinks);
});
