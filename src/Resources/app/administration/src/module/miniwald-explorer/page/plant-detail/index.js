import template from './plant-detail.html.twig';
import { miniwaldDetailMixin } from '../../mixin/miniwald-detail.mixin';
import { mediaFolderMixin } from '../../mixin/media-folder.mixin';

const { Component, Mixin } = Shopware;
const { mapPropertyErrors } = Shopware.Component.getComponentHelper();
const { Criteria } = Shopware.Data;

Component.register('miniwald-plant-detail', {
    template,

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('placeholder'),
        mediaFolderMixin,
        miniwaldDetailMixin({
            entityName: 'miniwald_explorer_plant',
            listRouteName: 'miniwald.explorer.plant.list',
            snippetPrefix: 'miniwald-explorer.plant.detail',
            associations: ['image', 'miniwald'],
            defaults: { active: true, sortOrder: 0 },
        }),
    ],

    data() {
        return {
            plantMediaFolderId: null,
        };
    },

    created() {
        this.loadMediaFolderId();
    },

    metaInfo() {
        return {
            title: this.identifier,
        };
    },

    computed: {
        ...mapPropertyErrors('entity', ['name']),

        miniwaldCriteria() {
            const criteria = new Criteria(1, 100);
            criteria.addSorting(Criteria.sort('name', 'ASC'));
            return criteria;
        },
    },

    methods: {
        async loadMediaFolderId() {
            this.plantMediaFolderId = await this.getMediaFolderId(
                'MiniwaldExplorer/Plants'
            );
        },
    },
});
