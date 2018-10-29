<template>
    <div :style="style" class="view-field view-field-choice fixed-width">
        <div>
            <span class="uk-label" v-for="v in values">{{ v }}</span>
        </div>
    </div>
</template>

<script>
    import BaseField from '../Base/BaseField.vue';

    export default {
        extends: BaseField,
        computed: {
            values() {
                let values = typeof this.value === 'string' ? [this.value] : this.value;
                return values.map((value) => {
                    if(typeof this.settings.choices[value] !== 'undefined') {
                        return this.settings.choices[value];
                    }
                    return value;
                });
            },
        }
    }
</script>

<style scoped lang="scss">
    .view-field-choice {
        white-space: nowrap;
        text-align: center;

        > div {
            display: inline-block;
            white-space: nowrap;
        }

        .uk-label {
            margin-right: 5px;

            &:last-child {
                margin-right: 0;
            }
        }
    }
</style>
