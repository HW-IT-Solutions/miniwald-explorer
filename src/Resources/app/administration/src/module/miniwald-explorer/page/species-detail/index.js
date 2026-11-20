import template from './species-detail.html.twig';
import { miniwaldDetailMixin } from '../../mixin/miniwald-detail.mixin';
import { mediaFolderMixin } from '../../mixin/media-folder.mixin';

const { Component, Mixin } = Shopware;
const { mapPropertyErrors } = Shopware.Component.getComponentHelper();
const { Criteria } = Shopware.Data;

Component.register('miniwald-species-detail', {
    template,

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('placeholder'),
        mediaFolderMixin,
        miniwaldDetailMixin({
            entityName: 'miniwald_explorer_species',
            listRouteName: 'miniwald.explorer.species.list',
            snippetPrefix: 'miniwald-explorer.species.detail',
            associations: [
                'image',
                'speciesCategory',
                'subspecies',
                'translations',
            ],
            defaults: { active: true, position: 0 },
        }),
    ],

    data() {
        return {
            speciesMediaFolderId: null,
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
        ...mapPropertyErrors('entity', ['name', 'speciesCategoryId']),

        speciesCategoryRepository() {
            return this.repositoryFactory.create(
                'miniwald_explorer_species_category'
            );
        },

        speciesCategoryCriteria() {
            const criteria = new Criteria(1, 100);
            criteria.addSorting(Criteria.sort('position', 'ASC'));
            return criteria;
        },
    },

    methods: {
        async loadMediaFolderId() {
            this.speciesMediaFolderId = await this.getMediaFolderId(
                'MiniwaldExplorer/Species/Species'
            );
        },
    },
});
