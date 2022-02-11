// store/modules/MyStoreModule.ts
import { VuexModule, Module, Mutation, Action, RegisterOptions } from "vuex-class-modules";
import axios from 'axios';
import utils from '@/utils/utils';
import { SearchModule } from './Search';

@Module
export class TimelineModule extends VuexModule {
  private searchModule: SearchModule;
  private result: any = {};
  private loading: boolean = false;

  constructor (searchModule: SearchModule, options: RegisterOptions) {
    super(options);
    this.searchModule = searchModule;
  }

  @Action
  async setSearch() {
    this.updateLoading(true);

    const params = this.searchModule.getParams;
    const url = process.env.apiUrl + '/timeline';

    try {
      const res = await axios.get(utils.paramsToString(url, params));
      let data = res?.data;

      if (utils.objectIsNotEmpty(data)) {
        this.updateResult(data);
      }
    } catch (ex) {}

    this.updateLoading(false);
  }

  @Mutation
  updateResult(result: any) {
    this.result = result;
  }

  @Mutation
  updateLoading(loading: boolean) {
    this.loading = loading;
  }

  get getResult(): any {
    return this.result;
  }

  get getLoading(): boolean {
    return this.loading;
  }
}
