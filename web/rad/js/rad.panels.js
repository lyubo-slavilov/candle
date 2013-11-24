rad.panels = {}

/*
 *******************************************
 *Custom changes must be made here
 *******************************************
 */
rad.panels.switcher = $('<select>').addClass('ui-panel-switch');
//add or remove more types of panels
//$('<option>').val('MainMenu').addClass('ui-panel-type-mainmenu').text('Main Menu').appendTo(rad.panels.switcher);
//$('<option>').val('Home').addClass('ui-panel-type-home').text('Home').appendTo(rad.panels.switcher);
$('<option>').val('Navigation').addClass('ui-panel-type-navigation').text('Navigation').appendTo(rad.panels.switcher);
$('<option>').val('Workarea').addClass('ui-panel-type-workarea').text('Work Area').appendTo(rad.panels.switcher);
//$('<option>').val('Video').addClass('ui-panel-type-video').text('Video').appendTo(rad.panels.switcher);
//$('<option>').val('Gallery').addClass('ui-panel-type-gallery').text('Gallery').appendTo(rad.panels.switcher);
$('<option>').val('Properties').addClass('ui-panel-type-properties').text('Properties').appendTo(rad.panels.switcher);
//$('<option>').val('StatusBar').addClass('ui-panel-type-statusbar').text('Status Bar').appendTo(rad.panels.switcher);


rad.panels._getPanelRemoteContent = function(panelName, data){
	//Change this to suite your url logic
	if (data) {
		data = '?data=' + data;
	} else {
		data = '';
	}
	return '/rad.php/widget/'+panelName+data;
}

rad.panels.initMainMenu = function(panel){
//	panel.find('.ui-panel-header').hide();
//	panel.find('.ui-panel-content').css({top:2});
}
rad.panels.initHome = function(panel){
	this.initMainMenu(panel);
}

rad.panels.initWorkArea = function(panel){
	//put your logic here
}

rad.panels.initNavigation = function(panel){
	//put your logic here
}

rad.panels.initProperties = function(panel){
	//put your logic here
}

rad.panels.initStatusBar = function(panel){
	//put your logic here
}

/*
 *******************************************
 *Please, do not touch code bellow if you dont 
 *know what you are doing.
 *******************************************
 */

/**
 * Initializes a panel
 */
rad.panels._initPanel = function(panel, doNotChangeTitle){
	var pData = panel.data('panel.customData');
	var pContainer = panel.find('.ui-panel-content');
	
	
	//load the remote content
	if(pData){
		if (typeof pData.rel == 'undefined') {
			pData.rel = null;
		}
		
		
		panel.addClass('panel-'+pData.type.toLowerCase());
		if (!doNotChangeTitle) {
			panel.find('.ui-panel-header').html('<span class="ui-panel-title">'+pData.type+"</span>");
		} else {
			panel.find('.ui-panel-header').html('<span class="ui-panel-title">'+panel.find('.ui-panel-title').text()+"</span>");
		}
		$.get(this._getPanelRemoteContent(pData.type.toLowerCase(), pData.rel),function(data){
			pContainer.html(data);
			if(typeof rad.panels['init'+pData.type] === 'function'){
				rad.panels['init'+pData.type](panel);
			}
			
			rad.panels._initJScrollPane(pContainer);
			
			//create the switcher
			var s = rad.panels.switcher.clone(true);
			s.prependTo(panel.find('.ui-panel-header'));
			s.find('.ui-panel-type-'+pData.type.toLowerCase()).attr('selected',true);
			rad.panels._initSwitcher(s,panel);
			
			rad.panels._initAjaxLinks(panel);
			rad.panels.initDialogs(panel);
			panel.find('.rad-selectable').radselectable();
			
		}).error(function(){
			$('<p>')
			.addClass('ui-state-error')
			.text('Failed to init...')
			.css({
				border: 'none', 
				margin: '10px'
			})
			.appendTo(pContainer);
		}).complete(function(){
			rad.panels.initilizedPanelsCount ++;
			if(rad.panels.initilizedPanelsCount >= rad.panels.loadedState.panels.length){
				rad.panels.stateLoaded = true;
				$('#panels').css({
					opacity:1
				});
				$('.loading-message').dialog('hide').dialog('destroy').remove();
			}
		});
	}
}

rad.panels._initSwitcher = function(s, panel){
	
	s.change(function(){
		var pd = panel.data('panel.customData');
		panel.removeClass('panel-'+pd.type.toLowerCase());
		panel.find('.ui-panel-content').remove();
		$('<div>').addClass('ui-panel-content').appendTo(panel);
		panel.data('panel.customData', {type:$(this).val()});
		rad.panels._initPanel(panel);
		
	});
	s.selectmenu({
		icons: [
			{find: '.ui-panel-type-workarea', icon: 'ui-icon-pencil'},
			{find: '.ui-panel-type-video', icon: 'ui-icon-video'},
			{find: '.ui-panel-type-gallery', icon: 'ui-icon-image'},
			{find: '.ui-panel-type-home', icon: 'ui-icon-home'},
			{find: '.ui-panel-type-mainmenu', icon: 'ui-icon-grip-dotted-horizontal'},
			{find: '.ui-panel-type-navigation', icon: 'ui-icon-folder-open'},
			{find: '.ui-panel-type-properties', icon: 'ui-icon-note'},
			{find: '.ui-panel-type-statusbar', icon: 'ui-icon-document'}
		]
	});
}

rad.panels._initJScrollPane = function(what){
	
	what.jScrollPane('destroy');
	what.jScrollPane({
		autoReinitialise: true,
		verticalGutter: 30,
		hideFocus: true
	});
}

rad.panels._initAjaxLinks = function(panel){
	$(panel).find('a.ajax').on('click', function(event){
		event.preventDefault();
		var target = $('.' + $(this).attr('data-target'));
		if (target.length == 0) {
			target = panel;
		}
		
		if (target.is('.ui-panel')) {
			rad.panels.switchPanelTo(target, $(this).attr('data-source'), $(this).attr('data-data'));
		} else {
			var url = $(this).attr('data-source');
			var prefix = '/rad.php/';
			if (url.search('/rad.php/') == 0) {
				prefix = '';
			}
			target.load(
				prefix + $(this).attr('data-source') + '?data=' +$(this).attr('data-data'),
				function() {
					var targetPanel = target.closest('.ui-panel');
					var pContainer = targetPanel.find('.ui-panel-content');
					
					//pContainer.jScrollPane('reinitialize');
					
					rad.panels._initAjaxLinks(targetPanel);
					rad.panels.initDialogs(targetPanel);
					panel.find('.rad-selectable').radselectable();
				}
			)
		}
		return false;
	});
}

rad.panels.switchPanelTo = function(panel, type, rel) {
	var pd = panel.data('panel.customData');
	panel.removeClass('panel-'+pd.type.toLowerCase());
	panel.data('panel.customData', {type: type, rel: rel});
	rad.panels._initPanel(panel, true);
}


rad.panels.initDialogs = function(panel) {
	$(panel).find('a[data-action=dialog]').off('click.dialog').on('click.dialog', function(){
		var anchor = $(this);
		$('<div>').dialog({
			modal: true,
			resizable: false,
			title: anchor.attr('title'),
			width: 'auto',
			open: function() {
				var dialog = $(this);
				dialog.load(anchor.attr('data-source'), function(){
				
					var form = dialog.find('form');
					
					var buttons = form.find('.actions button');
					if (buttons.length > 0) {
						var buttonsOption = [];
						buttons.each(function(){
							var opt = {};
							opt[$(this).text()] = function(){form.submit()};
							buttonsOption.push({
								text: $(this).text(),
								click: function(){form.submit()}
							})
						});
						
						buttonsOption.push({
							text: 'Cancel',
							click: function(){dialog.dialog('close')},
							class: 'linklike'
						})
						dialog.dialog('option', {buttons: buttonsOption});
					}
					
					form.on('submit', function(event){
						event.preventDefault();
						$.post(
							form.attr('action'),
							form.serialize()
						).success(function(){
							dialog.dialog('close');
							anchor.trigger('dialogSuccess');
						}).error(function(a, b, c){
							console.log(a,b,c);
							var event = $.Event();
							event.type = 'dialogError';
							event.dialog = dialog;
							event.handled = false;
							anchor.trigger(event, [a]);
							
							if (!event.handled) {
								if (a.responseText == '') {
									rad.alert('Something went wrong. Please try again');
								} else {
									rad.alert(a.responseText);
								}
							}
							
						});
						return false;
					});
					dialog.dialog('option', {
						position: {
							my: "center",
							at: "center",
							of: window
						}
					});
					
					form.find('input:nth(0)').focus();
					
				});
				
			},
			close: function(){
				$(this).dialog('destroy');
				$(this).remove();
			}
		});
	});
}