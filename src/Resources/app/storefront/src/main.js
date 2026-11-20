// MiniwaldExplorer Storefront Entry Point
// ========================================
import ObservationWizardPlugin from './js/observation-wizard/observation-wizard.plugin';
import NavMenuPlugin from './js/nav-menu/nav-menu.plugin';
import MiniwaldDetailMapPlugin from './js/miniforest-detail-map/miniforest-detail-map.plugin';
import LightboxPlugin from './js/lightbox/lightbox.plugin';
import PasswordTogglePlugin from './js/password-toggle/password-toggle.plugin';

const PluginManager = window.PluginManager;

PluginManager.register(
    'MiniwaldObservationWizard',
    ObservationWizardPlugin,
    '[data-observation-wizard]'
);
PluginManager.register('MiniwaldNavMenu', NavMenuPlugin, '[data-miniwald-nav]');
PluginManager.register(
    'MiniwaldDetailMap',
    MiniwaldDetailMapPlugin,
    '[data-miniwald-detail-map]'
);
PluginManager.register('MiniwaldLightbox', LightboxPlugin, '[data-lightbox]');
PluginManager.register(
    'MiniwaldPasswordToggle',
    PasswordTogglePlugin,
    '[data-password-toggle]'
);
