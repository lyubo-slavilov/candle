(function($){
	var Panels = {}
	$.extend(Panels, {
    target: null,
    events: {
        init   : "panels.init",
        panel : {
					clone: "panel.clone",
					create: "panel.create",
					destroy: "panel.destroy",
					resize: "panel.resize"
				}
    },
    main: function(target, options) {
			
				this.settings = $.extend({
					dividerTickness: 4
				},options);
				
        this.events = Panels.events;
        var _self  = [this];
 
        this.init(target, options);
        $(Panels).trigger(this.events.init, _self);
    }
		
	});
	
	/**
	 * Initializes the plugin
	 */
	Panels.main.prototype.init = function(target){
		this.target = target;
		
		
		target.bind(this.events.panel.clone, this.settings.panelClone);
		target.bind(this.events.panel.create, this.settings.panelCreate);
		target.bind(this.events.panel.destroy, this.settings.panelDestroy);
		target.bind(this.events.panel.resize, this.settings.panelResize);
		
		//create combiner helper
		if($('.ui-panel-combiner-helper').length == 0){
			$('<div/>').addClass('ui-panel-combiner-helper').appendTo($('body'));
		}
		//creaate default dividers
		var d1 = $('<div/>').addClass('ui-panel-divider ui-panel-divider-h')
			.css({top:0}).height(this.settings.dividerTickness)
			.appendTo(target);
		var d2 = $('<div/>')
			.addClass('ui-panel-divider ui-panel-divider-h')
			.css({bottom:0}).height(this.settings.dividerTickness)
			.appendTo(target);
		var d3 = $('<div/>')
			.addClass('ui-panel-divider ui-panel-divider-v')
			.css({left:0}).width(this.settings.dividerTickness)
			.appendTo(target);
		var d4 = $('<div/>')
			.addClass('ui-panel-divider ui-panel-divider-v')
			.css({right:0}).width(this.settings.dividerTickness)
			.appendTo(target);
		
		//TODO see if attachments are necessary when do the outer dividers not draggable
		this._attachDivider(d1, d3.add(d4), 'top');
		this._attachDivider(d2, d3.add(d4), 'bottom');
		this._attachDivider(d3, d1.add(d2), 'left');
		this._attachDivider(d4, d1.add(d2), 'right');
		
		this._initDraggable('.ui-panel-divider-h', 'h');
		this._initDraggable('.ui-panel-divider-v', 'v');

		this.bindDraggableEvents();
		this.createPanel(target, {
			top: d1,
			bottom: d2,
			left: d3,
			right: d4
		})
	}
	/*
	 *Gets the current state of the container
	 */
	Panels.main.prototype.getState = function(){
		var state = {};
			
		state.container = {};
		state.container.geometry = {
			height: this.target.height(),
			width: this.target.width(),
			top: this.target.position().top,
			left: this.target.position().left
		}
		//prepare dividers
		this.target.find('.ui-panel-divider').each(function(i,e){
			$(e).attr('panel-divider-id', i);
		});
		//prepare panels
		this.target.find('.ui-panel').each(function(i,e){
			$(e).attr('panel-id', i);
		});

		//store dividers neighbors info
		var obj;
		state.dividers = new Array;
		this.target.find('.ui-panel-divider').each(function(i,e){
			obj = {};
			obj.id = $(e).attr('panel-divider-id');

			if($(e).is('.ui-panel-divider-h')) obj.orientation = 'h';
			else obj.orientation = 'v';

			obj.geometry = {
				height: $(e).height(),
				width: $(e).width(),
				top: $(e).position().top,
				left: $(e).position().left
			}

			obj.dividers = {};
			obj.dividers.left = new Array;
			if($(e).prop('dividers.left')){
				$(e).prop('dividers.left').each(function(j,d){
					obj.dividers.left[j] = $(d).attr('panel-divider-id');
				});
			}
			obj.dividers.right = new Array;
			if($(e).prop('dividers.right')){
				$(e).prop('dividers.right').each(function(j,d){
					obj.dividers.right[j] = $(d).attr('panel-divider-id');
				});
			}
			obj.dividers.top = new Array;
			if($(e).prop('dividers.top')){
				$(e).prop('dividers.top').each(function(j,d){
					obj.dividers.top[j] = $(d).attr('panel-divider-id');
				});
			}
			obj.dividers.bottom = new Array;
			if($(e).prop('dividers.bottom')){
				$(e).prop('dividers.bottom').each(function(j,d){
					obj.dividers.bottom[j] = $(d).attr('panel-divider-id');
				});
			}
			state.dividers[i] = obj;

		});
		//store panels info
		state.panels = new Array;
		this.target.find('.ui-panel').each(function(i,e){
			obj = {};
			obj.id = $(e).attr('panel-id');
			obj.customData = $(e).data('panel.customData');
			obj.dividers = {};
			obj.dividers.left = new Array;
			if($(e).prop('dividers.left')){
				$(e).prop('dividers.left').each(function(j,d){
					obj.dividers.left[j] = $(d).attr('panel-divider-id');
				})
			}
			obj.dividers.right = new Array;
			if($(e).prop('dividers.right')){
				$(e).prop('dividers.right').each(function(j,d){
					obj.dividers.right[j] = $(d).attr('panel-divider-id');
				})
			}
			obj.dividers.top = new Array;
			if($(e).prop('dividers.top')){
				$(e).prop('dividers.top').each(function(j,d){
					obj.dividers.top[j] = $(d).attr('panel-divider-id');
				})
			}
			obj.dividers.bottom = new Array;
			if($(e).prop('dividers.bottom')){
				$(e).prop('dividers.bottom').each(function(j,d){
					obj.dividers.bottom[j] = $(d).attr('panel-divider-id');
				})
			}
			state.panels[i] = obj;
		});
		return state;
	}
	
	/*
	 *Set new state of the container
	 */
	Panels.main.prototype.setState = function(state){
		this.target.html('');
		var scaleY = this.target.height()/state.container.geometry.height;
		var scaleX = this.target.width()/state.container.geometry.width;
		var j,i,d,p,sub,g,r;
		var fixedDividers = new Array;
		//create all dividers
		for(i = 0; i< state.dividers.length; i++){
			
			if(state.dividers[i].orientation == 'h'){
				if(state.dividers[i].fixed) r = 1;
				else r = scaleY;
				g = {
					top: state.dividers[i].geometry.top*r,
					left: state.dividers[i].geometry.left*scaleX,
					height: state.dividers[i].geometry.height,
					width: state.dividers[i].geometry.width*scaleX
				}
			}else{
				if(state.dividers[i].fixed) r = 1;
				else r = scaleX;
				g = {
					top: state.dividers[i].geometry.top*scaleY,
					left: state.dividers[i].geometry.left*r,
					height: state.dividers[i].geometry.height*scaleY,
					width: state.dividers[i].geometry.width
				}
			}
			d = this.createDivider(state.dividers[i].orientation, g);
			d.attr('panel-divider-id', state.dividers[i].id);
			d.appendTo(this.target);
			if(state.dividers[i].fixed){
				fixedDividers.push(d);
			}
		}
		//attach dividers to each other
		for(i = 0; i< state.dividers.length; i++){
			d = $('.[panel-divider-id='+i+']');
			sub = $();
			for(j = 0; j < state.dividers[i].dividers.left.length; j++){
				sub = sub.add($('.[panel-divider-id='+state.dividers[i].dividers.left[j]+']'))
			}
			d.prop('dividers.left', sub);
			sub = $('<div>').addClass('dummy');
			for(j = 0; j < state.dividers[i].dividers.right.length; j++){
				sub = sub.add($('.[panel-divider-id='+state.dividers[i].dividers.right[j]+']'))
			}
			d.prop('dividers.right', sub);
			sub = sub = $();
			for(j = 0; j < state.dividers[i].dividers.top.length; j++){
				sub = sub.add($('.[panel-divider-id='+state.dividers[i].dividers.top[j]+']'))
			}
			d.prop('dividers.top', sub);
			sub = $();
			for(j = 0; j < state.dividers[i].dividers.bottom.length; j++){
				sub = sub.add($('.[panel-divider-id='+state.dividers[i].dividers.bottom[j]+']'))
			}
			d.prop('dividers.bottom', sub);
		}
		//reaarange dividers to match fixed ones
		for(i=0; i<fixedDividers.length; i++){
			this._reposAttachedDividers(fixedDividers[i], null);
		}
		//create panels
		for(i = 0; i< state.panels.length; i++){
			p = this.createPanel(this.target, {
				left: $('.[panel-divider-id='+state.panels[i].dividers.left[0]+']'),
				right: $('.[panel-divider-id='+state.panels[i].dividers.right[0]+']'),
				top: $('.[panel-divider-id='+state.panels[i].dividers.top[0]+']'),
				bottom: $('.[panel-divider-id='+state.panels[i].dividers.bottom[0]+']')
			},false,state.panels[i].customData);
			this.target.trigger(this.events.panel.resize, [p, {height:p.height(), width:p.width()}]);
		}
	}
	/*
	 *Binds all events for jQuery Draggable elements
	 */
	Panels.main.prototype.bindDraggableEvents = function(){
		var divs = this.target.find('.ui-panel-divider');
		this.bindDragStartEvent(divs);
		this.bindDragEvent(divs);
		this.bindDragStopEvent(divs);
	}
	/*
	 *bind drag start event
	 */
	Panels.main.prototype.bindDragStartEvent = function(dividers){
		
		dividers.bind('dragstart', function(e, ui){
//			console.log($(this).prop('dividers.left'));
//			console.log($(this).prop('dividers.right'));
//			console.log($(this).prop('dividers.top'));
//			console.log($(this).prop('dividers.bottom'));
			$('.ui-panel').each(function(i, el){
				$(this).prop('originalState', {
					left: $(el).position().left,
					top: $(el).position().top,
					width: $(el).width(),
					height: $(el).height()
				})
			});
			$(this).prop('dragJustStarted', true);
		});
	}
	/**
	 *Bind drag event
	 */
	Panels.main.prototype.bindDragEvent =  function(dividers){
		var $this = this;
		dividers.bind('drag', function(dragEvent, ui){
			if($(this).prop('toClone')){
				var pnlToClone = $(this).prop('toClone');
				var orientation = $(this).is('.ui-panel-divider-h')?'h':'v';
				
				if(orientation == 'h'){
					var divToClone = pnlToClone.prop('dividers.top');
					var divClone = $this.createDivider(orientation, {
						left: divToClone.position().left,
						top: pnlToClone.prop('originalState').top - $this.settings.dividerTickness,
						width:divToClone.width(),
						height: $this.settings.dividerTickness
					}).appendTo($(this).parent());

				}else{
					var divToClone = pnlToClone.prop('dividers.right');
					var divClone = $this.createDivider(orientation, {
						left: pnlToClone.prop('originalState').left+pnlToClone.prop('originalState').width,
						top: divToClone.position().top,
						width: $this.settings.dividerTickness,
						height: divToClone.height()
					}).appendTo($(this).parent());
				}
				
				//Create clonning on top	
				if($(this).prop('clonePosition') == 'top'){
					$(this).css({
						left: pnlToClone.position().left - $this.settings.dividerTickness,
						width: pnlToClone.width() + 2*$this.settings.dividerTickness
					});
					
					//IMPORTANT: do this alwayse before constructing the panel!!!!
					divClone.prop('panels.top', $(this).prop('panels.top'));
					divClone.prop('panels.bottom', $(this).prop('panels.bottom').add(pnlClone).not(pnlToClone));
					divClone.prop('panels.bottom').each(function(i,e){
						$(e).prop('dividers.top', divClone)
					});
					if(divClone.prop('panels.top')){
						divClone.prop('panels.top').each(function(i,e){
							$(e).prop('dividers.bottom', divClone)
						});
					}
					var pnlClone = $this.createPanel($(this).parent(),{
						top: divClone,
						bottom: $(this),
						left: pnlToClone.prop('dividers.left'),
						right: pnlToClone.prop('dividers.right')
					},true, pnlToClone.data('panel.customData'));
					
					pnlClone.prop('originalState', {
						top: pnlClone.position().top,
						left: pnlClone.position().left,
						height: pnlClone.height(),
						width: pnlClone.width()
					});
					
					$(this).prop('panels.top', pnlClone);
					$(this).prop('panels.bottom', pnlToClone);
					$this._attachDivider(divClone, divToClone.prop('dividers.top'), 'bottom');
					$this._attachDivider(divClone, divToClone.prop('dividers.bottom'), 'top');
					$this._attachDivider(divClone, divToClone.prop('dividers.left'), 'right');
					$this._attachDivider(divClone, divToClone.prop('dividers.right'), 'left');
	
					$this._deattachDivider(divToClone);
					$this._attachDivider(divToClone, pnlToClone.prop('dividers.left'), 'right');
					$this._attachDivider(divToClone, pnlToClone.prop('dividers.right'), 'left');
					pnlToClone.parent().trigger($this.events.panel.clone, [pnlToClone, pnlClone]);
					$(this).prop('toClone', null);
					$(this).prop('clonePosition', null);
				}
				//Create clonning on right	
				if($(this).prop('clonePosition') == 'right'){
					$(this).css({
						top: pnlToClone.position().top - $this.settings.dividerTickness,
						height: pnlToClone.height() + 2*$this.settings.dividerTickness
					});
					
					//IMPORTANT: do this alwayse before constructing the panel!!!!
					divClone.prop('panels.right', $(this).prop('panels.right'));
					divClone.prop('panels.left', $(this).prop('panels.left').add(pnlClone).not(pnlToClone));
					divClone.prop('panels.left').each(function(i,e){
						$(e).prop('dividers.right', divClone)
					});
					if(divClone.prop('panels.right')){
						divClone.prop('panels.right').each(function(i,e){
							$(e).prop('dividers.left', divClone)
						});
					}
					var pnlClone = $this.createPanel($(this).parent(),{
						right: divClone,
						left: $(this),
						top: pnlToClone.prop('dividers.top'),
						bottom: pnlToClone.prop('dividers.bottom')
					},true, pnlToClone.data('panel.customData'));
					pnlClone.prop('originalState', {
						top: pnlClone.position().top,
						left: pnlClone.position().left,
						height: pnlClone.height(),
						width: pnlClone.width()
					});
					$(this).prop('panels.right', pnlClone);
					$(this).prop('panels.left', pnlToClone);
					$this._attachDivider(divClone, divToClone.prop('dividers.top'), 'bottom');
					$this._attachDivider(divClone, divToClone.prop('dividers.bottom'), 'top');
					$this._attachDivider(divClone, divToClone.prop('dividers.left'), 'right');
					$this._attachDivider(divClone, divToClone.prop('dividers.right'), 'left');
	
					$this._deattachDivider(divToClone);
					$this._attachDivider(divToClone, pnlToClone.prop('dividers.top'), 'bottom');
					$this._attachDivider(divToClone, pnlToClone.prop('dividers.bottom'), 'top');
					
					pnlToClone.parent().trigger($this.events.panel.clone, [pnlToClone, pnlClone]);
					$(this).prop('toClone', null);
					$(this).prop('clonePosition', null);
				}
			}
			
			
			
			//Resize attached panels
			var ldiff = ui.position.left - ui.originalPosition.left;
			var tdiff = ui.position.top - ui.originalPosition.top;
			var stopDragging = false;
			
			if($(this).is('.ui-panel-divider-h')){
				//triger resizeEvents for all panels first!
				$($(this).prop('panels.top')).each(function(i,el){
					var h = ui.position.top - $(el).prop('dividers.top').position().top - $this.settings.dividerTickness;
					var event = $.Event($this.events.panel.resize);
					$this.target.trigger(event, [$(el), {height:h, width:$(el).width()}]);
					stopDragging = stopDragging || event.result === false;
				});
				$($(this).prop('panels.bottom')).each(function(i,el){
					var h = $(el).prop('dividers.bottom').position().top - ui.position.top - $this.settings.dividerTickness;
					var event = $.Event($this.events.panel.resize);
					$this.target.trigger(event, [$(el), {height:h, width:$(el).width()}]);
					stopDragging = stopDragging || event.result === false;
				});
				if(stopDragging) ui.position.top = $(this).position().top;//return false;
				
				//do the actual resize
				$($(this).prop('panels.top')).each(function(i, el){
					var h = ui.position.top - $(el).prop('dividers.top').position().top - $this.settings.dividerTickness;
					$(el).css({height: h});
				});
				
				$($(this).prop('panels.bottom')).each(function(i, el){
					var h = $(el).prop('dividers.bottom').position().top - ui.position.top - $this.settings.dividerTickness;
					$(el).css({
						top: ui.position.top + $this.settings.dividerTickness,
						height: h
					});
				});				
			}
			
			if($(this).is('.ui-panel-divider-v')){
				//triger resizeEvents for all panels first!
				$($(this).prop('panels.left')).each(function(i,el){
				var w = ui.position.left - $(el).prop('dividers.left').position().left - $this.settings.dividerTickness;
					var event = $.Event($this.events.panel.resize);
					$this.target.trigger(event, [$(el), {height:$(el).height(), width:w}]);
					stopDragging = stopDragging || event.result === false;
				});
				$($(this).prop('panels.right')).each(function(i,el){
					var w = $(el).prop('dividers.right').position().left - ui.position.left - $this.settings.dividerTickness;
					var event = $.Event($this.events.panel.resize);
					$this.target.trigger(event, [$(el), {height:$(el).height(), width:w}]);
					stopDragging = stopDragging || event.result === false;
				});
				if(stopDragging) ui.position.left = $(this).position().left; //return false;
				//Do the actual resize
				$($(this).prop('panels.left')).each(function(i, el){
					var w = ui.position.left - $(el).prop('dividers.left').position().left - $this.settings.dividerTickness;
					$(el).css({width: w});
				});
				$($(this).prop('panels.right')).each(function(i, el){
					var w = $(el).prop('dividers.right').position().left - ui.position.left - $this.settings.dividerTickness;
					$(el).css({
						left: ui.position.left + $this.settings.dividerTickness,
						width: w
					});
				});
				
			}
			//if(stopDragging) return false;
			//Uncomment this if you want the deviders to be resized while dragging (not sure)
//			pnls._reposAttachedDividers($(this), ui);
		});
	}
	
	/**
	 *Bind drag stop event to dividers
	 *
	 */
	Panels.main.prototype.bindDragStopEvent = function(dividers){
		var $main = this;
		
		
		dividers.bind('dragstop', function(e, ui){
			
			$main._reposAttachedDividers($(this), ui);
			
			var $this = $(this);
			//check for combaining properties
			if($this.is('.ui-panel-divider-h')){
				$('.ui-panel-divider-h').not($this).each(function(i,e){
					var $e = $(e);
					if($e.position().top == $this.position().top && (
						Math.abs($e.position().left+$e.width() - $this.position().left) < 10 ||
						Math.abs($this.position().left+$this.width() - $e.position().left) < 10
					)){
						//combine panels
						if($e.prop('panels.top'))	$e.prop('panels.top').prop('dividers.bottom', $this);
						$main._combineProps('panels.top', $this, $e);
						if($e.prop('panels.bottom'))	$e.prop('panels.bottom').prop('dividers.top', $this);
						$main._combineProps('panels.bottom', $this, $e);
						
						$main._attachDivider($this, $e.prop('dividers.top'), 'bottom');
						$main._attachDivider($this, $e.prop('dividers.bottom'), 'top');
						
						if($this.position().left < $e.position().left){
							$this.width($this.width() + $e.width() - $main.settings.dividerTickness);
							$main._deattachDividerFrom($this, 'right');
							$main._attachDivider($this, $e.prop('dividers.right'), 'left');
							
						}else{
							$this.width($this.width() + $e.width() - $main.settings.dividerTickness);
							$this.css({left: $e.position().left});
							$main._deattachDividerFrom($this, 'left');
							$main._attachDivider($this, $e.prop('dividers.left'), 'right');
							
						}
						$main._deattachDivider($e);
						$e.remove();
						$this.effect('highlight');
					}
				})
			}
			if($this.is('.ui-panel-divider-v')){
				$('.ui-panel-divider-v').not($this).each(function(i,e){
					var $e = $(e);
					if($e.position().left == $this.position().left && (
						Math.abs($e.position().top+$e.height() - $this.position().top) < 10 ||
						Math.abs($this.position().top+$this.height() - $e.position().top) < 10
					)){
						//combine panels
						if($e.prop('panels.left'))	$e.prop('panels.left').prop('dividers.right', $this);
						$main._combineProps('panels.left', $this, $e);
						if($e.prop('panels.right'))	$e.prop('panels.right').prop('dividers.left', $this);
						$main._combineProps('panels.right', $this, $e);
						
						$main._attachDivider($this, $e.prop('dividers.left'), 'right');
						$main._attachDivider($this, $e.prop('dividers.right'), 'left');
						
						if($this.position().top < $e.position().top){
							$this.height($this.height() + $e.height() - $main.settings.dividerTickness);
							$main._deattachDividerFrom($this, 'bottom');
							$main._attachDivider($this, $e.prop('dividers.bottom'), 'top');
						}else{
							$this.height($this.height() + $e.height() - $main.settings.dividerTickness);
							$this.css({top: $e.position().top});
							$main._deattachDividerFrom($this, 'top');
							$main._attachDivider($this, $e.prop('dividers.top'), 'bottom');
						}
						$e.remove();
						$this.effect('highlight');
					}
				})
			}
			
			$('.ui-panel-handle-container-active').removeClass('ui-panel-handle-container-active');
		});	
	}
	/**
	 * Creates a new divider
	 */
	Panels.main.prototype.createDivider = function(orientation, geometry){
		var d = $('<div/>')
			.addClass('ui-panel-divider ui-panel-divider-'+orientation)
			.css({position: 'absolute'})
			.css({
				left:geometry.left,
				top:geometry.top,
				width:geometry.width,
				height:geometry.height			
			});
		
		this._initDraggable(d,orientation);
		
		this.bindDragStartEvent(d);
		this.bindDragEvent(d);
		this.bindDragStopEvent(d);
		return d;
	}
	/**
	 * Creates a new panel in <container> and attaches it to the <dividers>
	 */
	Panels.main.prototype.createPanel = function(container, dividers, isCloning, customData){
		var $this = this;
		var w = dividers.right.position().left - dividers.left.position().left;
		w = w-this.settings.dividerTickness>0?w-this.settings.dividerTickness:0;
		var h = dividers.bottom.position().top - dividers.top.position().top;
		h = h-this.settings.dividerTickness>0?h-this.settings.dividerTickness:0;
		

		var pnl = $('<div>').addClass('ui-widget ui-widget-content ui-panel').css({
			top: dividers.top.position().top + this.settings.dividerTickness,
			left: dividers.left.position().left + this.settings.dividerTickness,
			width: w,
			height: h
			
		}).appendTo(container);
		
		$('<div>').addClass('ui-widget-header ui-panel-header').text('Panel').appendTo(pnl);
		$('<div>').addClass('ui-panel-content').appendTo(pnl);
		
		pnl.prop('dividers.top', dividers.top);
		if(dividers.top.prop('panels.bottom')){
			dividers.top.prop('panels.bottom', dividers.top.prop('panels.bottom').add(pnl));
		}else{
			dividers.top.prop('panels.bottom', pnl);
		}
		pnl.prop('dividers.bottom', dividers.bottom);
		if (dividers.bottom.prop('panels.top')) {
			dividers.bottom.prop('panels.top', dividers.bottom.prop('panels.top').add(pnl))
		} else {
			dividers.bottom.prop('panels.top', pnl);
		}
		
		
		
		pnl.prop('dividers.left', dividers.left);
		if (dividers.left.prop('panels.right')) {
			dividers.left.prop('panels.right', dividers.left.prop('panels.right').add(pnl))
		} else {
			dividers.left.prop('panels.right', pnl);
		}
		
		pnl.prop('dividers.right', dividers.right);
		if (dividers.right.prop('panels.left')) {
			dividers.right.prop('panels.left', dividers.right.prop('panels.left').add(pnl))
		} else {
			dividers.right.prop('panels.left', pnl);
		}
		
		var handlesHolder = $('<div>').addClass('ui-panel-handle-container').appendTo(pnl);
		$('<div>').addClass('ui-panel-handle ui-panel-handle-h').appendTo(handlesHolder)
		.mousedown(function(e){
			pnl.prop('dividers.right').prop('toClone', pnl);
			pnl.prop('dividers.right').prop('clonePosition', 'right');
			pnl.prop('dividers.right').trigger(e);
			$(this).parent().addClass('ui-panel-handle-container-active');
		})
		
		$('<div>').addClass('ui-panel-handle ui-panel-handle-v').appendTo(handlesHolder)
		.mousedown(function(e){
			pnl.prop('dividers.top').prop('toClone', pnl);
			pnl.prop('dividers.top').prop('clonePosition', 'top');
			pnl.prop('dividers.top').trigger(e);
			$(this).parent().addClass('ui-panel-handle-container-active');
		});
		$('<div>').addClass('ui-panel-handle ui-panel-handle-combine').appendTo(handlesHolder)
		.draggable({
			revert: true,
			revertDuration: 0,
			drag: function(event, ui){
				return $this._combinerOnDrag($(this), event, ui);
			},
			stop: function(event,ui){
				return $this._combinerOnDragStop($(this), event, ui);
			}
		}).mousedown(function(){
				
				$('.ui-panel').not(pnl).each(function(i,el){
					if(
						(
							pnl.prop('dividers.right').is($(el).prop('dividers.left')) &&
							pnl.position().top == $(el).position().top &&
							pnl.height() == $(el).height()
						) ||
						(
							pnl.prop('dividers.top').is($(el).prop('dividers.bottom')) &&
							pnl.position().left == $(el).position().left &&
							pnl.width() == $(el).width()
						)
					){
						var d = $('<div/>')
							.addClass('ui-panel-combiner-helper-text')
							.text('Drag the X button over this panel')
							.appendTo($('body'));
							
						d.css({
							top: $(el).position().top + ($(el).height() - d.height())/2,
							left: $(el).position().left + ($(el).width() - d.width())/2
						}).show();
					}
				})
				
		}).mouseup(function(){
				$('.ui-panel-combiner-helper-text').fadeOut('slow',function(){$(this).remove()});
		});
		
		pnl.data('panel.customData', customData);
		
		if(!isCloning) isCloning = false;
		this.target.trigger(this.events.panel.create, [pnl, isCloning]);
		return pnl;
		
	}
	/**
	 * Initializes jQuery Draggable object
	 */
	Panels.main.prototype._initDraggable = function(selector, orientation){
		var axis, cursor;
		if(orientation == 'h'){
			axis = 'y';
			cursor = 's-resize';
		}else{
			axis = 'x';
			cursor = 'w-resize';
		}
		$(selector).draggable({
			axis: axis,
			containment: 'parent',
			cursor: cursor,
			grid: [4,4]
		});
	}
	Panels.main.prototype._attachPanelToDivider = function(panel, toDivider, side){
		var revSide = this._getReverseDirection(side);
		panel.prop('dividers.'+revSide, toDivider);
		toDivider.prop('panels.'+side, $(toDivider.prop('panels.'+side)).add(panel));
	}
	/**
	 * Attach a divider to other dividers on their <side> side
	 */
	Panels.main.prototype._attachDivider = function(newDivider, toDividers, side){
		var revSide = this._getReverseDirection(side);

		if(toDividers){
			if(toDividers.prop('dividers.'+side)){
				toDividers.prop('dividers.'+side, toDividers.prop('dividers.'+side).add(newDivider));
			}else{
				toDividers.prop('dividers.'+side, newDivider);
			}
		}
		if(newDivider){
			if(newDivider.prop('dividers.'+revSide)){
				newDivider.prop('dividers.'+revSide, newDivider.prop('dividers.'+revSide).add(toDividers));
			}else{
				newDivider.prop('dividers.'+revSide, toDividers);
			}
		}
	}
	/**
	 * Deattach a divider from its neighbor dividers
	 */
	Panels.main.prototype._deattachDivider = function(divider){
		
		this._deattachDividerFrom(divider, 'left');
		this._deattachDividerFrom(divider, 'right');
		this._deattachDividerFrom(divider, 'top');
		this._deattachDividerFrom(divider, 'bottom');
	}
	/**
	 * Deattach divider's <side> side from all attached other dividers
	 */
	Panels.main.prototype._deattachDividerFrom = function(divider, side){
		var revSide = this._getReverseDirection(side);
		var attachedTo = divider.prop('dividers.'+side);
		var onSameSide;
		if(attachedTo){
			attachedTo.each(function(){
				onSameSide = $(this).prop('dividers.'+revSide);
				if(onSameSide){
					$(this).prop('dividers.'+revSide, onSameSide.not(divider))
				}
			})
		}
		divider.prop('dividers.'+side, null);
	}
	/**
	 * Calculates reverce keyword for a given dir
	 * Example 'left' will produce 'right', 'h' will produce 'v', etc...
	 */
	Panels.main.prototype._getReverseDirection = function(dir){
		var revDir;
		if(dir == 'top') revDir = 'bottom';
		if(dir == 'bottom') revDir = 'top';
		if(dir == 'left') revDir = 'right';
		if(dir == 'right') revDir = 'left';
		if(dir == 'h') revDir = 'v';
		if(dir == 'v') revDir = 'h';
		return revDir;
	}
	/**
	 * Repositions all dividers which are attached to a specific one which has been moved
	 */
	Panels.main.prototype._reposAttachedDividers = function(divider, ui){
		var delta;
		var $main = this;
		$(divider.prop('dividers.top')).not('.ui-panel-divider-h').each(function(){
			$(this).height(divider.position().top - $(this).position().top + $main.settings.dividerTickness);
		});
		$(divider.prop('dividers.bottom')).not('.ui-panel-divider-h').each(function(){
			delta = divider.position().top - $(this).position().top;
			$(this).css({top: divider.position().top})
			$(this).height($(this).height() - delta);
		});
		$(divider.prop('dividers.left')).not('.ui-panel-divider-v').each(function(){
			$(this).width(divider.position().left - $(this).position().left + $main.settings.dividerTickness);
		});
		$(divider.prop('dividers.right')).not('.ui-panel-divider-v').each(function(){
			delta = divider.position().left - $(this).position().left;
			$(this).css({left: divider.position().left})
			$(this).width($(this).width() - delta);
		});
	}
	
	Panels.main.prototype._combinerOnDrag = function(combiner, event, ui){
		
		this.target.find('.ui-panel').css({opacity: 1});
		combiner.parent().addClass('ui-panel-handle-container-active');
		$('body').css({cursor: 'crosshair'});
		$('.ui-panel-combiner-helper').hide();
		combiner.prop('panel.source', null);
		combiner.prop('panel.target', null);
		
		
		var targetPanel, sourcePanel;
		var tmp;
		var p = $(this._findPanelsOnXY(event.pageX, event.pageY)[0]);
		var goodCandidate = false;
		var helperDirection = '';
		var helperOffset = {};
		sourcePanel = combiner.closest('.ui-panel');
		combiner.removeClass('ui-panel-handle-combine-h ui-panel-handle-combine-v')
		if(p){
			if(
				sourcePanel.prop('dividers.right').is(p.prop('dividers.left')) &&
				p.position().top == sourcePanel.position().top && p.height() == sourcePanel.height()
			){
				targetPanel = p;
				combiner.addClass('ui-panel-handle-combine-h');
				goodCandidate = true;
				helperDirection = 'h';
				helperOffset = {top: 40, left: -27};
				combiner.prop('panel.lastDirection', 'h');
			}
			if(
				sourcePanel.prop('dividers.top').is(p.prop('dividers.bottom')) &&
				p.position().left == sourcePanel.position().left && p.width() == sourcePanel.width()
			){
				targetPanel = p;
				combiner.addClass('ui-panel-handle-combine-v');
				goodCandidate = true;
				helperDirection = 'v';
				helperOffset = {top: -20, left: -60};
				combiner.prop('panel.lastDirection', 'v');
			}
		}
		goodCandidate = goodCandidate || sourcePanel.is(p);
		if(targetPanel){
			combiner.prop('panel.lastTarget', targetPanel)
		}else{
			if(combiner.prop('panel.lastTarget') && goodCandidate){
				targetPanel = combiner.prop('panel.lastTarget');
				combiner.addClass('ui-panel-handle-combine-'+combiner.prop('panel.lastDirection'))
				helperDirection = combiner.prop('panel.lastDirection');
				//goodCandidate = true;
			}
		}
		if(targetPanel && goodCandidate){
			var opposite;
			if(sourcePanel.is(p)){
				tmp = sourcePanel;
				sourcePanel = targetPanel;
				targetPanel = tmp;
				opposite = 'opposite';
			}
			$(sourcePanel).css({opacity: 0.5});
			$(targetPanel).css({opacity: 0.5});
			$('.ui-panel-combiner-helper')
				.removeClass('ui-panel-combiner-helper- ui-panel-combiner-helper-h ui-panel-combiner-helper-v opposite')
				.addClass('ui-panel-combiner-helper-'+helperDirection)
				.addClass(opposite)
				.css({
					top: sourcePanel.position().top + helperOffset.top,
					left: sourcePanel.position().left + sourcePanel.width() + helperOffset.left
				})
				.show();
			combiner.prop('panel.source', sourcePanel);
			combiner.prop('panel.target', targetPanel);
		}
		
	}
	
	Panels.main.prototype._combinerOnDragStop = function(combiner, event, ui){
		
		this.target.find('.ui-panel').css({opacity: 1});
		combiner.parent().removeClass('ui-panel-handle-container-active');
		
		$('body').css({cursor: 'default'});
		$('.ui-panel-combiner-helper').fadeOut();
		$('.ui-panel-combiner-helper-text').remove();
		
		var sourcePanel = combiner.prop('panel.source');
		var targetPanel = combiner.prop('panel.target');
		
		var oposite = combiner.closest('.ui-panel').is(targetPanel);
		var divider;
		var $this = this;
		var p, p1, _d, _d1, d, d1;
		if(targetPanel){
			if(combiner.is('.ui-panel-handle-combine-h')){
				if(oposite){
					divider = targetPanel.prop('dividers.right');
				}else{
					divider = targetPanel.prop('dividers.left');
				}
				p = divider.prop('panels.left').not(sourcePanel).not(targetPanel);
				p1 = divider.prop('panels.right').not(sourcePanel).not(targetPanel);
				if(!divider.prop('dividers.top').is(targetPanel.prop('dividers.top'))){
					_d = $this.createDivider('v',{
						top: divider.position().top,
						left: divider.position().left,
						width: $this.settings.dividerTickness,
						height:targetPanel.prop('dividers.top').position().top - divider.position().top + $this.settings.dividerTickness
					});
					$this._initDraggable()
					$this._attachDivider(_d, $(divider.prop('dividers.top')), 'bottom');
					$this._attachDivider(_d, $(targetPanel.prop('dividers.top')), 'top');
					_d.appendTo($this.target);
				}
				if(!divider.prop('dividers.bottom').is(targetPanel.prop('dividers.bottom'))){
					_d1 = $this.createDivider('v',{
						top: targetPanel.prop('dividers.bottom').position().top,
						left: divider.position().left,
						width: $this.settings.dividerTickness,
						height: divider.prop('dividers.bottom').position().top - targetPanel.prop('dividers.bottom').position().top + $this.settings.dividerTickness
					});
					$this._attachDivider(_d1, divider.prop('dividers.bottom'), 'top');
					$this._attachDivider(_d1, targetPanel.prop('dividers.bottom'), 'bottom');
					_d1.appendTo($this.target);
				}
				$(divider.prop('dividers.left')).each(function(i,el){
					if($(el).position().top < targetPanel.position().top){
						if(_d) $this._attachDivider(_d, $(el), 'right')
					}else{
						if(_d1) $this._attachDivider(_d1, $(el), 'right')
					}
				});
				$(divider.prop('dividers.right')).each(function(i,el){
					if($(el).position().top < targetPanel.position().top){
						if(_d) $this._attachDivider(_d, $(el), 'left')
					}else{
						if(_d1) $this._attachDivider(_d1, $(el), 'left')
					}
				});
				$(p).each(function(i,el){
					p.prop('dividers.right', $(p.prop('dividers.right').not(divider)));
					if($(el).position().top < targetPanel.position().top){
						if(_d) $this._attachPanelToDivider($(el), _d, 'left')
					}else{
						if(_d1) $this._attachPanelToDivider($(el), _d1, 'left')
					}
				});
				$(p1).each(function(i,el){
					p.prop('dividers.left', $(p.prop('dividers.left').not(divider)));
					if($(el).position().top < targetPanel.position().top){
						if(_d) $this._attachPanelToDivider($(el), _d, 'right');
					}else{
						if(_d1) $this._attachPanelToDivider($(el), _d1, 'right');
					}
				})
				$this._deattachDivider(divider);
				divider.remove();
			}else{
				if(oposite){
					divider = targetPanel.prop('dividers.top');
				}else{
					divider = targetPanel.prop('dividers.bottom');
				}
				p = divider.prop('panels.bottom').not(sourcePanel).not(targetPanel);
				p1 = divider.prop('panels.top').not(sourcePanel).not(targetPanel);
				if(!divider.prop('dividers.right').is(targetPanel.prop('dividers.right'))){
					_d1 = $this.createDivider('h',{
						top: divider.position().top,
						left: targetPanel.prop('dividers.right').position().left,
						height: $this.settings.dividerTickness,
						width: -targetPanel.prop('dividers.right').position().left + divider.prop('dividers.right').position().left + $this.settings.dividerTickness
					});
					$this._attachDivider(_d1, $(divider.prop('dividers.right')), 'left');
					$this._attachDivider(_d1, $(targetPanel.prop('dividers.right')), 'right');
					_d1.appendTo($this.target);
				}
				if(!divider.prop('dividers.left').is(targetPanel.prop('dividers.left'))){
					_d = $this.createDivider('h',{
						top: divider.position().top,
						left: divider.prop('dividers.left').position().left,
						height: $this.settings.dividerTickness,
						width: targetPanel.prop('dividers.left').position().left - divider.prop('dividers.left').position().left + $this.settings.dividerTickness
					});
					$this._attachDivider(_d, divider.prop('dividers.left'), 'right');
					$this._attachDivider(_d, targetPanel.prop('dividers.left'), 'left');
					_d.appendTo($this.target);
				}
				
				$(divider.prop('dividers.bottom')).each(function(i,el){
					if($(el).position().left < targetPanel.position().left){
						if(_d) $this._attachDivider(_d, $(el), 'top')
					}else{
						if(_d1) $this._attachDivider(_d1, $(el), 'top')
					}
				});
				$(divider.prop('dividers.top')).each(function(i,el){
					if($(el).position().left < targetPanel.position().left){
						if(_d) $this._attachDivider(_d, $(el), 'bottom')
					}else{
						if(_d1) $this._attachDivider(_d1, $(el), 'bottom')
					}
				});
				$(p).each(function(i,el){
					p.prop('dividers.top', $(p.prop('dividers.top').not(divider)));
					if($(el).position().left < targetPanel.position().left){
						if(_d) $this._attachPanelToDivider($(el), _d, 'bottom')
					}else{
						if(_d1) $this._attachPanelToDivider($(el), _d1, 'bottom')
					}
				});
				$(p1).each(function(i,el){
					p.prop('dividers.bottom', $(p.prop('dividers.top').not(divider)));
					if($(el).position().left < targetPanel.position().left){
						if(_d) $this._attachPanelToDivider($(el), _d, 'top');
					}else{
						if(_d1) $this._attachPanelToDivider($(el), _d1, 'top');
					}
				})
				$this._deattachDivider(divider);
				divider.remove();
			}
			//do stuff with the panels
			
			if(combiner.is('.ui-panel-handle-combine-h')){
				sourcePanel.width(sourcePanel.width()+targetPanel.width()+$this.settings.dividerTickness)
				if(targetPanel.position().left < sourcePanel.position().left){
					sourcePanel.css({left: targetPanel.position().left});
					d = targetPanel.prop('dividers.left');
					d.prop('panels.right', $(d.prop('panels.right')).add(sourcePanel).not(targetPanel));
					sourcePanel.prop('dividers.left', d);
				}else{
					d = targetPanel.prop('dividers.right');
					console.log(d);
					d.prop('panels.left', $(d.prop('panels.left')).add(sourcePanel).not(targetPanel));
					sourcePanel.prop('dividers.right', d);
				}
			}else{
				sourcePanel.height(sourcePanel.height()+targetPanel.height()+$this.settings.dividerTickness)
				if(targetPanel.position().top < sourcePanel.position().top){
					sourcePanel.css({top: targetPanel.position().top});
					d = targetPanel.prop('dividers.top');
					
					d.prop('panels.bottom', $(d.prop('panels.bottom')).add(sourcePanel).not(targetPanel));
					
					sourcePanel.prop('dividers.top', d);
				}else{
					d = targetPanel.prop('dividers.bottom');
					d.prop('panels.top', $(d.prop('panels.top')).add(sourcePanel).not(targetPanel));
					sourcePanel.prop('dividers.bottom', d);
				}
			}
			//deattach targetPanel from all dividers
			d = targetPanel.prop('dividers.top');
			d.prop('panels.bottom', $(d.prop('panels.bottom')).not(targetPanel));
			d = targetPanel.prop('dividers.right');
			d.prop('panels.left', $(d.prop('panels.left')).not(targetPanel));
			d = targetPanel.prop('dividers.bottom');
			d.prop('panels.top', $(d.prop('panels.top')).not(targetPanel));
			d = targetPanel.prop('dividers.left');
			d.prop('panels.right', $(d.prop('panels.right')).not(targetPanel));
			targetPanel.remove();
		}
		combiner.prop('panel.source', null);
		combiner.prop('panel.target', null);
		combiner.prop('panel.lastTarget', null);
		combiner.prop('panel.lastDirection', null);
	} 
	
	/**
	 * Combine properties of <first> element with props of the <second>
	 * elsment and stores them in to the <first>
	 */
	Panels.main.prototype._combineProps = function(name, first, second){
		if(first.prop(name)){
			first.prop(name, first.prop(name).add(second.prop(name)));
		}else{
			first.prop(name, second.prop(name));
		}
	}
	
	Panels.main.prototype._findPanelsOnXY = function(x, y) {
    var $elements = $('.ui-panel').map(function() {
        var $this = $(this);
        var offset = $this.offset();
        var l = offset.left;
        var t = offset.top;
        var h = $this.height();
        var w = $this.width();

        var maxx = l + w;
        var maxy = t + h;

        return (y <= maxy && y >= t) && (x <= maxx && x >= l) ? $this : null;
    });

    return $elements;
}
	/*
	 * *******************
	 * Dealing with jQuery
	 * *******************
	 */
	
	//Panel medhods accesible via jQuery object
	Panels.methods = {
		init: function(options){
			return this.each(function() {
        $(this).data('panelObject', new Panels.main($(this), options));
			});
		},
		state: function(stateObj){
			
			if(typeof stateObj === 'object'){
				this.data('panelObject').setState(stateObj);
				return this
			}else{
				return this.data('panelObject').getState();
			}
		}
	}
	//Extending jQuery
	$.fn.panels = function(method) {
    // Method calling logic
    if ( Panels.methods[method] ) {
      return Panels.methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
    } else if ( typeof method === 'object' || ! method ) {
      return Panels.methods.init.apply( this, arguments );
    } else {
      $.error( 'Method ' +  method + ' does not exist on jQuery.panels' );
    }    
	};
})(jQuery)