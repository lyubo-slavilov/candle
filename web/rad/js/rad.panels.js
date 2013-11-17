rad.panels = {}

/*
 *******************************************
 *Custom changes must be made here
 *******************************************
 */
rad.panels.switcher = $('<select>').addClass('ui-panel-switch');
//add or remove more types of panels
$('<option>').val('MainMenu').addClass('ui-panel-type-mainmenu').text('Main Menu').appendTo(rad.panels.switcher);
$('<option>').val('Home').addClass('ui-panel-type-home').text('Home').appendTo(rad.panels.switcher);
$('<option>').val('Navigation').addClass('ui-panel-type-navigation').text('Navigation').appendTo(rad.panels.switcher);
$('<option>').val('Workarea').addClass('ui-panel-type-workarea').text('Work Area').appendTo(rad.panels.switcher);
$('<option>').val('Video').addClass('ui-panel-type-video').text('Video').appendTo(rad.panels.switcher);
$('<option>').val('Gallery').addClass('ui-panel-type-gallery').text('Gallery').appendTo(rad.panels.switcher);
$('<option>').val('Properties').addClass('ui-panel-type-properties').text('Properties').appendTo(rad.panels.switcher);
$('<option>').val('StatusBar').addClass('ui-panel-type-statusbar').text('Status Bar').appendTo(rad.panels.switcher);


rad.panels._getPanelRemoteContent = function(panelName, rel){
	//Change this to suite your url logic
	if (rel) {
		rel = '?rel=' + rel;
	} else {
		rel = '';
	}
	return '/rad.php/widget/'+panelName+rel;
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
		rad.panels.switchPanelTo(panel, $(this).attr('panel'), $(this).attr('rel'));
		return false;
	});
}

rad.panels.switchPanelTo = function(panel, type, rel) {
	var pd = panel.data('panel.customData');
	panel.removeClass('panel-'+pd.type.toLowerCase());
	panel.data('panel.customData', {type: type, rel: rel});
	rad.panels._initPanel(panel, true);
}