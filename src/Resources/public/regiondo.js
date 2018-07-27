window.addEventListener('load', function () {
    if (!window.RegiondoBookingScript) {
        return;
    }

    var script = document.createElement('script');
    script.id = 'regiondo-booking-js';
    script.src = window.RegiondoBookingScript.src;
    script.async = true;
    script.defer = true;

    if (window.RegiondoBookingScript.data) {
        for (var key in window.RegiondoBookingScript.data) {
            script.dataset[key] = window.RegiondoBookingScript.data[key];
        }
    }

    document.head.appendChild(script);
});
