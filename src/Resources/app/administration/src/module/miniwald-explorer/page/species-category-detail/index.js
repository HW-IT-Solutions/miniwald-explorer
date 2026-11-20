import template from './species-category-detail.html.twig';
import { miniwaldDetailMixin } from '../../mixin/miniwald-detail.mixin';
import { mediaFolderMixin } from '../../mixin/media-folder.mixin';

const { Component, Mixin } = Shopware;
const { mapPropertyErrors } = Shopware.Component.getComponentHelper();

Component.register('miniwald-species-category-detail', {
    template,

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('placeholder'),
        mediaFolderMixin,
        miniwaldDetailMixin({
            entityName: 'miniwald_explorer_species_category',
            listRouteName: 'miniwald.explorer.species.category.list',
            snippetPrefix: 'miniwald-explorer.speciesCategory.detail',
            associations: ['image', 'species', 'translations'],
            defaults: { active: true, position: 0 },
        }),
    ],

    data() {
        return {
            categoryMediaFolderId: null,
        };
    },

    created() {
        this.loadMediaFolderId();
    },

    metaInfo() {
        return {
            title: this.$createTitle(this.identifier),
        };
    },

    computed: {
        ...mapPropertyErrors('entity', ['name', 'position', 'active']),

        // Alias for backward compatibility with mapPropertyErrors
        // mapPropertyErrors generates e.g. entityNameError, entityPositionError, entityActiveError
    },

    methods: {
        async loadMediaFolderId() {
            this.categoryMediaFolderId = await this.getMediaFolderId(
                'MiniwaldExplorer/Species/Categories'
            );
        },
    },
});
