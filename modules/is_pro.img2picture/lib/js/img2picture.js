document.addEventListener('DOMContentLoaded', function () {

	let webpEnable = false;
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

	const doc = document.querySelector('body');
    const MutationObserver    = window.MutationObserver;
    const myObserver = new MutationObserver(InitI2Plazyload);
    const obsConfig = { childList: true, subtree: true };
    myObserver.observe (doc, obsConfig);
    InitI2Plazyload();


	function InitI2Plazyload() {
		const elements = document.querySelectorAll('*[data-i2p]:not(.i2p)');

		elements.forEach(el => {
			el.classList.add('i2p');
		});

		setTimeout(function() {
			webpEnable = (webp.lossy.support && webp.lossless.support);

			if (webpEnable) {
				elements.forEach(el => {
					el.classList.add('webp')
				})
			}
		}, 500);

		const observer = lozad(elements, {
			loaded: function(el) {
				el.classList.add('loaded');
				if ((el.nodeName.toLowerCase() === 'img') &&
					(el.parentNode.nodeName.toLowerCase() === 'picture'))
				{
					const sourses = el.parentNode.querySelectorAll('source');
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

