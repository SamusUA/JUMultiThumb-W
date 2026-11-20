const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

const source = fs.readFileSync(path.join(__dirname, '..', 'packages', 'plg_jumultithumb', 'assets', 'lightboxes', 'lightbox-init.js'), 'utf8');

function setup(mode) {
	const gallery = {};
	const links = [
		{ gallery, title: '<img src=x onerror=alert(1)>', href: '/one.jpg', dataset: {} },
		{ gallery, title: 'Two', href: '/two.jpg', dataset: {} },
		{ gallery: null, title: 'Inline', href: '/three.jpg', dataset: { jumtGroup: 'inline' } },
	];
	const containers = [];
	let initialized;
	links.forEach((link) => {
		link.closest = () => link.gallery;
		link.addEventListener = (name, handler) => { link.handler = handler; };
	});

	const document = {
		currentScript: { dataset: { jumtMode: mode } },
		readyState: 'complete',
		querySelectorAll: (selector) => selector.startsWith('a[data-jumt-lightbox') ? links : [],
		createElement: (tag) => {
			if (tag === 'span') {
				return {
					set textContent(value) {
						this.innerHTML = value.replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;');
					},
				};
			}
			if (tag === 'div') return { children: [], append(child) { this.children.push(child); } };
			return { dataset: {}, click() { this.clicked = true; } };
		},
		body: { append: (element) => containers.push(element) },
	};
	const window = {
		Zoomora: { bind: (selector) => { initialized = selector; } },
		GLightbox: (options) => { initialized = options; },
		baguetteBox: { run: (selector, options) => { initialized = { selector, options }; } },
	};
	vm.runInNewContext(source, { document, window });
	return { links, containers, initialized };
}

test('GLightbox separates galleries, escapes captions and forces image mode', () => {
	const { links, initialized } = setup('glightbox');
	assert.equal(links[0].dataset.gallery, links[1].dataset.gallery);
	assert.notEqual(links[0].dataset.gallery, links[2].dataset.gallery);
	assert.equal(links[0].dataset.title, '&lt;img src=x onerror=alert(1)&gt;');
	assert.equal(links[0].dataset.type, 'image');
	assert.equal(initialized.selector, 'a[data-jumt-lightbox="glightbox"]');
});

test('Zoomora binds grouped original links', () => {
	const { links, initialized } = setup('zoomora');
	assert.equal(links[0].dataset.gallery, links[1].dataset.gallery);
	assert.notEqual(links[0].dataset.gallery, links[2].dataset.gallery);
	assert.equal(initialized, 'a[data-jumt-lightbox="zoomora"]');
});

test('baguetteBox opens an isolated copy of the selected gallery', () => {
	const { links, containers, initialized } = setup('baguettebox');
	assert.equal(containers.length, 2);
	assert.equal(containers[0].children.length, 2);
	assert.equal(containers[0].children[0].dataset.caption, '&lt;img src=x onerror=alert(1)&gt;');
	assert.equal(initialized.selector, '.jumt-baguette-group');
	let prevented = false;
	links[1].handler({ preventDefault() { prevented = true; } });
	assert.equal(prevented, true);
	assert.equal(containers[0].children[1].clicked, true);
});
