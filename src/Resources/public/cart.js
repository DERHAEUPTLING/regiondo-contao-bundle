document.addEventListener('DOMContentLoaded', function () {
    /**
     * Get the request success callback
     * @param {Element} container
     * @param {{}} config
     * @param {Boolean} dispatchEvent
     * @return Function
     */
    function getRequestSuccessCallback(container, config, dispatchEvent) {
        return function (response) {
            container.innerHTML = response.buffer;
            initForm(container, config);

            if (dispatchEvent) {
                Regiondo.dispatchEvent(window.Regiondo.events.cartUpdated);
            }
        }
    }

    /**
     * Initialize the form
     * @param {Element} container
     * @param {{}} config
     */
    function initForm(container, config) {
        var form = container.querySelector('form');

        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();

                window.Regiondo.createRequest({
                    method: 'POST',
                    url: config.url,
                    container: container,
                    success: getRequestSuccessCallback(container, config, true),
                }).send(new FormData(form));
            });
        }
    }

    /**
     * Load the form
     * @param {Element} container
     * @param {{}} config
     */
    function loadForm(container, config) {
        window.Regiondo.createRequest({
            method: 'GET',
            url: config.url,
            container: container,
            success: getRequestSuccessCallback(container, config),
        }).send();
    }

    // Initialize all carts
    Array.prototype.slice.call(document.querySelectorAll('[data-regiondo-cart]')).forEach(function (container) {
        var config = JSON.parse(container.dataset.regiondoCart);

        document.addEventListener(window.Regiondo.events.bookingUpdated, function () {
            loadForm(container, config);
        });

        loadForm(container, config);
    });
});
