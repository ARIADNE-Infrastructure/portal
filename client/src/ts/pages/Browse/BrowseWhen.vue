<template>
	<div>
    <p v-if="result.error" class="text-red">
      {{ result.error }}
    </p>
    <div :style="`width:${ width }px;margin-top:-2rem`" class="mx-auto">
      <time-line />
    </div>
  </div>
</template>

<script lang="ts">
import { Component, Vue } from 'vue-property-decorator';
import TimeLine from '../../components/Form/TimeLine.vue';


@Component({
  components: {
    TimeLine,
  }
})
export default class BrowseWhen extends Vue {
  width: number = 0;

  async created () {
    this.setWidth();

    await this.$store.dispatch('setSearch', {
      fromRoute: true
    });

    window.addEventListener('resize', this.setWidth);
  }

  destroyed () {
    window.removeEventListener('resize', this.setWidth);
  }

  setWidth () {
    let width = Math.min(innerWidth, 1440),
        height = innerHeight - 100;

    if (width > height * 1.6) {
      this.width = Math.min(height * 1.5, width);

    } else {
      this.width = width - 30;
    }
  }

  get result () {
    return this.$store.getters.result();
  }
}
</script>
