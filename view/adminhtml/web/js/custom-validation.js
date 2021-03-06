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
                var multiple = pass.split(",");
                for(var i=0; i < multiple.length; i++){
                    for(var j=0; j < multiple.length; j++){
                        if(i != j && $.trim(multiple[i]) == $.trim(multiple[j])) {
                            return false;
                        }
                    }
                }
                /*strip leading and trailing spaces*/
                for(var i=0; i < multiple.length; i++) {
                    pass = $.trim(multiple[i]);
                    if (0 === pass.length) {
                        return true;
                    }
                    if (!(/[a-z]/i.test(pass)) || !(/[0-9]/.test(pass))) {
                        return false;
                    }
                    if (pass.length != 9 && pass.length != 10) {
                        return false;
                    }
                }
                return true;
            },
            $.mage.__('Project code must contain only letters and numbers and be 9 or 10 characters. No duplicates are allowed either.')
        );
        $.validator.addMethod(
            'validate-email-list',
            function (value) {
                var validRegexp, emails, i;

                if ($.mage.isEmpty(value)) {
                    return true;
                }
                if ((value.indexOf(' ') > -1) && (value.indexOf(',') <= -1)){
                    return false;
                }
                validRegexp = /^[a-z0-9\._-]{1,30}@([a-z0-9_-]{1,30}\.){1,5}[a-z]{2,4}$/i;
                emails = value.split(/[\s\n\,]+/g);

                for (i = 0; i < emails.length; i++) {
                    if (!validRegexp.test(emails[i].strip())) {
                        return false;
                    }
                }

                return true;
            },
            $.mage.__('Please enter valid email addresses, separated by commas. For example, johndoe@domain.com, johnsmith@domain.com.')//eslint-disable-line max-len
        );
    }
});
