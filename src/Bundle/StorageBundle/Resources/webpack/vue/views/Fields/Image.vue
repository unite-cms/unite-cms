<template>
    <div class="view-field view-field-image">
        <a :href="'#' + modalId" uk-toggle v-if="src" class="uk-inline-clip uk-box-shadow-small uk-box-shadow-hover-medium">
            <img :src="src" />
        </a>
        <div class="uk-flex-top" :id="modalId" uk-modal>
            <div class="uk-modal-dialog uk-margin-auto-vertical">
                <button class="uk-modal-close-outside" type="button" uk-close></button>
                <img :src="src" />
            </div>
        </div>
    </div>
</template>

<script>
    import BaseField from '../../../../../../CoreBundle/Resources/webpack/vue/views/Base/BaseField.vue';

    export default {
        extends: BaseField,
        data(){
            return {
                modalId: 'modal-' + this._uid,
                src: this.row[this.identifier] ? this.row[this.identifier].url : null
            }
        },
        methods: {

            /**
             * @inheritdoc
             */
            fieldQuery(identifier, field) {
                return identifier + ' { url }'
            },
        }
    }
</script>

<style scoped lang="scss">
    .uk-modal-dialog {
        width: 900px;
    }

    .view-field-image {
        height: 50px;
        width: 80px;
        text-align: left;

        .uk-inline-clip {
            width: auto;
            height: 100%;
            border-radius: 5px;
            border: 2px solid white;
            margin: -2px 0 0 -2px;

            img {
                height: 100%;
                width: auto;
                max-width: none;
                transform: translateX(-50%);
                position: relative;
                left: 50%;
            }
        }
    }
</style>
