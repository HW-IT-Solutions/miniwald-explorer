/**
 * Hide e-commerce related top-level admin menu entries.
 *
 * Since this is a Citizen Science app (not an e-commerce shop), we hide
 * the Shopware core menu entries for Catalogues, Orders, and Marketing.
 */

// Use a small timeout to ensure all modules are registered before we modify them
// This runs after all module index.js files have been imported
Shopware.Component.override('sw-admin-menu', {
    computed: {
        mainMenuEntries() {
            // Get the original entries from the parent
            const entries = this.$super('mainMenuEntries');

            // filtered top level entries
            const hiddenTopLevelIds = ['sw-order', 'sw-marketing'];
            // kept catalogue child entries
            const catalogueId = 'sw-catalogue';
            const keepCatalogueChildIds = ['sw-category'];

            // filter entries — use spread/clone to avoid mutating reactive objects in a computed
            return (
                entries
                    // top level
                    .filter((entry) => !hiddenTopLevelIds.includes(entry.id))
                    // catalogue children — spread to avoid mutating the original reactive entry
                    .map((entry) => {
                        if (entry.id === catalogueId && entry.children) {
                            return {
                                ...entry,
                                children: entry.children.filter((child) =>
                                    keepCatalogueChildIds.includes(child.id)
                                ),
                            };
                        }
                        return entry;
                    })
            );
        },
    },
});
