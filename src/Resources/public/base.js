window.Regiondo = {
    /**
     * Configuration
     */
    config: {
        ajaxLoadingClass: 'regiondo-ajax-loading',
        localStorageKey: 'regiondo-bookings',
    },

    /**
     * Events
     */
    events: {
        bookingUpdated: 'RegiondoBookingUpdated',
        cartUpdated: 'RegiondoCartUpdated',
    },

    /**
     * Get the storage
     * @return {{}}
     */
    getStorage: function () {
        var storage = JSON.parse(window.localStorage.getItem(this.config.localStorageKey)) || [];

        if (storage.length > 0) {
            var now = Math.floor(Date.now() / 1000);

            storage = storage.filter(function (item) {
                return item.ttl > now;
            });
        }

        window.localStorage.setItem(this.config.localStorageKey, JSON.stringify(storage));

        return storage;
    },

    /**
     * Dispatch the event
     * @param {String} name
     */
    dispatchEvent: function (name) {
        if (typeof(window.Event) === 'function') {
            var event = new window.Event(name);
        } else {
            var event = document.createEvent('Event');
            event.initEvent(name, true, true);
        }

        document.dispatchEvent(event);
    },

    /**
     * Create an AJAX request
     * @param {{}} settings
     * @return XMLHttpRequest
     */
    createRequest: function(settings) {
        var request = new window.XMLHttpRequest();
        var storage = this.getStorage();

        request.open(settings.method, settings.url, true);
        request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        request.setRequestHeader('X-Regiondo-Bookings', JSON.stringify(storage));

        request.onloadstart = function () {
            if (settings.container) {
                settings.container.classList.add(this.config.ajaxLoadingClass);
            }
        }.bind(this);

        request.onloadend = function () {
            if (settings.container) {
                settings.container.classList.remove(this.config.ajaxLoadingClass);
            }
        }.bind(this);

        request.onload = function () {
            if (request.status >= 200 && request.status < 400) {
                var response = JSON.parse(request.responseText);

                // Automatically update the reservations
                if (response.bookings) {
                    window.localStorage.setItem(this.config.localStorageKey, JSON.stringify(response.bookings));
                }

                if (typeof settings.success === 'function') {
                    settings.success(response);
                }
            } else {
                console.error(request);
            }
        }.bind(this);

        return request;
    }
};
