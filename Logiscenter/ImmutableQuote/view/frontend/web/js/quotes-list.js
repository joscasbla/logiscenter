/**
 * Immutable Quotes List JavaScript
 */
define([
    'jquery',
    'Magento_Ui/js/modal/confirm',
    'Magento_Ui/js/modal/alert',
    'mage/translate',
    'jquery/ui'
], function ($, confirmation, alert, $t) {
    'use strict';

    $.widget('logiscenter.quotesList', {
        options: {
            enableUrl: '',
            enableButtonSelector: '.enable-quote',
            loadingClass: 'loading',
            disabledClass: 'disabled'
        },

        _create: function () {
            this._bindEvents();
        },

        _bindEvents: function () {
            var self = this;
            
            this.element.on('click', this.options.enableButtonSelector, function (event) {
                event.preventDefault();
                var $button = $(this);
                var quoteId = $button.data('quote-id');
                
                if (!quoteId) {
                    alert({
                        title: $t('Error'),
                        content: $t('Invalid quote ID.')
                    });
                    return;
                }

                self._showEnableConfirmation(quoteId, $button);
            });
        },

        _showEnableConfirmation: function (quoteId, $button) {
            var self = this;
            
            confirmation({
                title: $t('Enable Quote'),
                content: $t('Are you sure you want to enable this quote? This will disable any other active quotes you have.'),
                actions: {
                    confirm: function () {
                        self._enableQuote(quoteId, $button);
                    }
                }
            });
        },

        _enableQuote: function (quoteId, $button) {
            var self = this;
            
            // Show loading state
            $button.addClass(this.options.loadingClass + ' ' + this.options.disabledClass);
            $button.prop('disabled', true);
            $button.find('span').text($t('Enabling...'));

            $.ajax({
                url: this.options.enableUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    quote_id: quoteId,
                    form_key: $('input[name="form_key"]').val()
                },
                success: function (response) {
                    if (response.success) {
                        self._handleEnableSuccess(response, $button);
                    } else {
                        self._handleEnableError(response.message || $t('Failed to enable quote.'), $button);
                    }
                },
                error: function (xhr, status, error) {
                    var message = $t('An error occurred while enabling the quote. Please try again.');
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    self._handleEnableError(message, $button);
                }
            });
        },

        _handleEnableSuccess: function (response, $button) {
            // Show success message
            alert({
                title: $t('Success'),
                content: response.message || $t('Quote enabled successfully.'),
                actions: {
                    always: function () {
                        // Reload page to show updated status
                        window.location.reload();
                    }
                }
            });
        },

        _handleEnableError: function (message, $button) {
            // Remove loading state
            $button.removeClass(this.options.loadingClass + ' ' + this.options.disabledClass);
            $button.prop('disabled', false);
            $button.find('span').text($t('Enable'));

            // Show error message
            alert({
                title: $t('Error'),
                content: message
            });
        }
    });

    return $.logiscenter.quotesList;
});
