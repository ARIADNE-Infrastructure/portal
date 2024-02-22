import { reactive } from 'vue';
import { AggregationModule } from './modules/Aggregation';
import { GeneralModule } from './modules/General';
import { ResourceModule } from './modules/Resource';
import { SearchModule } from './modules/Search';
import { SubjectModule } from './modules/Subject';
import { ContactModule } from './modules/Contact';
import { BreadCrumbModule } from './modules/BreadCrumb';
import { PeriodsModule } from './modules/Periods';

export const generalModule = reactive(new GeneralModule());
export const resourceModule = reactive(new ResourceModule(generalModule));
export const searchModule = reactive(new SearchModule(generalModule));
export const periodsModule = reactive(new PeriodsModule(searchModule));
export const aggregationModule = reactive(new AggregationModule(searchModule, periodsModule));
export const subjectModule = reactive(new SubjectModule(generalModule));
export const contactModule = reactive(new ContactModule(generalModule));
export const breadCrumbModule = reactive(new BreadCrumbModule());
