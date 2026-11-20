import template from './subspecies-detail.html.twig';
import { miniwaldDetailMixin } from '../../mixin/miniwald-detail.mixin';
import { mediaFolderMixin } from '../../mixin/media-folder.mixin';

const { Component, Mixin } = Shopware;
const { mapPropertyErrors } = Shopware.Component.getComponentHelper();
const { Criteria } = Shopware.Data;

Component.register('miniwald-subspecies-detail', {
    template,

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('placeholder'),
        mediaFolderMixin,
        miniwaldDetailMixin({
            entityName: 'miniwald_explorer_subspecies',
            listRouteName: 'miniwald.explorer.subspecies.list',
            snippetPrefix: 'miniwald-explorer.subspecies.detail',
            associations: ['image', 'species', 'translations'],
            defaults: { active: true, position: 0 },
        }),
    ],

    data() {
        return {
            subspeciesMediaFolderId: null,
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
        ...mapPropertyErrors('entity', ['name', 'speciesId']),

        speciesRepository() {
            return this.repositoryFactory.create('miniwald_explorer_species');
        },

        speciesCriteria() {
            const criteria = new Criteria(1, 100);
            criteria.addSorting(Criteria.sort('name', 'ASC'));
            return criteria;
        },
    },

    methods: {
        async loadMediaFolderId() {
            this.subspeciesMediaFolderId = await this.getMediaFolderId(
                'MiniwaldExplorer/Species/Subspecies'
            );
        },
    },
});
