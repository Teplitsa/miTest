/**
 * Frontend UI
 **/


jQuery(document).ready(function($){
	
	/* global container */
	var frlmitest = {};
	
	frlmitest.module = $('#module-test'),
	frlmitest.scoreHolder = $('#total-score'),
	frlmitest.scoreInfos = $('.total-score'),
	frlmitest.totalQuestions = parseInt($('[name="total_questions"]').val());
	
	
	/* storage */
	var progresstracker = {
		
		storage : {},
		module  : $('#module_id').val(),
		
		getStorage : function() {
			
			var key = 'frl_module_'+ this.module,
				s = $.totalStorage(key); 
			
			if(typeof(s) === 'string')
				s = JSON.parse(s);
			else
				s = {};
			
			return s;
		},
		
		setStorage : function() {
			
			var key = 'frl_module_'+ this.module,
				s = JSON.stringify(this.storage);
				
			$.totalStorage(key, s);
		},
		
		clearStorage : function() {
			
			var key = 'frl_module_'+ this.module;
			$.totalStorage.deleteItem(key);
		},
		
		storeQuestion : function(questionID) {
			
			var q = $('#'+questionID),
				currentInd = parseInt(q.attr('data-qorder'));
				
			if(currentInd == 0) {
				//introductory question - no options				
				this.storage[questionID] = $('#tsite_url').val();
				
			} else {
				//regular questions
				var	selection = q.find(':checked'),
					qdata = new Array(),
					comments = {};
				
				if(selection.length > 0){ //have selection
					selection.each(function(){
						var opt = $(this).attr('id'),
							optHolder = $(this).parents('.q-option'),
							comment, commentID;
							
						qdata.push(opt);
						
						if(optHolder.hasClass('has-comment')){//check for comments
							comment = optHolder.find('textarea').val();
							commentID = optHolder.find('textarea').attr('id');
							comments[commentID] = comment;							
						}
					});
					
					this.storage[questionID] = qdata;
					if(undefined !== this.storage['comments'])
						$.extend(this.storage['comments'], comments);
					else
						this.storage['comments'] = comments; console.log(this.storage['comments']);
				}
			} 
			
			this.setStorage(); 
		},
		
		populateQuestion : function(questionID) {
			
			if(undefined === this.storage[questionID])
				return false; //no item for this question
			
			var q = $('#'+questionID),
				qdata = this.storage[questionID];
			
			if(typeof(qdata) === 'string'){
				$('#tsite_url').val(qdata);
				
			} else {
				for (var i in qdata) {
					var id = qdata[i];
					q.find('#'+id).prop("checked", true);
				}
			}
			
			
			
			return true;
		},
		
		populateComments : function() {
			
			var comments = this.storage['comments'];
			
			for (var cID in comments) {
				var comment = comments[cID]; 
				
				$('#'+cID).val(comment);
				$('#'+cID).parents('.q-option-comment').show();
			}
		},
		
		init : function() {
			
			this.storage = this.getStorage();
			
		}
	};
		
	
	
	/* question actions */
	var questionWork = {
		
		settings : {},
		
		init : function(currentQ) {
			
			this.settings.question = currentQ,
			this.settings.selection = currentQ.find(':checked'),
			this.settings.index = parseInt(currentQ.attr('data-qorder')),			
			this.settings.readyBtn = currentQ.find('.ready'),
			this.settings.cancelBtn = currentQ.find('.cancel')			
		},
			
		
		readyHide : function() {
			
			this.settings.readyBtn.css('display', 'none');
			this.settings.cancelBtn.css('display', 'inline-block');
		},
		
		readyRestore : function() {
			
			this.settings.readyBtn.css('display', 'inline-block');
			this.settings.cancelBtn.css('display', 'none');
		},
		
		hide : function() {			
			
			this.settings.readyBtn.css('display', 'none');
			this.settings.cancelBtn.css('display', 'none');
		},
		
		freeze: function() {
			//setup active/inactive options
			this.settings.selection.parents('tr').addClass('active');
			this.settings.question.find('tr').each(function(){
				
				if(!$(this).hasClass('active'))
					$(this).addClass('inactive');
			});
			
			//prevent selection
			this.settings.selection.on('click', function(e){
				e.preventDefault();
			});
		},
		
		unfreeze: function() {
			//remove active inactive classes
			this.settings.question.find('tr').each(function(){
				
				$(this).removeClass('inactive').removeClass('active');
			});
			
			//remove click handler
			this.settings.selection.off('click');
		},
		
		show : function(fade) {
			if(fade)
				this.settings.question.find('.question-comments').fadeIn();
			else
				this.settings.question.find('.question-comments').show();
		},
		
		progress: function() {
			
			var total = this.totalScore(), //total score
				furthest, progVal;
				
				furthest = frlmitest.module.find(':checked').parents('.question').attr('data-qorder');				
				progVal = Math.round((parseInt(furthest)/frlmitest.totalQuestions)*100); //% of progress
			
			
			
			//animate progress				
			progressBlock.find('.ui-progressbar-value').animate({ width: progVal+"%"}, {queue: false});
					
			//show new total
			frlmitest.scoreInfos.text(total);			
		},
		
		totalScore: function() {
			
			var selection = frlmitest.module.find(':checked'),
				score = 0;
			
			selection.each(function(){
				
				var sc = parseInt($(this).attr('date-score'));
				score += sc;
			});
			
			return score;
		},
		
		saveSelection : function() {
			
			var fullSelection = frlmitest.module.find(':checked'),
				comments = frlmitest.module.find('.q-option-comment').find('textarea'),
				optionsSel, commentsSel = {};		
						
			fullSelection.each(function(i){ //prepare options
				
				var inputed = $(this).attr('id');
				if(i == 0)
					optionsSel = inputed;
				else
					optionsSel = optionsSel+','+inputed;
			});
			
			comments.each(function(i){ //prepare comments
				
				var text = $(this).val(); 
				if(text.length > 0){
					var id = $(this).attr('id');
					commentsSel[id] = text;
				}
			});
								
			$.ajax({
				type: "post",
				url: frlFront.ajaxurl,
				data: { action    : 'store_test',
						selection : optionsSel,
						comments  : commentsSel,
						module    : $('#module_id').val(),
						url       : $('#tsite_url').val(),
						score     : this.totalScore(),
						_frl_ajax_nonce: $('#_frl_nonce').val()
					  },
				success: function(response){ 				
					
					if(response[0] + response[1] == '-1'){						
						//error?
						
					} else {						
						//console.log(response);
						$('#submition_id').val(response);
					}
					
				}
				
			});//end of ajax
		},
		
		ready: function(currentQ) {
			
			this.init(currentQ);
			
			//no selection ?
			if(this.settings.selection.length == 0) {
				this.settings.question.find('.alert').fadeIn();				
				return false; //stop doing things
			
			} else {
				this.settings.question.find('.alert').hide();
			}
			
			//hide button
			this.readyHide();
			
			//freeze
			this.freeze();
			
			//show comments
			this.show(true);
			
			//progress
			this.progress();
									
			return true;
		},
		
		finalize: function(currentQ) {
			
			this.init(currentQ);
			
			//hide buttons - no way back
			this.hide();
			
			//freeze
			this.freeze();
			
			//progress
			this.progress();
									
			return true;
		},
		
		cancel: function(currentQ) {
			
			this.init(currentQ);
			
			//hide possible errors (just in case)
			this.settings.question.find('.alert').hide();
			
			//change buttons
			this.readyRestore();
			
			//unfreeze
			this.unfreeze();
			
			//comments and progress remains			
		},
		
		reveal: function(currentQ) { //when restored from local storage
			
			this.init(currentQ);
			
			if(this.settings.selection.length == 0) {
				return false; //stop doing things, no errors
			
			} else {
				this.settings.question.find('.alert').hide(); //just in case
			}
			
			//hide button
			this.readyHide();
			
			//freeze
			this.freeze();
			
			//show comments
			this.show(false);
			
			//progress
			var total = this.totalScore(); //total score
				progVal = Math.round((this.settings.index/frlmitest.totalQuestions)*100); //% of progress
			
			//animate progress				
			progressBlock.find('.ui-progressbar-value').animate({ width: progVal+"%"}, {queue: false});
					
			//show new total
			frlmitest.scoreInfos.text(total);	
			
			return true;			
		}
	};
	
	
	/**
	 * States
	 **/
		
	/* sticky header */	
	var stickyNav = $('#module-title'),
		mainContent = $('div[role="main"]');
		sticky_navigation_offset_top = stickyNav.offset().top,
		stickyNavH = parseInt($('#module-title').height()) + 40;
	
	
	var sticky_navigation = function(){
		var scroll_top = $(window).scrollTop(); // our current vertical position from the top		
		
		if (scroll_top > sticky_navigation_offset_top) { 
			stickyNav.addClass('fixed');
			mainContent.addClass('padded');
			
		} else {
			stickyNav.removeClass('fixed');
			mainContent.removeClass('padded');
			
		}   
	};	
	
	sticky_navigation(); //init
	
	$(window).scroll(function() {
		sticky_navigation();
	});
		
	
	/* progressbar */
	var progressBlock = $("#progressbar").progressbar({value: 0.1});		
		progressBlock.find('.ui-progressbar-value').addClass('meter');
		
	/* progresstracker init */
	progresstracker.init();
	
	/* comment field */
	frlmitest.module.find('.checkbox').find('input').on('change', function(e){
		
		var input = $(this),
			testTr = input.parents('tr');
			
		
		if(testTr.hasClass('has-comment') ){
			if(input.is(':checked'))
				testTr.find('.q-option-comment').fadeIn();
			else
				testTr.find('.q-option-comment').fadeOut();
		}		
		
		return true;
	});
	
	/* toggle area */
	$('.toggle-area').on('change', '.toggle-trigger', function(e){
		
		$(this).parents('.toggle-area').toggleClass('toggled');		
		
		return true;	
	});
	
	
	
	/**
	 * Main actions
	 **/
	
	/* click on .ready */	
	frlmitest.module.find('.question').on('click', '.ready', function(e){
		
		e.preventDefault();
		
		var currentQ = $(this).parents('.question');
		
		questionWork.ready(currentQ);
		progresstracker.storeQuestion(currentQ.attr('id')); //store progress
	});
	
	/* click on cancel */
	frlmitest.module.find('.question').on('click', '.cancel', function(e){
		
		e.preventDefault();
		
		var currentQ = $(this).parents('.question');
		
		questionWork.cancel(currentQ);
		
	});
	
	/* click on next - store progress no */	
	frlmitest.module.find('.question').on('click', '.next', function(e){
		
		e.preventDefault();
		
		var currentQ = $(this).parents('.question'),
			order = parseInt(currentQ.attr('data-qorder')),
			nextId, targetTop;
					
		//first Q
		if(order == 0) { 
			
			//validate for not empty url
			var givenURL = $('#tsite_url').val();
			if(givenURL.length == 0){
				currentQ.find('.error').css({'display' : 'block'});
				$('#tsite_url').addClass('error');
				
				return false; //stop doing things
			
			} else {				
				$('#tsite_url').removeClass('error');
				currentQ.find('.error').hide();
				
				progresstracker.storeQuestion(currentQ.attr('id'));
			}
			
		}
		
		//freeze result for Q - in fav of progress bar
		questionWork.finalize(currentQ);
		
		//last Q
		if(order == frlmitest.totalQuestions){ //activate proper result description	
			
			//save selection in DB
			questionWork.saveSelection();
			
			//clear storage
			progresstracker.clearStorage();
			
			//prepare results
			var intervals = $('#after-test').find('.interval'),
				score = questionWork.totalScore();
				
				
			intervals.each(function(){
				var currentInterval = $(this),
					bottom = parseInt(currentInterval.find('input.bottom').val()),
					top = parseInt(currentInterval.find('input.top').val());
				
				
				if(score > bottom && score <= top) {
					currentInterval.addClass('active');
				}
			});
		}
		
		//show next question
		currentQ.next().fadeIn();
			
		//scroll
		nextId = currentQ.next().attr('id');
		targetTop = $("#"+nextId).offset().top - stickyNavH;
				
		$('html, body').animate({scrollTop:targetTop}, 800);
		
				
		return true;
	});
	
	
	/**
	 * Submitions
	 **/
	
	frlmitest.module.on('submit', function(e){
		
		e.preventDefault();
		
		var lastQ = $('#after-test'),
			consult = lastQ.find('#consultation'),
			sendRes = lastQ.find('#send_results'),
			comment = lastQ.find('#comment'),
			emailPattern = new RegExp(/^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/),
			testData = {};
		
		//hide all errors		
		lastQ.find('.error').css({'display' : 'none'});
				
		if(consult.is(':checked')){ //consultation required
			
			var cName = lastQ.find('input[name="consult_name"]'),
				cEmail = lastQ.find('input[name="consult_email"]'),				
				validData = true;
				
			//validation
			if(cName.val().length == 0){
				validData = false;
				
				lastQ.find('.cname-error').css({'display' : 'block'});
			}
			
			if(cEmail.val().length == 0) {
				validData = false;
				
				lastQ.find('.cemail-error').css({'display' : 'block'});				
				
			} else if(!emailPattern.test(cEmail.val())){
				
				validData = false;
				lastQ.find('.cemail-error').css({'display' : 'block'});					
			}
			
			if(validData) {
				testData.consult = 1;
				testData.consultName = cName.val();
				testData.consultEmail = cEmail.val();
				
			} else {
				return false;
			}
		}
		
		if(sendRes.is(':checked')){ //sending is required
			
			var sEmail = lastQ.find('input[name="send_results_contacts"]');
			if(sEmail.val().length == 0) {				
				
				lastQ.find('.semail-error').css({'display' : 'block'});
				return false;
				
			} else if(!emailPattern.test(sEmail.val())){
								
				lastQ.find('.semail-error').css({'display' : 'block'});
				return false;
				
			} else { //valid
				
				testData.sendReq = 1;
				testData.sendEmail = sEmail.val();
			}
		}
		
		if(comment.is(':checked')){ //comment
			
			testData.commentText = $('textarea[name="comment_text"]').val();
		}
		
		//submit data
		$.ajax({
			type: "post",
			url: frlFront.ajaxurl,
			data: { action    : 'submit_test',
					entry     : $('#submition_id').val(),
					test_data : testData,
					_frl_ajax_nonce: $('#_frl_nonce').val()
				  },
			beforeSend : function() {
				
				$('#loading').addClass('active');
			},
			success: function(response){ 				
				
				$('#loading').removeClass('active');
				
				if(response[0] + response[1] == '-1'){						
					//error
					lastQ.find('#thankyou').find('.regular').hide(); //hide ok
					lastQ.find('#thankyou').find('.alert').show();
					
				} else {						
					lastQ.find('#thankyou').find('.alert').hide(); //hide error
					lastQ.find('#thankyou').find('.regular').show();
				}
				
			},
			error: function() {
				lastQ.find('#thankyou').find('.regular').hide(); //hide ok
				lastQ.find('#thankyou').find('.alert').show();
			}
			
		});//end of ajax
		
		//final
		$('#module_submit').hide();
		lastQ.find('.results-comments').fadeOut('fast').empty();
		lastQ.find('#thankyou').fadeIn(2000);
		
		return false;
	});
	
	
	
	/**
	 * Restore progress
	 **/
	
	/* have local data ? */
	$(window).load(function(){
		
		var questions = frlmitest.module.find('.question'),
			testQ = questions.eq(0).attr('id'),
			targetQ = '';
		
		if(undefined !== progresstracker.storage[testQ] && progresstracker.storage[testQ].length > 0) { //do we have local data
			
			$('#localdata').reveal({closeOnBackgroundClick : false});
			$('#localdata').on('click', '#local-no', function(e){
				
				//clear selections (just in case)
				$('#tsite_url').val('');
				questions.find(':checked').prop("checked", false);
				
				//clear storage
				progresstracker.clearStorage();
				
				$(this).trigger('reveal:close');	
			});
			
			$('#localdata').on('click', '#local-yes', function(e){
				e.preventDefault();
								
				questions.each(function(i){
					var currentQ = $(this),
						res;					
					
					if(i != (frlmitest.totalQuestions)){//no answer for last question - ever
						
						res = progresstracker.populateQuestion(currentQ.attr('id'));						
						if(i != 0 && res){
							questionWork.reveal(currentQ);
							currentQ.show();
							targetQ = currentQ.attr('id');
						}

					}					
				});
				
				//options' comments
				progresstracker.populateComments();
					
				if(targetQ.length > 0){ //further than first?
					questions.eq(0).find('.button').hide();
					
					//scroll on start
					var tq = questions.eq(1).attr('id');
					
					//scroll
					targetTop = $("#"+tq).offset().top - stickyNavH;
					$('html, body').animate({scrollTop:targetTop}, 800);
				}
								
				$(this).trigger('reveal:close');		
			});
		
		}//have storage
				
	});//load end
	
});

