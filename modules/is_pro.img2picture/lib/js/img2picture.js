document.addEventListener('DOMContentLoaded', function() {

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
        imgformat[i].img.onload = function(event) {
            event = event || window.event;
            var el = event.target || event.srcElement;
            imgformat[el.id].support = (el.width > 0 && el.height > 0);
        };
        imgformat[i].img.onerror = function(event) {
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


    function InitI2Plazyload() {
        let elements = document.querySelectorAll('*[data-i2p]:not(.i2p)');

        setTimeout(function() {
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
            load: function load(element) {
                element.classList.add('i2p');
                var isIE = typeof document !== 'undefined' && document.documentMode;
                if (element.nodeName.toLowerCase() === 'picture') {
                    var img = element.querySelector('img');
                    var append = false;

                    if (img === null) {
                        img = document.createElement('img');
                        append = true;
                    }

                    if (isIE && element.getAttribute('data-iesrc')) {
                        img.src = element.getAttribute('data-iesrc');
                    }

                    if (element.getAttribute('data-alt')) {
                        img.alt = element.getAttribute('data-alt');
                    }

                    if (append) {
                        element.append(img);
                    }
                }

                if (element.nodeName.toLowerCase() === 'video' && !element.getAttribute('data-src')) {
                    if (element.children) {
                        var childs = element.children;
                        var childSrc = void 0;
                        for (var i = 0; i <= childs.length - 1; i++) {
                            childSrc = childs[i].getAttribute('data-src');
                            if (childSrc) {
                                childs[i].src = childSrc;
                            }
                        }

                        element.load();
                    }
                }

                if (element.getAttribute('data-poster')) {
                    element.poster = element.getAttribute('data-poster');
                }

                if (element.getAttribute('data-src')) {
                    element.src = element.getAttribute('data-src');
                }

                if (element.getAttribute('data-srcset')) {
                    element.setAttribute('srcset', element.getAttribute('data-srcset'));
                }

                var backgroundImageDelimiter = ',';
                if (element.getAttribute('data-background-delimiter')) {
                    backgroundImageDelimiter = element.getAttribute('data-background-delimiter');
                }

                if (element.getAttribute('data-background-image')) {
                    element.style.backgroundImage = 'url(\'' + element.getAttribute('data-background-image').split(backgroundImageDelimiter).join('\'),url(\'') + '\')';
                } else if (element.getAttribute('data-background-image-set')) {
                    var imageSetLinks = element.getAttribute('data-background-image-set').split(backgroundImageDelimiter);
                    var firstUrlLink = imageSetLinks[0].substr(0, imageSetLinks[0].indexOf(' ')) || imageSetLinks[0]; // Substring before ... 1x
                    firstUrlLink = firstUrlLink.indexOf('url(') === -1 ? 'url(' + firstUrlLink + ')' : firstUrlLink;
                    if (imageSetLinks.length === 1) {
                        element.style.backgroundImage = firstUrlLink;
                    } else {
                        element.setAttribute('style', (element.getAttribute('style') || '') + ('background-image: ' + firstUrlLink + '; background-image: -webkit-image-set(' + imageSetLinks + '); background-image: image-set(' + imageSetLinks + ')'));
                    }
                }

                if (element.getAttribute('data-toggle-class')) {
                    element.classList.toggle(element.getAttribute('data-toggle-class'));
                }
            },
            loaded: function(el) {
                el.classList.add('loaded');
                if ((el.nodeName.toLowerCase() === 'img') &&
                    (el.parentNode.nodeName.toLowerCase() === 'picture')) {
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