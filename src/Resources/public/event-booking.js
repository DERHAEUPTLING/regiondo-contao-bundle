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
                Regiondo.dispatchEvent(window.Regiondo.events.bookingUpdated);
            }
        }
    }

    /**
     * Send the form request
     * @param {{}} form
     * @param {{}} container
     * @param {{}} config
     * @param {Boolean} isRefresh
     */
    function sendFormRequest(form, container, config, isRefresh) {
        window.Regiondo.createRequest({
            method: 'POST',
            url: config.url + (isRefresh ? '?refresh=1' : ''),
            container: container,
            success: getRequestSuccessCallback(container, config, !isRefresh),
        }).send(new FormData(form));
    }

    /**
     * Initialize the form
     * @param {Element} container
     * @param {{}} config
     */
    function initForm(container, config) {
        var form = container.querySelector('form');

        form.querySelector('select[data-regiondo-event]').addEventListener('change', function () {
            sendFormRequest(form, container, config, true);
        });

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            sendFormRequest(form, container, config);
        });
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

    // Initialize all products
    Array.prototype.slice.call(document.querySelectorAll('[data-regiondo-product]')).forEach(function (container) {
        var config = JSON.parse(container.dataset.regiondoProduct);

        document.addEventListener(window.Regiondo.events.cartUpdated, function () {
            loadForm(container, config);
        });

        loadForm(container, config);
    });
});
