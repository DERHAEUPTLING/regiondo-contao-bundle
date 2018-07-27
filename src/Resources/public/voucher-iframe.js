document.addEventListener('DOMContentLoaded', function () {
    Array.prototype.slice.call(document.querySelectorAll('[data-regiondo-voucher-iframe]')).forEach(function (container) {
        var config = JSON.parse(container.dataset.regiondoVoucherIframe);

        var iframe = document.createElement('iframe');
        iframe.id = 'regiondo-booking-widget';
        iframe.dataset.url = config.url;
        iframe.dataset.title = config.title;
        iframe.dataset.width = config.width + 'px';
        iframe.dataset.checkout = 'lightbox';
        iframe.style = 'border:0;background:transparent;';

        var parent = container.parentNode;
        parent.replaceChild(iframe, container);

        // Register the script URL if it's not there yet (cart module has a priority so do not override it)
        if (!window.RegiondoBookingScript) {
            window.RegiondoBookingScript = { src: config.script };
        }
    });
});
