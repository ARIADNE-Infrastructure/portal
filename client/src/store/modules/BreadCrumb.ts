/**
 * BreadCrumbModule for building crumbtrails and keeping track of users activity.
 */

export class BreadCrumbModule {
  crumbtrail: any[] = [];
  from: any = [];

  /**
   * Set referral. Fired from router.ts (Router) router.beforeEach hook.
   */
  setFrom(from: string) {
    this.updateFrom(from);
  }

  setCrumb(crumb: any) {
    /* If crumb allready exists in crumbtrail do not add.
       That means that user has navigated one step back, meaning thar we must delete last crumb. */
    if(!this.crumbtrail.find(item => item.path === crumb.path )) {
      this.updateCrumb(crumb);
    } else {
      this.crumbtrail.pop();
    }
  }

  resetCrumb() {
    this.destroyCrumb();
  }

  updateCrumb(crumb: {}) {
    this.crumbtrail.push(crumb);
  }

  updateFrom(from: string) {
    this.from = from;
  }

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
