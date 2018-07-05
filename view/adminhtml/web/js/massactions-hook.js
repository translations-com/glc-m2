define([
    'jquery',
    'Magento_Ui/js/modal/confirm',
    'Magento_Ui/js/modal/alert'
], function ($, confirm, alert) {
    'use strict';
    return function (Massactions) {
        return Massactions.extend({
            applyAction: function (actionIndex) {

                var t = this;
                var _super = this._super;

                if (actionIndex=='submission') {
                    var alertMessage = 'Fields are not configured under Globallink > Field Configuration';
                    var requestConfig = {
                        method: 'POST',
                        url: '/admin/translations/system_config/CheckFields',
                        data: {
                            'form_key': window.FORM_KEY
                        }
                    };
                    var massactionUrl = this.getAction(actionIndex).url;
                    var massActionVal = massactionUrl.search(/submission_cms_page/i);
                    //Code removed 11/8/2017 Justin Griffin
                    //This block was interfering with the submission
                    //page and causing it to throw an Ajax error
                    //It is still unknown whether pages and blocks will go through correctly
                    
                    /*if (massactionUrl.search(/submission_cms_block/i) != -1) {
                        alertMessage += ' > CMS Blocks';
                        requestConfig.data.entityTypeId = 13; // \TransPerfect\GlobalLink\Helper\Data::CMS_BLOCK_TYPE_ID
                    } else if (massactionUrl.search(/submission_cms_page/i) != -1) {
                        alertMessage += ' > CMS Pages';
                        requestConfig.data.entityTypeId = 12; // \TransPerfect\GlobalLink\Helper\Data::CMS_PAGE_TYPE_ID
                    }*/
                    if (requestConfig.data.entityTypeId) {
                        $.ajax(requestConfig)
                            .done(function (response) {
                                if (!response.success) {
                                    alert({
                                        content: response.errorMessage
                                    });
                                    return;
                                }
                                if (!response.fieldsCount) {
                                    alert({
                                        content: alertMessage
                                    });
                                    return;
                                }

                                _super.call(t, actionIndex);

                                var action = t.getAction(actionIndex);
                                if (window.newStoreId !== undefined) {
                                    var newUrl = action.url.replace(/\/store\/\d+\//g,"/store/"+window.newStoreId+"/");
                                    action.url = newUrl;
                                }

                                return t;
                            })
                            .fail(function (jqXHR, textStatus) {
                                
                                alert({
                                    content: 'Ajax call failed: ' + jqXHR.responseText
                                });
                            });
                    } else {
                        _super.call(t, actionIndex);

                        var action = t.getAction(actionIndex);
                        if (window.newStoreId !== undefined) {
                            var newUrl = action.url.replace(/\/store\/\d+\//g,"/store/"+window.newStoreId+"/");
                            action.url = newUrl;
                        }

                        return t;
                    }
                } else {
                    t._super();
                    return t;
                }
            }
        });
    };
});

