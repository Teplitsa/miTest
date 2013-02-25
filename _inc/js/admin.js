/**
 * Admin UI
 **/


jQuery(document).ready(function($){
	

	/**
	 * Shortcodes Dropdwn
	 **/
	$('.frl_shortcodes').find(":selected").prop("selected", false);
	$('.frl_shortcodes').change(function() {
		
		//get data	
		var choice = $(this).find(":selected"),
			tag = choice.val(),
			modal = choice.attr('data-modal'),
			format = choice.attr('data-format'),
			buttonsL10n = {};
				
		if(tag == 0)
			return false; //it's placeholder option
		
		if(modal > 0 && typeof($().dialog) == 'function') {		
			
			var info = $('#frl-shortcode-modal');
			
			//find content
			$.ajax({
				type: "post",
				url: ajaxurl,
				data: { action: 'shortcode_modal',
						shortcode: tag,
						_frl_ajax_nonce: $('#_frl_shortcode_nonce').val()
					  },
				beforeSend: function() {
					
					
				},
				success: function(response){ 
					
					//check for content
					if(response[0] + response[1] == '-1'){						
						info.html("<p>"+mitestL10.scError+"</p>");
						
					} else {						
						info.html(response);						
					}
					
				},
				error:function(){					
					alert(mitestL10.scError);
				}
				
			});//end of ajax
			
			buttonsL10n[mitestL10.scInsert] = function() { //insert 
				
				var attr = ''
					inputs = info.find('fieldset');		    
					
				inputs.each(function(){
					
					var item = $(this).find('input'),
						value;
					
					if(item.length > 0) {
						value = item.val();
						
						if(value.length > 0)
							attr = attr + ' ' + item.attr('name') + '="' + value + '"';
							
					} else {
						item = $(this).find('select');
						value = item.find(':selected').val();
						
						if(value != 0)
							attr = attr + ' ' + item.attr('name') + '="' + value + '"';
					}
										
				});
				
				var scode = "["+tag+attr+"]";
				if(format == 'closed')
					scode += "[/"+tag+"]";
					
				send_to_editor(scode);
				$(this).dialog('close');
			};
			
			buttonsL10n[mitestL10.scCancel] = function() { //cancel button
				$(this).dialog('close');				
			};
			
			info.dialog({                   
				'dialogClass'   : 'wp-dialog',           
				'modal'         : true,
				'autoOpen'      : false, 
				'closeOnEscape' : true,
				'title'         : mitestL10.scTitle,
				'zIndex'        : 300000,
				'buttons'       : buttonsL10n,
				'beforeClose'   : function() { choice.prop("selected", false); },
				'width'         : 400,
				'open'          : function(e, ui) {
					var btns = $('.ui-dialog-buttonset').find('button');					
					btns.eq(0).addClass('button-primary');
					btns.eq(1).addClass('button');
				},
				'close'         : function(e, ui) {
					info.empty();
				}
			});
			
			info.dialog('open');
			
		} else {
			var scode = "["+tag+"]";
			if(format == 'closed')
				scode += "[/"+tag+"]";
					
			send_to_editor(scode);
				
			choice.prop("selected", false);
		}
				
        return false;		
		
    });	
	
	
	/* functions to handle menu_order inline editing CPT */
	/* inline menu_order edit */
	var menuOrder = $('.edit-php').find('td.menu_order');
	
    menuOrder.find('.index-inline').click(function(e){
        
        var container = $(this).parents('.menu_order');
        
        $(this).hide();
        container.find('.index-inline-edit').fadeIn('normal');        
    });
    
    menuOrder.find('input[name="arr_morder_cancel"]').click(function(e){
        e.preventDefault();
        
        var container = $(this).parents('.menu_order');
        container.find('.index-inline-edit').hide();
        container.find('.index-inline').fadeIn('normal'); 
    });
    
    menuOrder.find('input[name="arr_morder_save"]').click(function(e){
        e.preventDefault();
        
        var container = $(this).parents('.menu_order'),
            inputAtt = container.find('input[type="text"]'),
            postID = inputAtt.attr('data-post_id'),
            menuOrder = inputAtt.val(),
            nonce = $('#_wpnonce').val();        
        
        $.ajax({
            type: "post",
            url: ajaxurl,
            data: { action: 'update_post_menu_order',
                    post_id: postID,
                    menu_order: menuOrder,
                    _frl_ajax_nonce: nonce
                  },
            beforeSend: function() {
                inputAtt.addClass('load');
                
            },
            success: function(response){ 
                // remove loader message and append AJAX content
                inputAtt.removeClass('load');
                
                //check for content
                if ( response[0] + response[1] == '-1' ) {//error returned
                    alert(mitestL10.moError);
                    
                } else { 
                    //append result
                    inputAtt.val(response);
                    container.find('.index-inline').text(response);
                    container.find('.index-inline-edit').hide();
                    container.find('.index-inline').fadeIn('normal'); 
                }						
            },
            error:function(){
                alert(mitestL10.moError);
            }
            
        });//end of ajax
        
    });
	
	
});