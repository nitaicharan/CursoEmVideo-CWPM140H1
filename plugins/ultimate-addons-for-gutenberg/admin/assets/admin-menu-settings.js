( function( $ ) {

	/**
	 * AJAX Request Queue
	 *
	 * - add()
	 * - remove()
	 * - run()
	 * - stop()
	 *
	 * @since 1.2.0.8
	 */
	var UAGBAjaxQueue = (function() {

		var requests = []

		return {

			/**
			 * Add AJAX request
			 *
			 * @since 1.2.0.8
			 */
			add:  function(opt) {
			    requests.push(opt)
			},

			/**
			 * Remove AJAX request
			 *
			 * @since 1.2.0.8
			 */
			remove:  function(opt) {
			    if( jQuery.inArray(opt, requests) > -1 )
			        requests.splice($.inArray(opt, requests), 1)
			},

			/**
			 * Run / Process AJAX request
			 *
			 * @since 1.2.0.8
			 */
			run: function() {
			    var self = this,
			        oriSuc

			    if( requests.length ) {
			        oriSuc = requests[0].complete

			        requests[0].complete = function() {
			             if( typeof(oriSuc) === "function" ) oriSuc()
			             requests.shift()
			             self.run.apply(self, [])
			        }

			        jQuery.ajax(requests[0])

			    } else {

			      self.tid = setTimeout(function() {
			         self.run.apply(self, [])
			      }, 1000)
			    }
			},

			/**
			 * Stop AJAX request
			 *
			 * @since 1.2.0.8
			 */
			stop:  function() {

			    requests = []
			    clearTimeout(this.tid)
			}
		}

	}())

	UAGBAdmin = {

		init: function() {
			/**
			 * Run / Process AJAX request
			 */
			UAGBAjaxQueue.run()

			$( document ).delegate( ".uagb-activate-widget", "click", UAGBAdmin._activate_widget )
			$( document ).delegate( ".uagb-deactivate-widget", "click", UAGBAdmin._deactivate_widget )

			$( document ).delegate( ".uagb-activate-all", "click", UAGBAdmin._bulk_activate_widgets )
			$( document ).delegate( ".uagb-deactivate-all", "click", UAGBAdmin._bulk_deactivate_widgets )

			$( document ).delegate( ".uag-install-theme", "click", UAGBAdmin._installNow )
			$( document ).delegate( ".uag-activate-theme", "click", UAGBAdmin._activateTheme)

			$( document ).delegate( ".uag-file-generation", "click", UAGBAdmin._fileGeneration )

		},

		_fileGeneration: function( e ) {

			e.preventDefault()
			var button = $( this ),
				value  = button.data("value")

			var data = {
				value : value,
				action: "uagb_file_generation",
				nonce: uagb.ajax_nonce,
			}

			if ( button.hasClass( "updating-message" ) ) {
				return
			}

			$( button ).addClass("updating-message")

			UAGBAjaxQueue.add({
				url: ajaxurl,
				type: "POST",
				data: data,
				success: function(data){
					console.log(data);
					location.reload();
				}
			})

		},

		/**
		 * Activate All Widgets.
		 */
		_bulk_activate_widgets: function( e ) {
			var button = $( this )

			var data = {
				action: "uagb_bulk_activate_widgets",
				nonce: uagb.ajax_nonce,
			}

			if ( button.hasClass( "updating-message" ) ) {
				return
			}

			$( button ).addClass("updating-message")

			UAGBAjaxQueue.add({
				url: ajaxurl,
				type: "POST",
				data: data,
				success: function(data){

					console.log( data )

					// Bulk add or remove classes to all modules.
					$(".uagb-widget-list").children( "li" ).addClass( "activate" ).removeClass( "deactivate" )
					$(".uagb-widget-list").children( "li" ).find(".uagb-activate-widget")
						.addClass("uagb-deactivate-widget")
						.text(uagb.deactivate)
						.removeClass("uagb-activate-widget")
					$( button ).removeClass("updating-message")
				}
			})
			e.preventDefault()
		},

		/**
		 * Deactivate All Widgets.
		 */
		_bulk_deactivate_widgets: function( e ) {
			var button = $( this )

			var data = {
				action: "uagb_bulk_deactivate_widgets",
				nonce: uagb.ajax_nonce,
			}

			if ( button.hasClass( "updating-message" ) ) {
				return
			}
			$( button ).addClass("updating-message")

			UAGBAjaxQueue.add({
				url: ajaxurl,
				type: "POST",
				data: data,
				success: function(data){

					console.log( data )
					// Bulk add or remove classes to all modules.
					$(".uagb-widget-list").children( "li" ).addClass( "deactivate" ).removeClass( "activate" )
					$(".uagb-widget-list").children( "li" ).find(".uagb-deactivate-widget")
						.addClass("uagb-activate-widget")
						.text(uagb.activate)
						.removeClass("uagb-deactivate-widget")
					$( button ).removeClass("updating-message")
				}
			})
			e.preventDefault()
		},

		/**
		 * Activate Module.
		 */
		_activate_widget: function( e ) {
			var button = $( this ),
				id     = button.parents("li").attr("id")

			var data = {
				block_id : id,
				action: "uagb_activate_widget",
				nonce: uagb.ajax_nonce,
			}

			if ( button.hasClass( "updating-message" ) ) {
				return
			}

			$( button ).addClass("updating-message")

			UAGBAjaxQueue.add({
				url: ajaxurl,
				type: "POST",
				data: data,
				success: function(data){

					// Add active class.
					$( "#" + id ).addClass("activate").removeClass( "deactivate" )
					// Change button classes & text.
					$( "#" + id ).find(".uagb-activate-widget")
						.addClass("uagb-deactivate-widget")
						.text(uagb.deactivate)
						.removeClass("uagb-activate-widget")
						.removeClass("updating-message")
				}
			})

			e.preventDefault()
		},

		/**
		 * Deactivate Module.
		 */
		_deactivate_widget: function( e ) {
			var button = $( this ),
				id     = button.parents("li").attr("id")
			var data = {
				block_id: id,
				action: "uagb_deactivate_widget",
				nonce: uagb.ajax_nonce,
			}

			if ( button.hasClass( "updating-message" ) ) {
				return
			}

			$( button ).addClass("updating-message")

			UAGBAjaxQueue.add({
				url: ajaxurl,
				type: "POST",
				data: data,
				success: function(data){

					// Remove active class.
					$( "#" + id ).addClass( "deactivate" ).removeClass("activate")

					// Change button classes & text.
					$( "#" + id ).find(".uagb-deactivate-widget")
						.addClass("uagb-activate-widget")
						.text(uagb.activate)
						.removeClass("uagb-deactivate-widget")
						.removeClass("updating-message")
				}
			})
			e.preventDefault()
		},


		/**
		 * Activate Success
		 */
		_activateTheme: function( event, response ) {

			event.preventDefault()

			var $button = jQuery(event.target)

			var $slug = $button.data("slug")

			$button.text( uagb.activating_text ).addClass( "updating-message" )

			// WordPress adds "Activate" button after waiting for 1000ms. So we will run our activation after that.
			setTimeout( function() {
				
				$.ajax({
					url: uagb.ajax_url,
					type: "POST",
					data: {
						"action" : "uag-theme-activate",
						"slug"   : $slug,
						"nonce"  : uagb.ajax_nonce,
					},
				})
					.done(function (result) {
					
						if( result.success ) {
							$button.text( uagb.activated_text ).removeClass( "updating-message" )

							setTimeout( function() {
								$button.parents( ".uagb-sidebar" ).find( ".uagb-astra-sidebar" ).slideUp()
							}, 1200 )
						}

					})

			}, 1200 )

		},

		/**
		 * Install Now
		 */
		_installNow: function(event)
		{
			event.preventDefault()

			var $button 	= jQuery( event.target ),
				$document   = jQuery(document)

			$button.text( uagb.installing_text ).addClass( "updating-message" )

			if ( wp.updates.shouldRequestFilesystemCredentials && ! wp.updates.ajaxLocked ) {
				wp.updates.requestFilesystemCredentials( event )

				$document.on( "credential-modal-cancel", function() {
					$button.text( wp.updates.l10n.installNow )
					wp.a11y.speak( wp.updates.l10n.updateCancel, "polite" )
				} )
			}
			
			wp.updates.installTheme( {
				slug:    $button.data( "slug" )
			}).then(function(e){
				$button.removeClass( "uag-install-theme updating-message" ).addClass( "uag-activate-theme" ).text( "Activate Astra Now!" )
			})
		},

	}

	$( document ).ready(function() {
		UAGBAdmin.init()
	})


} )( jQuery )
