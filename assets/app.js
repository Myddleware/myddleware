//CSS
require('./app.scss');

let $ = require('jquery');// create global $ and jQuery variables
global.$ = global.jQuery = $;

// Import FOSJs routing
const routes = require('../public/js/fos_js_routes.json');
import Routing from '../vendor/friendsofsymfony/jsrouting-bundle/Resources/public/js/router.min.js';
// Configuration FOSJs routing
Routing.setRoutingData(routes);
// Set Routing global
global.Routing = Routing;
global.lang = $('html').attr('lang');
global.path_img = '/build/images/';

require('./vendors/jquery-ui/jquery-ui.min.js')
require('./vendors/bootstrap/js/bootstrap.min.js')
require('./js/lib/jquery_onoff/jquery.onoff.min.js')
require('./js/lib/jquery_fancybox/jquery.fancybox.pack.js')
require('./js/lib/jquery_scrollbox/jquery.scrollbox.min.js')
require('./js/lib/jquery-ui-timepicker-addon/jquery-ui-timepicker-addon.js')
require('./js/lib/jquery_qtip/jquery.qtip.min.js')
require('./js/lib/jquery_myddleware/function.js')
require('./js/regle.js')
require('./js/account.js')
require('./js/lib/d3.v2.js')
require('./js/home.js')
require('./js/jobscheduler.js')
require('./js/fiche.js')
require('./js/jcarousel.ajax.js')
require('./js/animation.js')

// start the Stimulus application
import './bootstrap';
