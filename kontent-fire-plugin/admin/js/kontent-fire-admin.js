/**
 * Admin JavaScript for Kontent Fire
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Platform connection handler
        $('.connect-platform').on('click', function() {
            var platform = $(this).data('platform');
            // Platform connection logic to be implemented
            alert('Connect to ' + platform + ' - OAuth flow to be implemented');
        });
    });

})(jQuery);
