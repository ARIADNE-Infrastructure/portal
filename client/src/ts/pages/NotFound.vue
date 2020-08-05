<!-- 404 error page -->
<template>
	<div>
    <h1 class="text-xl text-red mb-md">
      Sorry, we can't find that page (404).
    </h1>
    <p>
      Unfortunately we couldn't find the page you were looking for. Try searching the portal. An email was sent to report the issue.
    </p>

    <search
      class="max-w-2xl my-xl"
      color="yellow"
      bg="bg-yellow"
      hover="hover:bg-yellow-80"
      focus="focus:border-yellow"
      :big="true"
      :autoFocus="true"
      :showFields="true"
    />

    <p class="mt-base underline">
      <router-link to="/">
        <i class="fas fa-long-arrow-alt-left mr-xs"></i> Go back to Front page
      </router-link>
    </p>
  </div>
</template>

<script lang="ts">
import { Component, Vue } from 'vue-property-decorator';
import Search from '../components/Form/Search.vue';

@Component({
  components: {
    Search
  }
})
export default class NotFound extends Vue {
}
</script>
