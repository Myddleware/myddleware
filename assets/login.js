//CSS
require('./login.scss');

// Import FOSJs routing
const routes = require('../public/js/fos_js_routes.json');
import Routing from '../vendor/friendsofsymfony/jsrouting-bundle/Resources/public/js/router.min.js';
// Configuration FOSJs routing
Routing.setRoutingData(routes);
// Set Routing global
global.Routing = Routing;

global.path_img = '/build/images/';
let $ = require('jquery');// create global $ and jQuery variables
global.$ = global.jQuery = $;
require('bootstrap')
require('@fortawesome/fontawesome-free/js/all')
require('jquery-ui')
require('jquery-ui/ui/widgets/tabs')
require('jquery-ui/ui/widgets/accordion')
require('jquery-ui/ui/widgets/draggable')
require('jquery-ui/ui/widgets/droppable')
require('jquery-ui/ui/widgets/sortable')
require('jquery-ui/ui/widgets/dialog')
require('jquery-ui/ui/tabbable')
require('./js/lib/jquery_fancybox/jquery.fancybox.pack.js')
require('./js/lib/jquery_scrollbox/jquery.scrollbox.min.js')
require('./js/lib/jquery_qtip/jquery.qtip.min.js')
require('./js/lib/jquery_myddleware/function.js')
require('./js/regle.js')
require('./js/login.js')
