var rad = {};


					
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
					
					
					


