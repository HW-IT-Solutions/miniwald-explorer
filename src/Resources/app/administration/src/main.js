// Import styles
import './module/miniwald-explorer/miniwald-explorer.scss';

// Import navigation shell first - registers top-level menu entries as type:'core'
import './module/miniwald-navigation';
// Import main plugin module - registers routes and child navigation entries
import './module/miniwald-explorer';

// Import Shopware core module overrides (customer list/detail customizations)
import './module/sw-customer';

// Hide e-commerce menu entries (Catalogues, Orders, Marketing)
import './module/hide-ecommerce-menu';

// Register search ranking fields for custom entities (must be before sw-search-bar)
import './module/search-ranking-config';

// Customize admin search bar: add custom entities, remove e-commerce types
import './module/sw-search-bar';

// Override search bar item component to provide detail routes and display values
import './module/sw-search-bar-item';

import './module/sw-dashboard';
