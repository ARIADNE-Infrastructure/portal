import store from './';
import { AggregationModule } from './modules/Aggregation';
import { GeneralModule } from './modules/General';
import { ResourceModule } from './modules/Resource';
import { SearchModule } from './modules/Search';
import { SubjectModule } from './modules/Subject';
import { ContactModule } from './modules/Contact';
import { BreadCrumbModule } from './modules/BreadCrumb';
import { PeriodsModule } from './modules/Periods';

export const generalModule = new GeneralModule({ store, name: "generalModule" });
export const resourceModule = new ResourceModule(generalModule, { store, name: "resourceModule" });
export const searchModule = new SearchModule(generalModule, { store, name: "searchModule" });
export const periodsModule = new PeriodsModule(searchModule, { store, name: "periodsModule" });
export const aggregationModule = new AggregationModule(searchModule, periodsModule, { store, name: "aggregationModule" });

export const subjectModule = new SubjectModule(generalModule, { store, name: "subjectModule" });
export const contactModule = new ContactModule(generalModule, { store, name: "contactModule" });
export const breadCrumbModule = new BreadCrumbModule({ store, name: "breadCrumbModule" });

