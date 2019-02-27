define([
    'jquery'
], function ($) {
    "use strict";

    return function () {
        $.validator.addMethod(
            'validate-classifier',
            function (value) {
                if (value == null) {
                    return false;
                }
                var pass = $.trim(value);
                /*strip leading and trailing spaces*/
                if (0 === pass.length) {
                    return true;
                }
                if (!(/[a-z]/i.test(value)) || !(/[0-9]/.test(value))) {
                    return false;
                }
                if (pass.length < 7) {
                    return false;
                }
                return true;
            },
            $.mage.__('Project code must contain only letters and numbers and be 8 characters or more.')
        );
    }
});
define([
    'jquery'
], function ($) {
    "use strict";

    return function () {
        $.validator.addMethod(
            'validate-email-list',
            function (value) {
                if ($.mage.isEmpty(value)) {
                    return true;
                }
                var valid_regexp = /^[a-z0-9\._-]{1,30}@([a-z0-9_-]{1,30}\.){1,5}[a-z]{2,4}$/i,
                    emails = value.split(/[\s\n\,]+/g);
                for (var i = 0; i < emails.length; i++) {
                    if (!valid_regexp.test(emails[i].trim())) {
                        return false;
                    }
                }
                return true;
            },
            $.mage.__('Field must be a comma separated list of emails.')
        );
    }
});
