(function (window, document) {
    'use strict';

    var defaults = [
        { emoji: '🎈', left: '8%', size: '36px', delay: '.1s', duration: '5.6s', drift: '38px', driftEnd: '-38px' },
        { emoji: '✨', left: '18%', size: '28px', delay: '.6s', duration: '5.2s', drift: '-28px', driftEnd: '28px' },
        { emoji: '🎉', left: '31%', size: '42px', delay: '.3s', duration: '6s', drift: '44px', driftEnd: '-44px' },
        { emoji: '🎈', left: '47%', size: '30px', delay: '.9s', duration: '5.5s', drift: '-36px', driftEnd: '36px' },
        { emoji: '⭐', left: '62%', size: '38px', delay: '.2s', duration: '5.8s', drift: '30px', driftEnd: '-30px' },
        { emoji: '🎊', left: '74%', size: '34px', delay: '.7s', duration: '5.4s', drift: '-42px', driftEnd: '42px' },
        { emoji: '🎈', left: '88%', size: '40px', delay: '.4s', duration: '6.1s', drift: '34px', driftEnd: '-34px' }
    ];

    function seconds(value) {
        var number = parseFloat(String(value || '0').replace('s', ''));
        return isNaN(number) ? 0 : number;
    }

    function balloons(options) {
        options = options || {};
        var items = options.items || defaults;
        var layer = document.createElement('div');
        var maxLife = 0;

        layer.className = 'bhs-reward-balloon-layer';
        layer.setAttribute('aria-hidden', 'true');

        items.forEach(function (item) {
            var el = document.createElement('span');
            el.textContent = item.emoji || '🎈';
            el.style.setProperty('--left', item.left || '50%');
            el.style.setProperty('--size', item.size || '36px');
            el.style.setProperty('--delay', item.delay || '0s');
            el.style.setProperty('--duration', item.duration || '5.5s');
            el.style.setProperty('--drift', item.drift || '32px');
            el.style.setProperty('--drift-end', item.driftEnd || '-32px');
            layer.appendChild(el);
            maxLife = Math.max(maxLife, seconds(item.delay) + seconds(item.duration));
        });

        document.body.appendChild(layer);
        window.setTimeout(function () {
            if (layer.parentNode) {
                layer.parentNode.removeChild(layer);
            }
        }, Math.ceil((maxLife + 0.6) * 1000));
    }

    function autoPlay() {
        var triggers = document.querySelectorAll('[data-reward-effect="balloons"]');
        Array.prototype.forEach.call(triggers, function (trigger) {
            if (trigger.getAttribute('data-reward-played') === '1') {
                return;
            }
            trigger.setAttribute('data-reward-played', '1');
            balloons();
        });

        var popups = document.querySelectorAll('[data-reward-popup="modal"]');
        Array.prototype.forEach.call(popups, function (popup) {
            if (popup.getAttribute('data-reward-popup-played') === '1') {
                return;
            }
            popup.setAttribute('data-reward-popup-played', '1');
            if (window.bootstrap && window.bootstrap.Modal) {
                window.bootstrap.Modal.getOrCreateInstance(popup).show();
            }
        });
    }

    window.BHSkinRewardEffects = window.BHSkinRewardEffects || {};
    window.BHSkinRewardEffects.balloons = balloons;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', autoPlay);
    } else {
        autoPlay();
    }
})(window, document);
