/**
 * JS
 * 
 * @author ilGhera
 * @package crm-in-cloud-for-wc-premium/js
 * @since 1.1.0
 */

var crmfwcController = function() {

	var self = this;

	self.onLoad = function() {
	    self.crmfwc_pagination();
		self.tzCheckbox();
	    self.crmfwc_export_users();
	    self.crmfwc_users_options();
	    self.crmfwc_delete_remote_users();
	    self.crmfwc_export_products();
	    self.crmfwc_delete_remote_products();
		self.crmfwc_disconnect();
        self.crmfwc_check_connection();
		self.chosen();
	}


	/**
	 * Delete the admin messages
	 */
	self.delete_messages = function() {

		jQuery(function($){

			$('.yes, .not', '.crmfwc-message ').html('');

		})

	}


	/**
	 * Tab navigation
	 */
	self.crmfwc_pagination = function() {

		jQuery(function($){

			var contents = $('.crmfwc-admin')
			var url = window.location.href.split("#")[0];
			var hash = window.location.href.split("#")[1];

			if(hash) {
		        contents.hide();		    
			    $('#' + hash).fadeIn(200);		
		        $('h2#crmfwc-admin-menu a.nav-tab-active').removeClass("nav-tab-active");
		        $('h2#crmfwc-admin-menu a').each(function(){
		        	if($(this).data('link') == hash) {
		        		$(this).addClass('nav-tab-active');
		        	}
		        })
		        
		        $('html, body').animate({
		        	scrollTop: 0
		        }, 'slow');
			}

			$("h2#crmfwc-admin-menu a").click(function () {
		        var $this = $(this);
		        
		        contents.hide();
		        $("#" + $this.data("link")).fadeIn(200);

		        self.chosen(true);
		        self.chosen();

		        $('h2#crmfwc-admin-menu a.nav-tab-active').removeClass("nav-tab-active");
		        $this.addClass('nav-tab-active');

		        window.location = url + '#' + $this.data('link');

		        $('html, body').scrollTop(0);

		        /*Delete the admin messages*/
		        self.delete_messages();

		    })

		})
	        	
	}


	/**
	 * Checkboxes
	 */
	self.tzCheckbox = function() {

		jQuery(function($){
			$('input[type=checkbox]').tzCheckbox({labels:['On','Off']});
		});

	}


	/**
	 * Plugin tools available only if connected to CRM in Cloud
	 */
	self.crmfwc_tools_control = function(deactivate = false) {

		jQuery(function($){

			if(deactivate) {

				$('.crmfwc-form').addClass('disconnected');
				$('.crmfwc-form.connection').removeClass('disconnected');

				$('.crmfwc-form input').attr('disabled','disabled');
				$('.crmfwc-form select').attr('disabled','disabled');

				$('.crmfwc-suppliers-groups, .crmfwc-customers-groups').addClass('crmfwc-select');
		        self.chosen(true);

			} else {

				$('.crmfwc-form').removeClass('disconnected');
				$('.crmfwc-form input').removeAttr('disabled');
				$('.crmfwc-form select').removeAttr('disabled');

			}


		})

	}
		

	/**
	 * Check the connection to CRM in Cloud
     *
     * @param string $email the email address.
     * @param string $passw the user password.
     *
     * @return void
	 */
	self.crmfwc_check_connection = function( email, passw ) {

		jQuery(function($){
     
			var data;

            if ( $('body').hasClass('woocommerce_page_crm-in-cloud-for-wc') ) {

                data = {
                    'action': 'check-connection'
                }

                if ( email ) {
                    data.crmfwc_email = email;
                }

                if ( passw ) {
                    data.crmfwc_passw = passw;
                }
                console.log( 'DATA: ' + JSON.stringify( data ) );

                $.post(ajaxurl, data, function(response){

                    var result = JSON.parse(response);

                    if( result && ! result['error'] ) {

                        /*Activate plugin tools*/
                        self.crmfwc_tools_control();
                
                        $('.crmfwc-login-error.alert-danger').hide();
                        $('.check-connection').html(result['ok']);
                        $('.crmfwc-connect').hide();
                        $('.crmfwc-email-field').hide();
                        $('.crmfwc-disconnect').css('display', 'inline-block');
                        $('.crmfwc-disconnect').animate({
                            opacity: 1
                        }, 500);

                    } else {

                        /*Show error message*/
                        if ( result ) {

                            $('.crmfwc-login-error').html( result['error_description'] );
                            $('.crmfwc-login-error').show();

                        }

                        /*Deactivate plugin tools*/
                        self.crmfwc_tools_control(true);

                    }

                })

            }

		})

	}


	/**
	 * Disconnect from CRM in Cloud deleting the Agreement Grant Tocken from the db
	 */
	self.crmfwc_disconnect = function() {

		jQuery(function($){

			$(document).on('click', '.crmfwc-disconnect', function(e){

				e.preventDefault();

				var data = {
					'action': 'crmfwc-disconnect'
				}

				$.post(ajaxurl, data, function(response){
					location.reload();
				})

			})

		})

	}


	/**
	 * Adds a spinning gif to the message box waiting for the response
	 */
	self.crmfwc_response_loading = function() {

		jQuery(function($){

			var container = $('.crmfwc-message .yes');

			$(container).html('<div class="crmfwc-loading"><img></div>');
			$('img', container).attr('src', crmfwcSettings.responseLoading);

		})

	}


	/**
	 * Show a message to the admin
	 * @param  {string} message the text
	 * @param  {bool}   error   different style with true
	 */
	self.crmfwc_response_message = function(message, error = false, update = false) {

		jQuery(function($){

			/*Remove the loading gif*/
			$('.crmfwc-message .yes').html('');

			var container	  = error ? $('.crmfwc-message .not') : $('.crmfwc-message .yes');
			var message_class = error ? 'alert-danger' : 'alert-info';
			var icon		  = error ? 'fa-exclamation-triangle' : 'fa-info-circle';
			
			if ( update ) {

				$(container).append( '<div class="bootstrap-iso"><div class="alert ' + message_class + '"><b><i class="fas ' + icon + '"></i>WC Exporter for CRM in Cloud </b> - ' + message + '</div>' );

			} else {

				$(container).html( '<div class="bootstrap-iso"><div class="alert ' + message_class + '"><b><i class="fas ' + icon + '"></i>WC Exporter for CRM in Cloud </b> - ' + message + '</div>' );

			}

		})

	}


	/**
	 * Export WP users to CRM in Cloud
	 */
	self.crmfwc_export_users = function() {

		jQuery(function($){

			$('.button-primary.crmfwc.export-users').on('click', function(e){

				e.preventDefault();

				self.delete_messages();
				self.crmfwc_response_loading();

		        $('html, body').animate({
		        	scrollTop: $('#crmfwc-admin-menu').offset().top -30
		        }, 'slow');

				var export_company = $('tr.export-company .tzCheckBox').hasClass('checked') ? 1 : 0;
				var export_orders  = $('tr.export-orders .tzCheckBox').hasClass('checked') ? 1 : 0;
				var roles          = $('.crmfwc-contacts-role').val();
				var data           = {
					'action': 'export-users',
					'crmfwc-export-users-nonce': crmfwcSettings.exportUsersNonce,
					'roles': roles,
					'export-company': export_company,
					'export-orders': export_orders
				}

				$.post(ajaxurl, data, function(response){

					var result = JSON.parse(response);

					for (var i = 0; i < result.length; i++) {

						var error = 'error' === result[i][0] ? true : false;
						var update = 0 !== i ? true : false; 

						self.crmfwc_response_message( result[i][1], error, false );

					}

				})
			
			})

		})

	}


	/**
	 * Users synchronization options
	 */
	self.crmfwc_users_options = function() {

		jQuery(function($){

            var syncContacts  = $('.synchronize-contacts');
            var syncCompanies = $('.synchronize-companies');
            var span          = $('span.tzCheckBox', syncContacts);

            // On load
            if ( $(span).hasClass('checked') ) {

                $(syncCompanies).show('slow');

            }

            // On change options
            $(span).on('click', function(){
                
                if ( $(this).hasClass('checked') ) {

                    $(syncCompanies).show('slow');

                } else {

                    $(syncCompanies).hide();

                }

            })

        })

    }


	/**
	 * Delete all the users from CRM in Cloud
	 */
	self.crmfwc_delete_remote_users = function() {

		jQuery(function($){

			$('.button-primary.crmfwc.red.users').on('click', function(e){

				e.preventDefault();

				self.delete_messages();

				var delete_company = $('tr.delete-company .tzCheckBox').hasClass('checked') ? 1 : 0;
				var answer         = confirm( 'Vuoi cancellare tutti i contatti da CRM in Cloud?' ); // temp.

				if ( answer ) {

					self.crmfwc_response_loading();

			        $('html, body').animate({
			        	scrollTop: $('#crmfwc-admin-menu').offset().top -30
			        }, 'slow');
					
					var data = {
						'action': 'delete-remote-users',
						'crmfwc-delete-users-nonce': crmfwcSettings.deleteUsersNonce,
						'delete-company': delete_company
					}


					$.post(ajaxurl, data, function(response){

						var result = JSON.parse(response);

						for (var i = 0; i < result.length; i++) {

							var error = 'error' === result[i][0] ? true : false;
							var update = 0 !== i ? true : false; 

							self.crmfwc_response_message( result[i][1], error, false );
	
						}

					})

				}

			})

		})

	}


	/**
	 * Export WP products to CRM in Cloud
	 */
	self.crmfwc_export_products = function() {

		jQuery(function($){

			$('.button-primary.crmfwc.export-products').on('click', function(e){

				e.preventDefault();

				self.delete_messages();
				self.crmfwc_response_loading();

		        $('html, body').animate({
		        	scrollTop: $('#crmfwc-admin-menu').offset().top -30
		        }, 'slow');

				var cats          = $('.crmfwc-products-cats').val();
				var data           = {
					'action': 'export-products',
					'crmfwc-export-products-nonce': crmfwcSettings.exportProductsNonce,
					'cats': cats
				}

				$.post(ajaxurl, data, function(response){

					var result = JSON.parse(response);

					for (var i = 0; i < result.length; i++) {

						var error = 'error' === result[i][0] ? true : false;
						var update = 0 !== i ? true : false; 

						self.crmfwc_response_message( result[i][1], error, false );

					}

				})
			
			})

		})

	}


	/**
	 * Delete all the products from CRM in Cloud
	 */
	self.crmfwc_delete_remote_products = function() {

		jQuery(function($){

			$('.button-primary.crmfwc.red.products').on('click', function(e){

				e.preventDefault();

				self.delete_messages();

				var answer = confirm( 'Vuoi cancellare tutti i prodotti da CRM in Cloud?' ); // temp.

				if ( answer ) {

					self.crmfwc_response_loading();

			        $('html, body').animate({
			        	scrollTop: $('#crmfwc-admin-menu').offset().top -30
			        }, 'slow');
					
					var data = {
						'action': 'delete-remote-products',
						'crmfwc-delete-products-nonce': crmfwcSettings.deleteProductsNonce,
					}

					$.post(ajaxurl, data, function(response){

                        // console.log( response );
						var result = JSON.parse(response);

						for (var i = 0; i < result.length; i++) {

							var error = 'error' === result[i][0] ? true : false;
							var update = 0 !== i ? true : false; 

							self.crmfwc_response_message( result[i][1], error, false );
	
						}

					})

				}

			})

		})

	}


	/**
	 * Fires Chosen
	 * @param  {bool} destroy method distroy
	 */
	self.chosen = function(destroy = false) {

		jQuery(function($){

			$('.crmfwc-select').chosen({
		
				disable_search_threshold: 10,
				width: '200px'
			
			});

		})

	}


}


/**
 * Class starter with onLoad method
 */
jQuery(document).ready(function($) {
	
	var Controller = new crmfwcController;
	Controller.onLoad();

});
