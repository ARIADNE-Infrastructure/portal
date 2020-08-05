<script lang="ts">
import { Component, Vue, Prop } from 'vue-property-decorator';

@Component
export default class AccordionMixin extends Vue {
  show: boolean = false;

  toggle(): void {
    this.show = !this.show;
  }

  enter(el: any): void {
    el.style.height = el.scrollHeight + 'px';
  }

  leave(el: any): void {
    el.style.height = '0px';
  }
}
</script>
