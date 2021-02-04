<script lang="ts">
import { Component, Vue, Prop, Watch } from 'vue-property-decorator';

@Component
export default class ListAccordionMixin extends Vue {
  show: boolean = false;
  item: any = null;

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
