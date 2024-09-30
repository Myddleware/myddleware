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
global.path_img = 'build/images/';

require('jquery-ui')
require('jquery-ui/ui/widgets/tabs')
require('jquery-ui/ui/widgets/accordion')
require('jquery-ui/ui/widgets/draggable')
require('jquery-ui/ui/widgets/droppable')
require('jquery-ui/ui/widgets/sortable')
require('jquery-ui/ui/widgets/dialog')
require('jquery-ui/ui/tabbable')
require('bootstrap')
require('@fortawesome/fontawesome-free/js/all')
require('./vendors/dtsel/dtsel')
require('google-charts/dist/googleCharts')
require('./js/lib/jquery_fancybox/jquery.fancybox.pack.js')
require('./js/lib/jquery_scrollbox/jquery.scrollbox.min.js')
require('./js/lib/jquery_qtip/jquery.qtip.min.js')
require('./js/lib/jquery_myddleware/function.js')
require('./js/regle.js')
require('./js/account.js')
require('./js/lib/d3.v2.js')
require('./js/home.js')
require('./js/jobscheduler.js')
require('./js/jcarousel.ajax.js')
require('./js/animation.js')
require('./js/task.js')
require('./js/connector.js')
require('./js/smtp.js')
require('./js/filter.js')
require('./js/crontab.js')
require('./js/rule_relation_filter.js')
require('./js/editAction.js')
require('./js/connector_detail.js')
require("./js/workflows.js");



// start the Stimulus application
import './bootstrap';
import 'select2/dist/css/select2.css';
import 'select2';

