<!-- 404 error page -->
<template>
  <div>
    <b-link
      to="/"
      class="p-md bg-red text-white hover:bg-red-90 transition-bg duration-300 w-full block text-center"
    >
      <i class="fas fa-long-arrow-alt-left mr-xs"></i>
      Go back to Front page
    </b-link>

    <div class="text-center pt-3x px-base max-w-screen-xl mx-auto">
      <h1 class="text-xl text-red mb-md">
        Sorry, we can't find that page (404).
      </h1>
      <p class="mb-md">
        Unfortunately we couldn't find the page you were looking for.<br>
        It might have been deleted, or relocated.
      </p>
      <p class="mb-xl">
        Try a search in the portal?
      </p>

      <div class="mb-2x">
        <filter-search
          class="max-w-2xl m-auto"
          color="yellow"
          bg="bg-yellow"
          hover="hover:bg-yellow-80"
          focus="focus:border-yellow"
          :big="true"
          :showFields="true"
        />
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { Component, Vue } from 'vue-property-decorator';
import utils from '@/utils/utils';
import BLink from '@/component/Base/Link.vue';

import FilterSearch from '@/component/Filter/Search.vue';

@Component({
  components: {
    BLink,
    FilterSearch
  }
})
export default class NotFound extends Vue {
  utils = utils;
}
</script>
