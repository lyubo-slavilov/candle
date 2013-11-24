var rad = {};

rad.alert = function(message) {
	$('<div><p class="ui-state-error" style="min-width: 250px; padding:10px">' + message + '</p></div>').dialog({
		modal: true,
		resizable: false,
		width: 'auto',
		minWidth: '250px',
		close: function(){
			$(this).dialog('destroy').remove();
		},
		buttons: [
          {text: 'OK', click: function(){$(this).dialog('close')}}
        ]
	});
}			

					
$(function(){
	$('#panels').panels({
		dividerTickness: 2,
		panelClone: function(e, source, clone){
			
		},
		panelCreate: function(e, panel, isClonning){
			rad.panels._initPanel(panel);
		},
		panelResize: function(e, panel, data){
			if(data.height < 54){
				panel.find('.ui-panel-header').hide();
				panel.find('.ui-panel-content').css({top:2});
			} 
			else{
				panel.find('.ui-panel-content').css({top:22});
				panel.find('.ui-panel-header').show();
			}
			//panel.find('.ui-panel-header').text('W: '+data.width+', H:'+data.height);
			if((panel.width() >= 80 && data.width < 80) || (panel.height() >= 34 && data.height < 34)) return false;
			
			//$('.ui-panel-content').jScrollPane('reinitialize');
		}
	});
	//load the interface
	$('#panels').css({
		opacity:1
	});
	setTimeout(function(){
		if(!rad.panels.stateLoaded){
			$('<div>')
			.addClass('loading-message')
			.text('Loading UI...')
			.dialog({
				modal:true, 
				title: 'Please wait'
			});
		}
	},1000);
				
	$.get('/rad.php/widget/defaultstate',function(data){
		rad.panels.loadedState = data;
		rad.panels.initilizedPanelsCount = 0;
		$('#panels').panels('state', data);
	},'json');
});
					
					
(function( $ ){
	//plugin radselectable
	$.fn.radselectable = function() {
		$(this).addClass('ui-selectable rad-selectable').find('li').addClass('ui-selectee').click(function(){
			$(this).closest('.ui-panel').find('.ui-selectee').removeClass('ui-selected');
			$(this).addClass('ui-selected');
			
		}).find('a').click(function(){
			$(this).closest('li.ui-selectee').trigger('click');
		});
	}
	
	//plugin buttonset vertical
	$.fn.buttonsetv = function() {
	  $(':radio, :checkbox', this).wrap('<div style="margin: 1px"/>');
	  $(this).buttonset();
	  $('a:first', this).removeClass('ui-corner-left').addClass('ui-corner-top');
	  $('a:last', this).removeClass('ui-corner-right').addClass('ui-corner-bottom');
	  mw = 0; // max witdh
	  $('span', this).each(function(index){
	     w = $(this).width();
	     if (w > mw) mw = w; 
	  })
	  $('span', this).each(function(index){
	    $(this).width(mw);
	  })
	  return $(this);
	};
})( jQuery );

