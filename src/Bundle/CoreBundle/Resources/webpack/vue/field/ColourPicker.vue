<template>
    <div class="colour-picker" ref="colourpicker">
        <input type="text" :id="id" :name="name" class="colour-text-input" v-model="colourValue" @focus="showPicker()" @input="updateFromInput" />
        <span class="colour-picker-container">
            <span class="current-colour" :style="'background-color: ' + colourValue" @click="togglePicker()"></span>
            <chrome-picker :value="colours" @input="updateFromPicker" v-if="displayPicker" />
        </span>
    </div>
</template>

<script>
  import { Chrome } from 'vue-color';

  let defaultColour = '#000000'; // Colour when the value is empty

  export default {
      data: function () {
          return {
              content: this.value,
              colours: {
                  hex: defaultColour,
              },
              colourValue: '',
              displayPicker: false,
          }
      },
      mounted() {
          this.setColour(this.value || defaultColour);
      },
      components: {
          'chrome-picker': Chrome
      },
      props: [
          'value',
          'id',
          'name'
      ],
      methods: {
          setColour(colour) {
              this.updateColours(colour);
              this.colourValue = colour;
          },
          updateColours(colour) {
              if(colour.slice(0, 1) == '#') {
                  this.colours = {
                      hex: colour
                  };
              }
              else if(colour.slice(0, 4) == 'rgba') {
                  var rgba = colour.replace(/^rgba?\(|\s+|\)$/g,'').split(','),
                      hex = '#' + ((1 << 24) + (parseInt(rgba[0]) << 16) + (parseInt(rgba[1]) << 8) + parseInt(rgba[2])).toString(16).slice(1);
                  this.colours = {
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
              this.updateColours(this.colourValue);
          },
          updateFromPicker(colour) {
              this.colours = colour;
              if(colour.rgba.a == 1) {
                  this.colourValue = colour.hex;
              }
              else {
                  this.colourValue = 'rgba(' + colour.rgba.r + ', ' + colour.rgba.g + ', ' + colour.rgba.b + ', ' + colour.rgba.a + ')';
              }
          },
          documentClick(e) {
              var el = this.$refs.colourpicker,
                  target = e.target;
              if(el !== target && !el.contains(target)) {
                  this.hidePicker()
              }
          }
      },
      watch: {
          colourValue(val) {
              if(val) {
                  this.updateColours(val);
                  this.$emit('input', val);
              }
          }
      },
  }
</script>

<style lang="scss">
    .colour-picker {
        position: relative;
        width: 229px;

        .colour-text-input {
            border: 1px solid #ccc;
            border-radius: 4px 0 0 4px;
            box-shadow: rgba(0, 0, 0, 0.075) 0px 1px 1px 0px inset;
            box-sizing: border-box;
            color: rgb(85, 85, 85);
            float: left;
            font-size: 14px;
            line-height: 20px;
            padding: 6px 12px;
            transition: border-color ease-in-out .15s,box-shadow ease-in-out .15s;
            width: 188px;
            z-index: 2;

            &:focus {
                border-color: #66afe9;
                outline: 0;
                box-shadow: inset 0 1px 1px rgba(0,0,0,.075), 0 0 8px rgba(102,175,233,.6);
            }
        }

        .colour-picker-container {
            background-color: rgb(238, 238, 238);
            border: 1px solid #ccc;
            border-left: 0;
            border-radius: 0 4px 4px 0;
            box-sizing: border-box;
            display: table-cell;
            height: 34px;
            line-height: 14px;
            padding: 6px 12px;
            vertical-align: middle;
            width: 41px;
        }

        .current-colour {
            display: inline-block;
            width: 16px;
            height: 16px;
            cursor: pointer;
        }
    }

    .vc-chrome {
        position: absolute;
        top: 35px;
        right: 0;
        z-index: 9;
    }
</style>
