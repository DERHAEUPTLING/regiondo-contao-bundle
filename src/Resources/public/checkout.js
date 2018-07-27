document.addEventListener('DOMContentLoaded', function () {
    /**
     * Get the request success callback
     * @param {Element} container
     * @param {{}} config
     * @return Function
     */
    function getRequestSuccessCallback(container, config) {
        return function (response) {
            container.innerHTML = response.buffer;
            initForm(container, config);
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
            success: getRequestSuccessCallback(container, config),
        }).send(new FormData(form));
    }

    /**
     * Initialize the form
     * @param {Element} container
     * @param {{}} config
     */
    function initForm(container, config) {
        var form = container.querySelector('form');

        if (form) {
            Array.prototype.slice.call(document.querySelectorAll('[data-regiondo-option]')).forEach(function (select) {
                select.addEventListener('change', function () {
                    sendFormRequest(form, container, config, true);
                });
            });

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                sendFormRequest(form, container, config);
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

    // Initialize all checkouts
    Array.prototype.slice.call(document.querySelectorAll('[data-regiondo-checkout]')).forEach(function (container) {
        loadForm(container, JSON.parse(container.dataset.regiondoCheckout));
    });
});
