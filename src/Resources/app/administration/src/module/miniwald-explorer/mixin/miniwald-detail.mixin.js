/**
 * Reusable mixin for Miniwald Explorer entity detail/create pages.
 *
 * Usage:
 *   import { miniwaldDetailMixin } from '../../mixin/miniwald-detail.mixin';
 *
 *   Component.register('miniwald-species-detail', {
 *       template,
 *       mixins: [
 *           Mixin.getByName('notification'),
 *           Mixin.getByName('placeholder'),
 *           miniwaldDetailMixin({
 *               entityName: 'miniwald_explorer_species',
 *               listRouteName: 'miniwald.explorer.species.list',
 *               snippetPrefix: 'miniwald-explorer.species.detail',
 *               associations: ['image'],
 *               defaults: { active: true, position: 0 },
 *           })
 *       ],
 *   });
 */

const { Criteria } = Shopware.Data;

export function miniwaldDetailMixin(config) {
    const {
        entityName,
        listRouteName,
        snippetPrefix,
        publishDataId = null,
        associations = ['image'],
        defaults = {},
    } = config;

    const dataId = publishDataId || `${entityName.replace(/_/g, '-')}__entity`;

    return {
        inject: ['repositoryFactory', 'acl'],

        data() {
            return {
                entity: null,
                isLoading: false,
                processSuccess: false,
                showMediaModal: false,
                mediaUploadInProgress: false,
            };
        },

        computed: {
            identifier() {
                return this.entity !== null
                    ? this.entity.translated?.name || this.entity.name || ''
                    : '';
            },

            entityIsLoading() {
                return this.isLoading || this.entity == null;
            },

            isSaveDisabled() {
                return this.mediaUploadInProgress;
            },

            entityRepository() {
                return this.repositoryFactory.create(entityName);
            },

            mediaRepository() {
                return this.repositoryFactory.create('media');
            },

            tooltipSave() {
                if (!this.acl.can(`${entityName}:editor`)) {
                    return {
                        message: this.$tc('sw-privileges.tooltip.warning'),
                        disabled: this.acl.can(`${entityName}:editor`),
                        showOnDisabledElements: true,
                    };
                }

                const systemKey = this.$device.getSystemKey();

                return {
                    message: `${systemKey} + S`,
                    appearance: 'light',
                };
            },

            tooltipCancel() {
                return {
                    message: 'ESC',
                    appearance: 'light',
                };
            },
        },

        watch: {
            '$route.params.id'() {
                this.createdComponent();
            },
        },

        created() {
            this.createdComponent();
        },

        methods: {
            createdComponent() {
                Shopware.ExtensionAPI.publishData({
                    id: dataId,
                    path: 'entity',
                    scope: this,
                });

                if (
                    this.$route.params.id &&
                    this.$route.params.id !== 'create'
                ) {
                    this.loadEntityData();
                } else {
                    this.createNewEntity();
                }
            },

            createNewEntity() {
                this.entity = this.entityRepository.create(
                    Shopware.Context.api
                );
                // Apply defaults
                Object.keys(defaults).forEach((key) => {
                    this.entity[key] = defaults[key];
                });
            },

            loadEntityData() {
                this.isLoading = true;

                const criteria = new Criteria(1, 1);
                associations.forEach((assoc) => {
                    criteria.addAssociation(assoc);
                });

                this.entityRepository
                    .get(this.$route.params.id, Shopware.Context.api, criteria)
                    .then((entity) => {
                        this.entity = entity;
                        this.isLoading = false;
                    })
                    .catch(() => {
                        this.isLoading = false;
                    });
            },

            onClickSave() {
                if (this.mediaUploadInProgress) {
                    this.createNotificationWarning({
                        message: this.$tc(
                            'miniwald-explorer.general.mediaUploadInProgress'
                        ),
                    });
                    return;
                }

                this.isLoading = true;

                this.entityRepository
                    .save(this.entity, Shopware.Context.api)
                    .then(() => {
                        this.isLoading = false;
                        this.processSuccess = true;
                        this.$router.push({ name: listRouteName });
                    })
                    .catch(() => {
                        this.isLoading = false;
                        this.createNotificationError({
                            message: this.$tc(
                                `${snippetPrefix}.errorMessage`,
                                0,
                                { name: this.entity.name || '' }
                            ),
                        });
                    });
            },

            onClickCancel() {
                this.$router.push({ name: listRouteName });
            },

            onMediaUploadStart() {
                this.mediaUploadInProgress = true;
            },

            setMediaItem({ targetId }) {
                this.entity.imageId = targetId;
                this.mediaUploadInProgress = false;
            },

            onMediaSelectionChange([mediaEntity]) {
                this.entity.imageId = mediaEntity.id;
                this.showMediaModal = false;
            },

            onUnlinkLogo() {
                this.entity.imageId = null;
            },

            onDropMedia(dragData) {
                this.setMediaItem({ targetId: dragData.id });
            },

            saveFinish() {
                this.processSuccess = false;
            },

            onChangeLanguage() {
                this.loadEntityData();
            },
        },
    };
}
