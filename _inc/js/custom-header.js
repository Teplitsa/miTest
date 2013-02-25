(function($) {
	var frame;

	$( function() {		

		// Build the choose from library frame.
		$('#choose-from-library-link').click( function( event ) {
			var $el = $(this);
			event.preventDefault();

			// If the media frame already exists, reopen it.
			if ( frame ) {
				frame.open();
				return;
			}

			// Create the media frame.
			frame = wp.media.frames.customHeader = wp.media({
				// Set the title of the modal.
				title: $el.data('choose'),

				// Tell the modal to show only images.
				library: {
					type: 'image'
				},

				// Customize the submit button.
				button: {
					// Set the text of the button.
					text: $el.data('update'),					
					close: true
				}
			});
			
			// When an image is selected, run a callback.
			frame.on( 'select', function() {
				
				//store ID
				var attachment = frame.state().get('selection').first(),
					imgHolder = $('#header-holder'),
					idHolder = $('input[name="header_id"]');
					idHolder.val(attachment.id);
				
				//display img
				var	sizes = attachment.get('sizes'),
					size = sizes['full'];
					
					$( '<img />', {
						src:    size.url
					}).appendTo( imgHolder );
				
				$el.parents('.header-wrapper').addClass('has-image');
					//console.log(size);
			});
		

			frame.open();
		});
		
		$('#remove-header-image').click(function(event){
			
			event.preventDefault();
			
			$('input[name="header_id"]').val(0);
			$('#header-holder').empty();
			
			$(this).parents('.header-wrapper').removeClass('has-image');
		});
		
	});
}(jQuery));
