const { Criteria } = Shopware.Data;

export const mediaFolderMixin = {
    data() {
        return {
            mediaFolderCache: {},
        };
    },

    computed: {
        mediaFolderRepository() {
            return this.repositoryFactory.create('media_folder');
        },
    },

    methods: {
        /**
         * Get media folder ID by path (e.g., "MiniwaldExplorer/Plants")
         * Results are cached to avoid repeated API calls
         */
        async getMediaFolderId(folderPath) {
            if (this.mediaFolderCache[folderPath]) {
                return this.mediaFolderCache[folderPath];
            }

            const parts = folderPath.split('/');
            let currentFolder = null;

            for (const part of parts) {
                const criteria = new Criteria();
                criteria.addFilter(Criteria.equals('name', part));

                if (currentFolder) {
                    criteria.addFilter(
                        Criteria.equals('parentId', currentFolder.id)
                    );
                } else {
                    criteria.addFilter(Criteria.equals('parentId', null));
                }

                const result = await this.mediaFolderRepository.search(
                    criteria,
                    Shopware.Context.api
                );

                if (result.total === 0) {
                    console.error(
                        `Media folder not found: ${part} in path ${folderPath}`
                    );
                    return null;
                }

                currentFolder = result.first();
            }

            this.mediaFolderCache[folderPath] = currentFolder.id;
            return currentFolder.id;
        },
    },
};
