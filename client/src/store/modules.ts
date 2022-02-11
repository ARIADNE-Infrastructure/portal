import store from './';
import { AggregationModule } from './modules/Aggregation';
import { GeneralModule } from './modules/General';
import { ResourceModule } from './modules/Resource';
import { SearchModule } from './modules/Search';
import { SubjectModule } from './modules/Subject';
import { ContactModule } from './modules/Contact';
import { TimelineModule } from './modules/Timeline';

export const generalModule = new GeneralModule({ store, name: "generalModule" });
export const resourceModule = new ResourceModule(generalModule, { store, name: "resourceModule" });
export const searchModule = new SearchModule(generalModule, { store, name: "searchModule" });
export const aggregationModule = new AggregationModule(generalModule, searchModule, { store, name: "aggregationModule" });
export const subjectModule = new SubjectModule(generalModule, { store, name: "subjectModule" });
export const contactModule = new ContactModule(generalModule, { store, name: "cocontactModulentact" });
export const timelineModule = new TimelineModule(searchModule, { store, name: "timelineModule" });
