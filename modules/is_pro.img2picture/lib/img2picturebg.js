
class Cimg2picture
{
	constructor(options) {

		this.options = options;
		this.options.webp = false;

	}

	init() {

		this.canIuseWebp();
		const thisClass = this;

		setTimeout(function () {
			const doc = document.querySelector('body');
			const MutationObserver = window.MutationObserver;
			const myObserver = new MutationObserver(img2picture_setBackground);
			const obsConfig = { attributes: true, subtree: true };

			myObserver.observe(doc, obsConfig);

			thisClass.setBackground();

			window.addEventListener('scroll', function () {
				thisClass.setBackground();
			});
			window.addEventListener('resize', function () {
				thisClass.setBackground();
			});
		}, 600);
	}

	canIuseWebp()
	{
		const thisClass = this;
		thisClass.options.webp = false;
		let webp = {
			lossy: {
				src: 'data:image/webp;base64,UklGRiIAAABXR'+
					 'UJQVlA4IBYAAAAwAQCdASoBAAEADsD+JaQAA3AAAAAA',
				support: null
			},
			lossless: {
				src: 'data:image/webp;base64,UklGRhoAAABXRUJQVlA'+
					 '4TA0AAAAvAAAAEAcQERGIiP4HAA==',
				support: null
			}
		};
		for (var i in webp) {
			webp[i].img=new Image();
			webp[i].img.id=i;
			webp[i].img.onload=function(event) {
				event=event || window.event;
				var el=event.target || event.srcElement;
				webp[el.id].support=(el.width>0 && el.height>0);
			};
			webp[i].img.onerror=function(event) {
				event=event || window.event;
				var el=event.target || event.srcElement;
				webp[el.id].support=false;
			};
			webp[i].img.src=webp[i].src;
		};
		setTimeout(function() {
			thisClass.options.webp = (webp.lossy.support && webp.lossless.support);
		}, 500);
	}

	setBackground()
	{
		const thisClass = this;
		const windowHeight = (window.innerHeight || document.documentElement.clientHeight);
		const windowWidth = (window.innerWidth || document.documentElement.clientWidth);
		const top = (windowHeight * -2);
		const bottom = (windowHeight * 3);
		let needWidth = 'original';
		thisClass.options.RESPONSIVE_VALUE.forEach(function (el, ind) {
			const min = 0 + parseInt(el.min);
			const max = 0 + parseInt(el.max);
			if ((windowWidth >= min) && (windowWidth <= max)) {
				needWidth = el.width;
			}
		});
		let elements = document.querySelectorAll('*[data-img2picture-background]');
		elements.forEach(function (element, index) {
			const imgRect = element.getBoundingClientRect();
			if ((top < imgRect.top) && (bottom > imgRect.top)) {
				const background_text = element.getAttribute('data-img2picture-background');
				const backgrounds = JSON.parse(background_text);
				let background_url = '';
				if (backgrounds.FILES[needWidth].length > 0) {
					if ((thisClass.options.webp) && (typeof backgrounds.FILES[needWidth].webp != 'undefined')) {
						background_url = backgrounds.FILES[needWidth].webp;
					} else {
						background_url = backgrounds.FILES[needWidth].src;
					}
				} else {
					if ((thisClass.options.webp) && (typeof backgrounds.FILES['original'].webp != 'undefined')) {
						background_url = backgrounds.FILES['original'].webp;
						if (!background_url) {
							background_url = backgrounds.FILES['original'].src;
						}
					} else {
						background_url = backgrounds.FILES['original'].src;
					}
				}
				element.style.backgroundImage = 'url(' + background_url + ')';
			}
		})
	};

}

function img2picture_setBackground() {
	if (typeof img2picture != 'undefined') {
		img2picture.setBackground();
	}
}