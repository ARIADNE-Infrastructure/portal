import { VuexModule, Module, Mutation, Action, RegisterOptions } from "vuex-class-modules";

/**
 * BreadCrumbModule for building crumbtrails and keeping track of users activity.
 */

@Module
export class BreadCrumbModule extends VuexModule {
  private crumbtrail: any[] = [];
  private from: any = [];

  constructor(options: RegisterOptions) {
    super(options);
  }

  /**
   * Set referral. Fired from router.ts (Router) router.beforeEach hook.
   */
  @Action
  setFrom(from: string) {
    this.updateFrom(from);
  }

  @Action
  setCrumb(crumb: any) {
    /* If crumb allready exists in crumbtrail do not add.
       That means that user has navigated one step back, meaning thar we must delete last crumb. */
    if(!this.crumbtrail.find(item => item.path === crumb.path )) {
      this.updateCrumb(crumb);
    } else {
      this.crumbtrail.pop();
    }
  }

  @Action
  resetCrumb() {
    this.destroyCrumb();
  }

  @Mutation
  updateCrumb(crumb: {}) {
    this.crumbtrail.push(crumb);
  }

  @Mutation
  updateFrom(from: string) {
    this.from = from;
  }

  @Mutation
  destroyCrumb() {
    this.crumbtrail = [];
  }
  get getCrumbs(): any {
    return this.crumbtrail;
  }

  get getFrom(): any {
    return this.from;
  }

  get getPrevious() {
    return this.crumbtrail[this.crumbtrail.length-2];
  }
}
