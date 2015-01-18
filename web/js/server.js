 server = {
    endpoints: {},
    
    /**
     * Generates url from Cosmos.endponits templates
     * This method do not preform any validations. It just replaces any {placeholder} with its value from params
     */
    url: function(name, params) {
        var rule = this.endpoints[name];
        
        if (!params) params = {};
        
        if (rule) {
            var u = rule.url;
            for (var p in params) {
                u = u.replace(':' + p, params[p]);
            }
            return u;
        } else return '';
    },
    
    /**
     * Preforms ajax post request
     */
    post: function(route, postParams, callback) {
        var url;
        if (typeof route == 'object') {
            url = this.url(route.name, route.params);
        } else {
            url = this.url(route);
        }
        
        var ajax = document.getElementById('main-ajax');
        var listener = function(e) {
            ajax.removeEventListener('core-response', listener, true);
            if (typeof callback == 'function') {
                callback(e);
            }
        }
        ajax.addEventListener('core-response', listener, true);
        ajax.method = 'post';
        ajax.url = url;
        ajax.params = postParams;
        ajax.go();
    },
    
    /**
     * preforms ajax get request
     */
    get: function(route, getParams, callback) {
        var url;
        if (typeof route == 'object') {
            url = this.url(route.name, route.params);
        } else {
            url = this.url(route);
        }
        
        var q = '';
        for (var i in getParams) {
            q += '&' + i + '=' + getParams[i];
        }
        
        var ajax = document.getElementById('main-ajax');
        var listener = function(e) {
            ajax.removeEventListener('core-response', listener, true);
            if (typeof callback == 'function') {
                callback(e);
            }
        }
        ajax.addEventListener('core-response', listener, true);
        ajax.method = 'post';
        ajax.params = {};
        ajax.url = url + '?' + q;
        ajax.go();
    },
}