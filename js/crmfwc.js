/**
 * JS
 * 
 * @author ilGhera
 * @package crm-in-cloud-for-wc/js
 * @since 0.9.0
 */

var crmfwcController = function() {

	var self = this;

	self.onLoad = function() {
	    self.crmfwc_pagination();
		self.tzCheckbox();
	    self.crmfwc_export_users();
	    self.crmfwc_delete_remote_users();
		self.get_user_groups('customers');
		self.get_user_groups('suppliers');
		self.crmfwc_export_products();
		self.crmfwc_export_orders();
		self.crmfwc_delete_remote_products();
		self.crmfwc_delete_remote_orders();
		self.crmfwc_disconnect();
		self.book_invoice();
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
	 * Plugin tools available only if connected to Reviso
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
	 * Check the connection to Reviso
	 */
	self.crmfwc_check_connection = function() {

		jQuery(function($){

			var data = {
				'action': 'check-connection'
			}

			$.post(ajaxurl, data, function(response){

				if(response) {

					console.log( response );

					/*Activate plugin tools*/
					self.crmfwc_tools_control();
			
					$('.check-connection').html(response);
					$('.crmfwc-connect').hide();
					$('.crmfwc-disconnect').css('display', 'inline-block');
					$('.crmfwc-disconnect').animate({
						opacity: 1
					}, 500);

				} else {

					/*Deactivate plugin tools*/
					// self.crmfwc_tools_control(true);

				}

			})

		})

	}


	/**
	 * Disconnect from Reviso deleting the Agreement Grant Tocken from the db
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

				$(container).append( '<div class="bootstrap-iso"><div class="alert ' + message_class + '"><b><i class="fas ' + icon + '"></i>WC Exporter for Reviso </b> - ' + message + '</div>' );

			} else {

				$(container).html( '<div class="bootstrap-iso"><div class="alert ' + message_class + '"><b><i class="fas ' + icon + '"></i>WC Exporter for Reviso </b> - ' + message + '</div>' );

			}

		})

	}


	/**
	 * Export WP users to Reviso
	 */
	self.crmfwc_export_users = function() {

		jQuery(function($){

			$('.button-primary.crmfwc.export-users').on('click', function(e){

				e.preventDefault();

				self.delete_messages();

				var role  = $('.crmfwc-contacts-role').val();

				var data = {
					'action': 'export-users',
					'crmfwc-export-users-nonce': crmfwcSettings.exportNonce,
					'role': role
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
	 * Delete all the users from Reviso
	 */
	self.crmfwc_delete_remote_users = function() {

		jQuery(function($){

			$('.button-primary.crmfwc.red.users').on('click', function(e){

				console.log( 'DELETE!' );

				e.preventDefault();

				self.delete_messages();

				var type = $(this).hasClass('customers') ? 'customers' : 'suppliers';
				var answer = confirm( 'Vuoi cancellare tutti i ' + type + ' da Reviso?' );

				if ( answer ) {

					var data = {
						'action': 'delete-remote-users',
						'crmfwc-delete-users-nonce': crmfwcSettings.deleteNonce,
						'type': type
					}


					$.post(ajaxurl, data, function(response){

						self.crmfwc_response_loading();

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
	 * Export products to Reviso
	 */
	self.crmfwc_export_products = function() {

		jQuery(function($){

			$('.button-primary.crmfwc.export.products').on('click', function(e){

				e.preventDefault();

				self.delete_messages();

				var terms = $('.crmfwc-products-categories').val();

				var data = {
					'action': 'export-products',
					'crmfwc-export-products-nonce': crmfwcProducts.exportNonce,
					'terms': terms
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
	 * Delete all the products from Reviso
	 */
	self.crmfwc_delete_remote_products = function() {

		jQuery(function($){

			$('.button-primary.crmfwc.red.products').on('click', function(e){

				e.preventDefault();

				self.delete_messages();
							
				var answer = confirm( 'Vuoi cancellare tutti i prodotti da Reviso?' );

				if ( answer ) {

					var data = {
						'action': 'delete-remote-products',
						'crmfwc-delete-products-nonce': crmfwcProducts.deleteNonce,
					}

					$.post(ajaxurl, data, function(response){

						self.crmfwc_response_loading();

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
	 * Show customers and suppliers groups in the plugin options page
	 * @param {string} type customer or supplier
	 */
	self.get_user_groups = function(type) {

		jQuery(function($){

			var groups;
			var data = {
				'action': 'get-' + type + '-groups',
				'confirm': 'yes' 
			}

			$.post(ajaxurl, data, function(response){

				groups = JSON.parse(response);

				if (typeof groups === 'object') {

					for (key in groups) {
						$('.crmfwc-' + type + '-groups').append('<option value="' + key + '">' + groups[key] + '</option>');
					}

				} else {

					$('.crmfwc-' + type + '-groups').append('<option>' + groups + '</option>');

				}

				$('.crmfwc-' + type + '-groups').addClass('crmfwc-select');
		        self.chosen(true);

			})

		})

	}


	/**
	 * Export orders to Reviso
	 */
	self.crmfwc_export_orders = function() {

		jQuery(function($){

			$('.button-primary.crmfwc.export.orders').on('click', function(e){

				e.preventDefault();

				self.delete_messages();

				var statuses = $('.crmfwc-orders-statuses').val();

				var data = {
					'action': 'export-orders',
					'crmfwc-export-orders-nonce': crmfwcOrders.exportNonce,
					'statuses': statuses
				}

				$.post(ajaxurl, data, function(response){

					console.log(response);
										
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
	 * Delete all orders from Reviso
	 */
	self.crmfwc_delete_remote_orders = function() {

		jQuery(function($){

			$('.button-primary.crmfwc.red.orders').on('click', function(e){

				e.preventDefault();

				self.delete_messages();
								
				var answer = confirm( 'Vuoi cancellare tutti gli ordini da Reviso?' );

				if ( answer ) {

					var data = {
						'action': 'delete-remote-orders',
						'crmfwc-delete-orders-nonce': crmfwcOrders.deleteNonce,
					}

					$.post(ajaxurl, data, function(response){

						self.crmfwc_response_loading();

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
	 * Show the book invoices option only with issue invoices option activated
	 */
	self.book_invoice = function() {

		jQuery(function($){

			var	issue_invoices 		 = $('.crmfwc-issue-invoices');
			var issue_invoice_button = $('.crmfwc-issue-invoices-field span.tzCheckBox');
			var	book_invoices_field  = $('.crmfwc-book-invoices-field');
			var	send_invoices_field  = $('.crmfwc-send-invoices-field');
			
			if ( $(issue_invoices).attr('checked') == 'checked' ) {

				book_invoices_field.show();
				send_invoices_field.show();

			}

			$(issue_invoice_button).on( 'click', function(){
				
				if ( $(this).hasClass('checked') ) {
				
					book_invoices_field.show();
					send_invoices_field.show();
				
				} else {
				
					book_invoices_field.hide('slow');			
					send_invoices_field.hide('slow');			
		
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

			$('.crmfwc-select-large').chosen({
		
				disable_search_threshold: 10,
				width: '290px'
			
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
