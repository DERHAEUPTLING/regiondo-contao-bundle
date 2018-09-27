document.addEventListener('DOMContentLoaded', function () {
    Array.prototype.slice.call(document.querySelectorAll('[data-regiondo-booking-iframe]')).forEach(function (container) {
        var config = JSON.parse(container.dataset.regiondoBookingIframe);

        var iframe = document.createElement('iframe');
        iframe.id = 'regiondo-booking-widget';
        iframe.dataset.url = config.url;
        iframe.dataset.title = config.title;
        iframe.dataset.width = config.width + 'px';
        iframe.dataset.checkout = 'lightbox';
        iframe.style = 'border:0;background:transparent;';

        var matches = /^#regiondo-(\d{4}-\d{2}-\d{2})(-\d{2}:\d{2})?$/.exec(window.location.hash);

        // Add the date and optionally time to the URL
        if (matches !== null) {
            if (matches[2]) {
                iframe.dataset.url += '/type/ticket/date/' + matches[1] + '/time/' + matches[2].substring(1);
            } else {
                iframe.dataset.url += '/type/ticket/date/' + matches[1];
            }
        }

        var parent = container.parentNode;
        parent.replaceChild(iframe, container);

        // Register the script URL if it's not there yet (cart module has a priority so do not override it)
        if (!window.RegiondoBookingScript) {
            window.RegiondoBookingScript = { src: config.script };
        }
    });
});
