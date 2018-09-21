<template>
    <div class="uk-margin uk-grid-small" uk-grid>
        <div :class="{ 'uk-width-1-1' : !showTitleWidget, 'uk-width-1-2' : showTitleWidget }">
            <label class="uk-form-label" :for="input_id">{{ label }}</label>
            <div class="uk-form-controls">
                <div class="url-control uk-inline">
                    <input :id="input_id" type="url" class="uk-input" :name="name + '[url]'" :value="url" />
                    <a v-if="showTargetWidget" class="uk-form-icon uk-form-icon-flip" :class="{ external : (target == '_blank')}" uk-tooltip :title="targetTooltip" v-on:click.prevent="toggleTarget">
                        <i data-feather="external-link" width="16" height="16"></i>
                    </a>
                </div>
            </div>
        </div>
        <div v-if="showTitleWidget" class="uk-width-1-2@s">
            <label class="uk-form-label" :for="input_id + '_title'">Title</label>
            <div class="uk-form-controls">
                <input :id="input_id + '_title'" type="text" class="uk-input" :name="name + '[title]'" :value="title" />
            </div>
        </div>
        <input v-if="showTargetWidget" type="hidden" :name="name + '[target]'" :value="target" />
    </div>
</template>

<script>
    export default {
        data(){
          console.log(this.name);
          console.log(this.titleWidget);
          console.log(this.targetWidget);

          let targetWidget = (typeof this.targetWidget !== 'undefined') ? JSON.parse(this.targetWidget) : false;
          let titleWidget = (typeof this.titleWidget !== 'undefined') ? JSON.parse(this.titleWidget) : false;

          return {
              input_id: this.name + '_url',
              url: this.value,
              target: targetWidget ? (targetWidget.value ? targetWidget.value : targetWidget.choices[0].value) : '',
              title: titleWidget ? titleWidget.value : '',
              showTargetWidget: targetWidget,
              showTitleWidget: titleWidget,
          };
        },
        props: [
            'id',
            'label',
            'name',
            'value',
            'titleWidget',
            'targetWidget',
        ],
        computed: {
            targetTooltip() {
                if(!this.showTargetWidget) {
                    return '';
                }

                return this.showTargetWidget.choices
                    .filter((choice) => { return this.target == choice.value; })
                    .map((choice) => { return choice.label; });
            }
        },
        methods: {
            toggleTarget(){
                if(!this.showTargetWidget) {
                    return;
                }

                this.target = this.showTargetWidget.choices
                    .filter((choice) => { return this.target != choice.value; })
                    .map((choice) => { return choice.value; });
            }
        }
    }
</script>

<style lang="scss">
    @import "../../sass/base/variables";

    unite-cms-core-link-field {
        position: relative;
        display: block;

        .url-control {
            width: 100%;

            .uk-form-icon {
                opacity: 0.5;
                &.external {
                    opacity: 1;
                    color: $global-link-color;
                }
            }
        }
    }
</style>
