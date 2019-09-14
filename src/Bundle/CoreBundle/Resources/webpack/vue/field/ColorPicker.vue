<template>
    <div class="colour-picker" ref="colorpicker">
        <input type="hidden" :id="id" :name="name" class="colour-text-input" v-model="colorValue" @focus="showPicker()" @input="updateFromInput" />
        <div class="color-picker-container">
            <button class="uk-button current-color" :style="{ backgroundColor: colorValue }" @click.prevent="togglePicker()"></button>
            <span class="uk-text-meta">{{ colorValue }}</span>
            <component :is="picker" v-if="displayPicker" :value="colors" @input="updateFromPicker" :palette="presetColors" />
        </div>
    </div>
</template>

<script>
  import { Sketch, Compact } from 'vue-color';

  let defaultColor = '#000000'; // Color when the value is empty

  export default {
      data: function () {
          let allowedColors = this.allowedColors ? JSON.parse(this.allowedColors) : [];
          return {
              content: this.value,
              colors: {
                  hex: defaultColor,
              },
              colorValue: '',
              displayPicker: false,
              picker: allowedColors.length > 0 ? Compact : Sketch,
              presetColors: allowedColors,
          }
      },
      mounted() {
          this.setColor(this.value || defaultColor);
      },
      props: [
          'value',
          'id',
          'name',
          'allowedColors'
      ],
      methods: {
          setColor(color) {
              this.updateColors(color);
              this.colorValue = color;
          },
          updateColors(color) {
              if(color.slice(0, 1) === '#') {
                  this.colors = {
                      hex: color
                  };
              }
              else if(color.slice(0, 4) === 'rgba') {
                  var rgba = color.replace(/^rgba?\(|\s+|\)$/g,'').split(','),
                      hex = '#' + ((1 << 24) + (parseInt(rgba[0]) << 16) + (parseInt(rgba[1]) << 8) + parseInt(rgba[2])).toString(16).slice(1);
                  this.colors = {
                      hex: hex,
                      a: rgba[3],
                  }
              }
          },
          showPicker() {
              document.addEventListener('click', this.documentClick);
              this.displayPicker = true;
          },
          hidePicker() {
              document.removeEventListener('click', this.documentClick);
              this.displayPicker = false;
          },
          togglePicker() {
              this.displayPicker ? this.hidePicker() : this.showPicker();
          },
          updateFromInput() {
              this.updateColors(this.colorValue);
          },
          updateFromPicker(color) {
              this.colors = color;
              if(color.rgba.a === 1) {
                  this.colorValue = color.hex;
              }
              else {
                  this.colorValue = 'rgba(' + color.rgba.r + ', ' + color.rgba.g + ', ' + color.rgba.b + ', ' + color.rgba.a + ')';
              }
          },
          documentClick(e) {
              let el = this.$refs.colorpicker,
                  target = e.target;
              if(el !== target && !el.contains(target)) {
                  this.hidePicker()
              }
          }
      },
      watch: {
          colorValue(val) {
              if(val) {
                  this.updateColors(val);
                  this.$emit('input', val);
              }
          }
      },
  }
</script>

<style lang="scss" scoped>

    .color-picker-container {
        background: #FFFFFF;
        border: 1px solid #D8D8D8;
        box-shadow: 0 2px 4px 0 rgba(0, 0, 0, 0.06);
        padding: 5px;
        border-radius: 2px;
        margin: 5px 0;
        width: auto;
        display: inline-block;
        position: relative;

        .vc-sketch, .vc-compact {
            position: absolute;
            top: 0;
            left: 110%;
            z-index: 10;
        }

        .current-color {
            width: 40px;
            height: 40px;
        }

        .uk-text-meta {
            display: inline-block;
            width: 200px;
            margin-left: 10px;
        }
    }
</style>
