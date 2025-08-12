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

require('./js/lib/d3.v2.js')
require('./js/jcarousel.ajax.js')
require('./js/animation.js')
require('./js/task.js')
require('./js/connector.js')
require('./js/rule_relation_filter.js')
require('./js/regle.js')
require('./js/historique.js')
require('./js/mappingRule.js')
require('./js/rule-group.js')


if (window.location.href.includes('rule/document/list')) {
    require('./js/filter.js');
}

if (window.location.href.includes('workflowAction') || window.location.href.includes('workflow')) {
    require('./js/workflows.js')
    require('./js/editAction.js')
}
if (window.location.href.includes('workflow/show')) {
    require('./js/workflow-actions-collapse.js')
    require('./js/workflow-logs-collapse.js')
    require('./js/workflow-toggle-detail.js')
    require('./js/workflow-action-toggle-list-inside-workflow-show.js')
}

if (window.location.href.includes('workflowAction/showAction')) {
    require('./js/workflow-action-toggle-detail.js')
}

if (window.location.href.includes('workflow/list')) {
    require('./js/workflow-toggle-list.js')
    require('./js/workflowsearchworkflowname.js')
}

if (window.location.href.includes('rule/view')) {
    require('./js/rule-detail.js')
    require('./js/workflow-toggle-list.js')
}

// if windows loction includes rule/account
if (window.location.href.includes('rule/account')) {
    require('./js/account.js')
}

if (window.location.href.includes('rule/jobscheduler/crontab_list')) {
    require('./js/crontab-list-toggle.js')
}


if (window.location.href.includes('rule/panel') || window.location.href.includes('premium/list')) {
    require('./js/home.js')
}

if (window.location.href.includes('rule/jobscheduler')) {
require('./js/crontab.js')
require('./js/jobscheduler.js')
}

if (window.location.href.includes('rule/managementsmtp')) {
    require('./js/smtp.js')
}

if (window.location.href.match(/rule\/connector\/(\d+\/detail|view\/\d+)/)) {
    require('./js/connector_detail.js')
}

if (window.location.href.includes('workflowAction/new') || window.location.href.includes('workflowAction/editWorkflowAction')) {
    require('./js/workflowActionSearchFields.js')
    require('./js/workflow-action-label-rule-change.js')
}


if (!(window.location.href.includes('install'))) {
    require('./js/historique.js')
}


if (window.location.href.includes('flux')) {
    require('./js/imagemousehoverbutton.js')
}

if (window.location.href.includes('rule/flux')) {
    require('./js/lookup-filter.js')
}

if (window.location.href.includes('rule/user_manager')) {
    require('./js/user-manager.js')
}
if (window.location.href.includes('flux/modern')) {
    require('./js/document-detail/document-detail.js')
}

// start the Stimulus application
import 'select2/dist/css/select2.css';
import 'select2';

