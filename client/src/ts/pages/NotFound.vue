<!-- 404 error page -->
<template>
	<div class="text-center mb-5x">
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
      <search
        class="max-w-2xl m-auto"
        color="yellow"
        bg="bg-yellow"
        hover="hover:bg-yellow-80"
        focus="focus:border-yellow"
        :big="true"
        :showFields="true"
      />
    </div>

    <router-link to="/" class="border-base border-midGray p-sm hover:bg-yellow hover:border-yellow hover:text-white transition-all duration-300">
      <i class="fas fa-long-arrow-alt-left mr-xs"></i> Go back to Front page
    </router-link>
  </div>
</template>

<script lang="ts">
import { Component, Vue } from 'vue-property-decorator';
import Search from '../components/Form/Search.vue';
import utils from '../utils/utils';

@Component({
  components: {
    Search
  }
})
export default class NotFound extends Vue {
  utils = utils;
}
</script>
