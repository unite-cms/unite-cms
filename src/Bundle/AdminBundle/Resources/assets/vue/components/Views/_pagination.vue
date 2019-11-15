<template>
  <ul class="uk-pagination uk-flex-center">
    <li v-if="current > 0" @click.prevent="setPage(current - 1)"><a href="#"><span uk-pagination-previous></span></a></li>
    <li @click.prevent="setPage(page)" :class="{ 'uk-active': current === page }" v-for="page in pages"><a href="#">{{ page + 1 }}</a></li>
    <li v-if="pages.length > current + 1" @click.prevent="setPage(current + 1)"><a href="#"><span uk-pagination-next></span></a></li>
  </ul>
</template>
<script>
  export default {
      props: {
          total: Number,
          count: Number,
          offset: Number,
          limit: Number,
      },
      computed: {
          pages() {
              let pages = [];
              for(let i = 0; i < Math.ceil(this.total / this.limit); i++){
                  pages.push(i);
              }
              return pages;
          },
          current() {
              return Math.ceil(this.offset / this.limit);
          }
      },
      methods: {
          setPage(page) {
              this.$emit('change', {
                  page: page,
                  offset: this.limit * page,
              });
          }
      }
  }
</script>
