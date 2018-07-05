define([], function () {
    'use strict';
    return function (Filters) {
        return Filters.extend({
            apply: function () {
                var prevStoreId = this.applied.store_id;
                this._super();
                var newStoreId = this.filters.store_id;
                if (prevStoreId !== newStoreId) {
                    if (!newStoreId) {
                        newStoreId = '0';
                    }
                    window.newStoreId = newStoreId;
                }
                return this;
            }
        });
    };
});

