import PhotoSwipeLightbox from './photoswipe-lightbox.esm.js';

const imageLinks = 'a.jumultithumb-pswp[data-pswp-width][data-pswp-height]';

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
		selector: `${imageLinks}[data-pswp-gallery="jumultithumb-inline"]`
	},
	{
		selector: `${imageLinks}[data-pswp-gallery="jumultithumb-module"]`
	}
];

bodyGalleries.forEach(({ selector }) => {
	if (!document.querySelector(selector)) return;

	bindLightbox(document.body, selector);
});

document.querySelectorAll('.juphotogallery').forEach((gallery) => {
	bindLightbox(gallery, imageLinks);
});
