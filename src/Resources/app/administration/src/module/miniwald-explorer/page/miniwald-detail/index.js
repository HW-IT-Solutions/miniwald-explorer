import template from './miniwald-detail.html.twig';
import { miniwaldDetailMixin } from '../../mixin/miniwald-detail.mixin';
import { mediaFolderMixin } from '../../mixin/media-folder.mixin';

const { Component, Mixin } = Shopware;
const { mapPropertyErrors } = Shopware.Component.getComponentHelper();

Component.register('miniwald-miniwald-detail', {
    template,

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('placeholder'),
        mediaFolderMixin,
        miniwaldDetailMixin({
            entityName: 'miniwald_explorer_miniwald',
            listRouteName: 'miniwald.explorer.miniwald.list',
            snippetPrefix: 'miniwald-explorer.miniwald.detail',
            associations: ['image', 'translations'],
            defaults: {
                radiusMeters: 500,
            },
        }),
    ],

    data() {
        return {
            miniForestsMediaFolderId: null,
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
        ...mapPropertyErrors('entity', [
            'name',
            'latitude',
            'longitude',
            'radiusMeters',
        ]),
    },

    methods: {
        async loadMediaFolderId() {
            this.miniForestsMediaFolderId = await this.getMediaFolderId(
                'MiniwaldExplorer/Mini Forests'
            );
        },
    },
});
