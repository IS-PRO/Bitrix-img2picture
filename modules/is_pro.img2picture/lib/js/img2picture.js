document.addEventListener('DOMContentLoaded', function () {

	let webpEnable = false;
	let avifEnable = false;
	let imgformat = {
		webplossy: {
			src: 'data:image/webp;base64,UklGRiIAAABXR' +
				'UJQVlA4IBYAAAAwAQCdASoBAAEADsD+JaQAA3AAAAAA',
			support: null
		},
		webplossless: {
			src: 'data:image/webp;base64,UklGRhoAAABXRUJQVlA' +
				'4TA0AAAAvAAAAEAcQERGIiP4HAA==',
			support: null
		},
		avif: {
			src: 'data:image/avif;base64,AAAAIGZ0eXBhdmlmAAAAAGF2aWZtaWYxbWlhZk1BMUIAAADybWV0YQAAAAAAAAAoaGRscgAAAAAAAAAAcGljdAAAAAAAAAAAAAAAAGxpYmF2aWYAAAAADnBpdG0AAAAAAAEAAAAeaWxvYwAAAABEAAABAAEAAAABAAABGgAAAB0AAAAoaWluZgAAAAAAAQAAABppbmZlAgAAAAABAABhdjAxQ29sb3IAAAAAamlwcnAAAABLaXBjbwAAABRpc3BlAAAAAAAAAAIAAAACAAAAEHBpeGkAAAAAAwgICAAAAAxhdjFDgQ0MAAAAABNjb2xybmNseAACAAIAAYAAAAAXaXBtYQAAAAAAAAABAAEEAQKDBAAAACVtZGF0EgAKCBgANogQEAwgMg8f8D///8WfhwB8+ErK42A=',
			support: null
		}
	};
	for (var i in imgformat) {
		imgformat[i].img = new Image();
		imgformat[i].img.id = i;
		imgformat[i].img.onload = function (event) {
			event = event || window.event;
			var el = event.target || event.srcElement;
			imgformat[el.id].support = (el.width > 0 && el.height > 0);
		};
		imgformat[i].img.onerror = function (event) {
			event = event || window.event;
			var el = event.target || event.srcElement;
			imgformat[el.id].support = false;
		};
		imgformat[i].img.src = imgformat[i].src;
	};

	const doc = document.querySelector('body');
	const MutationObserver = window.MutationObserver;
	const myObserver = new MutationObserver(InitI2Plazyload);
	const obsConfig = {
		childList: true,
		subtree: true
	};
	myObserver.observe(doc, obsConfig);
	InitI2Plazyload();

	function is_cached(src) {
		var image = new Image();
		image.src = src;
		return image.complete;
	}

	function InitI2Plazyload() {
		let elements = document.querySelectorAll('*[data-i2p]:not(.i2p)');

		elements.forEach(el => {
			if (el.hasAttribute('data-srcset') && is_cached(el.getAttribute('data-srcset'))) {
				el.setAttribute('srcset', el.getAttribute('data-srcset'));
				el.removeAttribute("data-i2p");
				el.classList.add('loaded');
			}
		});
		elements = document.querySelectorAll('*[data-i2p]:not(.i2p)');
		elements.forEach(el => {
			el.classList.add('i2p');
		});

		setTimeout(function () {
			webpEnable = (imgformat.webplossy.support && imgformat.webplossless.support);
			avifEnable = imgformat.avif.support;
			if (webpEnable || avifEnable) {
				elements.forEach(el => {
					if (webpEnable) {
						el.classList.add('webp');
					}
					if (avifEnable) {
						el.classList.add('avif');
					}
				})
			}
		}, 100);

		const observer = lozad(elements, {
			loaded: function (el) {
				el.classList.add('loaded');
				if ((el.nodeName.toLowerCase() === 'img') &&
					(el.parentNode.nodeName.toLowerCase() === 'picture')) {
					const sourses = el.parentNode.querySelectorAll('source.i2p');
					if (sourses) {
						sourses.forEach(source => {
							observer.triggerLoad(source);
						})
					}
				}
			}
		});
		observer.observe();
	}
})