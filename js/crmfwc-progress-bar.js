/**
 * Script progress bar
 *
 * @author ilGhera
 * @package crm-in-cloud-for-wc-premium/js
 *
 * @since 1.3.0
 */
var crmfwcControlBarController = function() {

    var self = this;

    self.onLoad = function() {

        self.progressBar();

    }

    /**
     * The progress bar
     *
     * @param {string} type contacts or products.
     * @param {bool} del export or delete.
     *
     * @return void
     */
    self.progressBar = function( type, del = false  ) {

		jQuery(function($){

            var i = 0;

            if ( 'products' === type ) {
                var totalActions     = del ? 'get-total-products-delete-actions' : 'get-total-products-actions';
                var scheduledActions = del ? 'get-scheduled-products-delete-actions' : 'get-scheduled-products-actions';
                var runningMessage   = del ? options.productsDeleteRunning : options.productsExportRunning;
                var completedMessage = del ? options.productsDeleteCompleted : options.productsExportCompleted;
            } else {
                var totalActions     = del ? 'get-total-contacts-delete-actions' : 'get-total-contacts-actions';
                var scheduledActions = del ? 'get-scheduled-contacts-delete-actions' : 'get-scheduled-contacts-actions';
                var runningMessage   = del ? options.contactsDeleteRunning : options.contactsExportRunning;
                var completedMessage = del ? options.contactsDeleteCompleted : options.contactsExportCompleted;
            }

            var data = {
                'action': totalActions,
            };

            $.post(ajaxurl, data, function(response){

                var totActions = response;
                console.log( 'TOT. ITEMS', totActions );

                if ( totActions > 0 ) {

                    console.log('RUNNING!');

                    // Running message
                    $('.crmfwc-progress-bar-text').html( runningMessage );
                    $('.crmfwc-progress-bar-text span').html( totActions );
                    
                    // Display the progress bar
                    $('.ilghera-notice-warning.crmfwc-export').show('slow');

                    var run = 0;
                    var width = 0;
                    var data2, currentWidth, diff;
                    var updateData = setInterval( function(){

                        data2 = {
                            'action': scheduledActions
                        }

                        $.post(ajaxurl, data2, function(resp){

                            if ( resp == totActions ) {
                                run = 1;
                            }

                            if ( resp > 0 ) {

                                diff         = totActions - resp;
                                currentWidth = ( diff / totActions ) * 100;
                                // var id = setInterval(frame, 30);

                            } else {

                                run = 1;
                                clearInterval( updateData );
                                currentWidth = 100;

                            }

                            // Running message
                            $('.crmfwc-progress-bar-text span').html( resp );

                            console.log( 'ITEMS LEFT', resp );
                            console.log( 'TOT. ITEMS', totActions );
                            console.log( 'PERCENTAGE', currentWidth );

                            if ( 1 == run ) {

                                $('#crmfwc-progress').css( 'width', currentWidth + '%' );
                                $('#crmfwc-progress-bar span').html( Math.ceil( currentWidth ) + '%' );

                                if ( resp == 0) {

                                    // Completed message
                                    $('.crmfwc-progress-bar-text').html( completedMessage );
                                    $('.crmfwc-progress-bar-text span').html( totActions );

                                    run = 0;

                                }

                            }

                        })

                    }, 500 );

                }

            })

        })

    }


    /**
     * Display the progress bar on products export
     *
     * @return void
     */
    self.exportProducts = function() {

		jQuery(function($){

            $('.crmfwc.export-products').on('click', function(){

                setTimeout(function(){
                    self.progressBar( 'products' );
                }, 800)

            })

        })

    }


    /**
     * Display the progress bar on products delete 
     *
     * @return void
     */
    self.deleteProducts = function() {

		jQuery(function($){

            $('.crmfwc.red.users').on('click', function(){

                console.log( 'DELETE PRODUCTS!' );

                setTimeout(function(){
                    self.progressBar( 'products', true );
                }, 800)

            })

        })

    }


    /**
     * Display the progress bar on contacts export
     *
     * @return void
     */
    self.exportContacts = function() {

		jQuery(function($){

            $('.crmfwc.export-users').on('click', function(){

                setTimeout(function(){
                    self.progressBar( 'contacts' );
                }, 800)

            })

        })

    }


    /**
     * Display the progress bar on contacts delete 
     *
     * @return void
     */
    self.deleteContacts = function() {

		jQuery(function($){

            $('.crmfwc.red.users').on('click', function(){

                console.log( 'DELETE USERS!' );

                setTimeout(function(){
                    self.progressBar( 'contacts', true );
                }, 800)

            })

        })

    }

}

/**
 * Class starter with onLoad method
 */
jQuery(document).ready(function($) {
	
	var Controller = new crmfwcControlBarController;
	Controller.onLoad();

});

