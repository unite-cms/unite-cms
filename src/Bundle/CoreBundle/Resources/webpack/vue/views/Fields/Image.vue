<template>
    <div class="view-field view-field-image">
        <span v-if="src" class="uk-inline-clip">
            <img :src="src" />
        </span>
    </div>
</template>

<script>
    import BaseField from '../Base/BaseField.vue';

    export default {
        extends: BaseField,
        data(){
            return {
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
    .view-field-image {
        height: 50px;
        width: 80px;
        text-align: center;

        .uk-inline-clip {
            width: auto;
            height: 100%;
            border-radius: 5px;
            border: 2px solid white;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
            margin-top: -2px;
            margin-left: -2px;
            cursor: pointer;

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
